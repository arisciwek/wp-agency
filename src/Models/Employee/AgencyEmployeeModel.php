<?php
/**
 * Agency Employee Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Employee
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Employee/AgencyEmployeeModel.php
 *
 * Description: Model untuk mengelola data karyawan agency di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.2.0 - 2025-10-22
 * - Removed fields: finance, operation, legal, purchase from create() method
 * - Fields removed from database schema, no longer needed
 * - Updated insert format string to match remaining fields
 *
 * 1.1.0 - 2025-01-22
 * - Task-2066: Added findByUserAndDivision() method for auto entity creation
 * - Method checks if employee already exists for user in division
 *
 * 1.0.0 - 2024-01-12
 * - Initial implementation
 * - Added core CRUD operations
 * - Added DataTables integration
 * - Added cache support
 */

namespace WPAgency\Models\Employee;

use WPAgency\Cache\AgencyCacheManager;

class AgencyEmployeeModel {
    private $table;
    private $agency_table;
    private $division_table;
    private $cache; // Tambahkan properti cache

    // Add class constant for valid status values
    private const VALID_STATUSES = ['active', 'inactive'];

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_agency_employees';
        $this->agency_table = $wpdb->prefix . 'app_agencies';
        $this->division_table = $wpdb->prefix . 'app_agency_divisions';
        $this->cache = new AgencyCacheManager();
    }

    public function create(array $data): ?int {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'agency_id' => $data['agency_id'],
                'division_id' => $data['division_id'],
                'user_id' => $data['user_id'] ?? get_current_user_id(),
                'name' => $data['name'],
                'position' => $data['position'],
                'keterangan' => $data['keterangan'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'created_by' => $data['created_by'] ?? get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'status' => $data['status'] ?? 'active'
            ],
            [
                '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'
            ]
        );

        if ($result === false) {
            return null;
        }

        $employee_id = (int) $wpdb->insert_id;
        

        // Invalidasi cache terkait agency_employee
        $this->cache->delete('agency_active_employee_count', (string)$data['agency_id']);
        $this->cache->delete('agency_employee_count', (string)$data['agency_id']);
        // Also invalidate global employee count cache
        $this->cache->delete('agency_employee_count');
        $this->cache->delete('division_agency_employee_list', (string)$data['division_id']);
        $this->cache->invalidateDataTableCache('agency_employee_list', [
            'agency_id' => $data['agency_id']
        ]);

        return $employee_id;
    }

    public function find(int $id): ?object {
        global $wpdb;

        // Cek cache terlebih dahulu
        $cached_employee = $this->cache->get('agency_employee', $id);

        if ($cached_employee !== null) {
            return $cached_employee;
        }
        
        // Jika tidak ada di cache, ambil dari database
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, 
                   c.name as agency_name,
                   b.name as division_name,
                   u.display_name as created_by_name
            FROM {$this->table} e
            LEFT JOIN {$this->agency_table} c ON e.agency_id = c.id
            LEFT JOIN {$this->division_table} b ON e.division_id = b.id
            LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID
            WHERE e.id = %d
        ", $id));
        
        // Simpan ke cache jika ditemukan
        if ($result) {
            $this->cache->set('agency_employee', $result, $this->cache::getCacheExpiry(), $id);
        }

        return $result;
    }

    /**
     * Find employee by user_id and division_id
     * Used to check if employee already exists before auto-creation
     *
     * @param int $user_id WordPress user ID
     * @param int $division_id Division ID
     * @return object|null Employee object or null if not found
     */
    public function findByUserAndDivision(int $user_id, int $division_id): ?object {
        global $wpdb;

        // Create cache key
        $cache_key = "user_{$user_id}_division_{$division_id}";

        // Check cache first
        $cached_result = $this->cache->get('agency_employee_by_user_division', $cache_key);
        if ($cached_result !== null) {
            return $cached_result;
        }

        // Query database
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT e.*,
                   c.name as agency_name,
                   b.name as division_name
            FROM {$this->table} e
            LEFT JOIN {$this->agency_table} c ON e.agency_id = c.id
            LEFT JOIN {$this->division_table} b ON e.division_id = b.id
            WHERE e.user_id = %d AND e.division_id = %d
        ", $user_id, $division_id));

        // Cache the result
        if ($result) {
            $this->cache->set('agency_employee_by_user_division', $result, 300, $cache_key);
        }

        return $result;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        // Simpan data agency_id dan division_id sebelum update
        $current_employee = $this->find($id);
        $agency_id = $current_employee->agency_id;
        $old_division_id = $current_employee->division_id;
        $new_division_id = $data['division_id'] ?? $old_division_id;

        // Update data
        $updateData = [
            'name' => $data['name'],
            'position' => $data['position'],
            'finance' => $data['finance'],
            'operation' => $data['operation'],
            'legal' => $data['legal'],
            'purchase' => $data['purchase'],
            'keterangan' => $data['keterangan'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'division_id' => $new_division_id,
            'updated_at' => current_time('mysql')
        ];

        $format = [
            '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s'
        ];

        // Add status to update data if provided and valid
        if (isset($data['status']) && in_array($data['status'], self::VALID_STATUSES)) {
            $updateData['status'] = $data['status'];
            $format[] = '%s';
        }

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $format,
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        // Invalidasi cache
        $this->cache->delete('agency_employee', $id);
        $this->cache->delete('agency_employee_count', (string)$agency_id);
        $this->cache->delete('agency_active_employee_count', (string)$agency_id);
        // Also invalidate global employee count cache
        $this->cache->delete('agency_employee_count');
        // Invalidate dashboard stats cache
        $this->cache->delete('agency_stats_0');

        // Invalidasi cache divisi lama dan baru jika berbeda
        $this->cache->delete('division_agency_employee_list', (string)$old_division_id);
        if ($old_division_id != $new_division_id) {
            $this->cache->delete('division_agency_employee_list', (string)$new_division_id);
        }

        // Invalidasi cache datatable
        $this->cache->invalidateDataTableCache('agency_employee_list', [
            'agency_id' => $agency_id
        ]);

        // Invalidate per-user count caches for all affected users
        $affected_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM (
                SELECT user_id FROM {$this->agency_table} WHERE id = %d
                UNION
                SELECT user_id FROM {$this->table} WHERE agency_id = %d AND status = 'active'
            ) AS users",
            $agency_id,
            $agency_id
        ));

        foreach ($affected_users as $user_id) {
            $this->cache->delete('employee_total_count_' . $user_id);
            $this->cache->delete('agency_stats_' . $user_id);
        }

        // Also invalidate admin stats
        $this->cache->delete('agency_stats_0');

        return true;
    }

    public function delete(int $id): bool {
        global $wpdb;

        // Dapatkan data employee sebelum dihapus
        $employee = $this->find($id);
        if (!$employee) {
            return false;
        }

        $agency_id = $employee->agency_id;
        $division_id = $employee->division_id;

        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        // Invalidasi cache
        $this->cache->delete('agency_employee', $id);
        $this->cache->delete('agency_employee_count', (string)$agency_id);
        $this->cache->delete('agency_active_employee_count', (string)$agency_id);
        // Also invalidate global employee count cache
        $this->cache->delete('agency_employee_count');
        // Invalidate dashboard stats cache
        $this->cache->delete('agency_stats_0');
        $this->cache->delete('division_agency_employee_list', (string)$division_id);
        $this->cache->invalidateDataTableCache('agency_employee_list', [
            'agency_id' => $agency_id
        ]);

        // Invalidate per-user count caches for all affected users
        $affected_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM (
                SELECT user_id FROM {$this->agency_table} WHERE id = %d
                UNION
                SELECT user_id FROM {$this->table} WHERE agency_id = %d AND status = 'active'
            ) AS users",
            $agency_id,
            $agency_id
        ));

        foreach ($affected_users as $user_id) {
            $this->cache->delete('employee_total_count_' . $user_id);
            $this->cache->delete('agency_stats_' . $user_id);
        }

        // Also invalidate admin stats
        $this->cache->delete('agency_stats_0');

        return true;
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool {
        global $wpdb;

        // Generate cache key
        $cache_key = 'agency_employee_email_' . md5($email);
        if ($excludeId) {
            $cache_key .= '_exclude_' . $excludeId;
        }
        
        // Cek cache dulu
        $cached_result = $this->cache->get($cache_key);
        if ($cached_result !== null) {
            return (bool)$cached_result;
        }

        // Buat query
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE email = %s";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        // Jalankan query
        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
        
        // Simpan ke cache dengan waktu singkat (5 menit)
        $this->cache->set($cache_key, $exists, 5 * MINUTE_IN_SECONDS);
        
        return $exists;
    }

    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir, string $status_filter = 'active'): array {
        global $wpdb;

        error_log('=== Start Debug Employee DataTable Query ===');
        error_log('Agency ID: ' . $agency_id);
        error_log('Start: ' . $start);
        error_log('Length: ' . $length);
        error_log('Search: ' . $search);
        error_log('Order Column: ' . $orderColumn);
        error_log('Order Direction: ' . $orderDir);
        error_log('Status Filter: ' . $status_filter);

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS e.*,
                         b.name as division_name,
                         u.display_name as created_by_name";
        $from = " FROM {$this->table} e";
        $join = " LEFT JOIN {$this->division_table} b ON e.division_id = b.id
                  LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID";
        $where = " WHERE e.agency_id = %d";
        $params = [$agency_id];

        // Add status filter
        if ($status_filter !== 'all') {
            $where .= " AND e.status = %s";
            $params[] = $status_filter;
        }

        error_log('Initial Query Parts:');
        error_log('Select: ' . $select);
        error_log('From: ' . $from);
        error_log('Join: ' . $join);
        error_log('Where: ' . $where);

        // Add search if provided
        if (!empty($search)) {
            $search_terms = array_filter(array_map('trim', explode(' ', $search)));
            if (!empty($search_terms)) {
                $conditions = [];
                foreach ($search_terms as $term) {
                    $term_escaped = '%' . $wpdb->esc_like($term) . '%';
                    $conditions[] = "(e.name LIKE %s OR e.position LIKE %s OR b.name LIKE %s OR e.status LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->usermeta} WHERE user_id = e.user_id AND meta_key = 'wp_capabilities' AND meta_value LIKE %s))";
                    $params = array_merge($params, [$term_escaped, $term_escaped, $term_escaped, $term_escaped, $term_escaped]);
                }
                $where .= " AND (" . implode(' AND ', $conditions) . ")";
                error_log('Search Where Clause Added: ' . $where);
                error_log('Search Parameters: ' . print_r($params, true));
            }
        }

        // Validate order column
        $validColumns = ['name', 'role', 'division_name', 'status'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'name';
        }
        error_log('Validated Order Column: ' . $orderColumn);

        // Map frontend column to actual column
        $orderColumnMap = [
            'name' => 'e.name',
            'role' => 'e.name', // Map role ordering to name since role is generated
            'division_name' => 'b.name',
            'status' => 'e.status'
        ];

        $orderColumn = $orderColumnMap[$orderColumn] ?? 'e.name';
        error_log('Mapped Order Column: ' . $orderColumn);

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        error_log('Validated Order Direction: ' . $orderDir);

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);
        error_log('Order Clause: ' . $order);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);
        error_log('Limit Clause: ' . $limit);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Log the final query with parameters
        $final_query = $wpdb->prepare($sql, $params);
        error_log('Final Complete Query: ' . $final_query);

        // Get paginated results
        $results = $wpdb->get_results($final_query);
        
        if ($results === null) {
            error_log('Query Error: ' . $wpdb->last_error);
            throw new \Exception($wpdb->last_error);
        }

        error_log('Query Results Count: ' . count($results));

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Filtered Count: ' . $filtered);

        // Get total count for agency with status filter
        $total_where = "WHERE agency_id = %d";
        $total_params = [$agency_id];
        if ($status_filter !== 'all') {
            $total_where .= " AND status = %s";
            $total_params[] = $status_filter;
        }

        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} {$total_where}",
            $total_params
        );
        error_log('Total Count Query: ' . $total_query);

        $total = $wpdb->get_var($total_query);
        error_log('Total Count: ' . $total);

        error_log('=== End Debug Employee DataTable Query ===');

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
    }
    
    /**
     * Get total employee count based on user permission
     * Filters by owner OR employee relation to agencies
     */
    public function getTotalCount(?int $agency_id = null): int {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Generate cache key with user_id for permission-based caching
        $cache_key = 'employee_total_count_' . $current_user_id;
        if ($agency_id) {
            $cache_key .= '_' . $agency_id;
        }

        // Cek cache dulu
        $cached_count = $this->cache->get($cache_key);
        if ($cached_count !== null) {
            return (int)$cached_count;
        }

        // Check if user is agency owner
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->agency_table} WHERE user_id = %d",
            $current_user_id
        ));

        // Check if user is employee (active status only)
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        // Permission based filtering
        if (current_user_can('edit_all_agencies') || current_user_can('edit_all_employees')) {
            // Admin: statistics always show active count only
            $sql = "SELECT COUNT(DISTINCT e.id) FROM {$this->table} e WHERE e.status = 'active'";
            $count = (int) $wpdb->get_var($sql);
        } elseif ($has_agency > 0 || $is_employee > 0) {
            // User owns agency OR is employee: count employees from their agencies (active only)
            $sql = $wpdb->prepare(
                "SELECT COUNT(DISTINCT e.id)
                 FROM {$this->table} e
                 INNER JOIN {$this->agency_table} a ON e.agency_id = a.id
                 LEFT JOIN {$this->table} emp ON a.id = emp.agency_id AND emp.status = 'active'
                 WHERE (a.user_id = %d OR emp.user_id = %d)
                   AND a.status = 'active'
                   AND e.status = 'active'",
                $current_user_id,
                $current_user_id
            );
            $count = (int) $wpdb->get_var($sql);
        } else {
            $count = 0;
        }

        // Simpan ke cache
        $this->cache->set($cache_key, $count, 10 * MINUTE_IN_SECONDS);

        return $count;
    }

    /**
     * Get employees by division
     */
    public function getByDivision(int $division_id): array {
        global $wpdb;
        
        // Cek cache dulu
        $cache_key = 'division_agency_employee_list_' . $division_id;
        $cached_employees = $this->cache->get($cache_key);
        
        if ($cached_employees !== null) {
            return $cached_employees;
        }

        // Query database
        $employees = $wpdb->get_results($wpdb->prepare("
            SELECT e.*
            FROM {$this->table} e
            WHERE e.division_id = %d
            ORDER BY e.name ASC
        ", $division_id));
        
        // Simpan ke cache
        if ($employees) {
            $this->cache->set($cache_key, $employees, $this->cache::getCacheExpiry(), $division_id);
        }
        
        return $employees;
    }

    // Add method to validate status
    public function isValidStatus(string $status): bool {
        return in_array($status, self::VALID_STATUSES);
    }

    // Update changeStatus method to validate status
    public function changeStatus(int $id, string $status): bool {
        if (!$this->isValidStatus($status)) {
            return false;
        }

        global $wpdb;
        
        // Dapatkan data employee sebelum update
        $employee = $this->find($id);
        if (!$employee) {
            return false;
        }
        
        $agency_id = $employee->agency_id;
        $division_id = $employee->division_id;

        $result = $wpdb->update(
            $this->table,
            [
                'status' => $status,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        // Invalidasi cache
        $this->cache->delete('agency_employee', $id);
        $this->cache->delete('agency_employee_count', (string)$agency_id);
        $this->cache->delete('agency_active_employee_count', (string)$agency_id);
        $this->cache->delete('division_agency_employee_list', (string)$division_id);
        $this->cache->invalidateDataTableCache('agency_employee_list', [
            'agency_id' => $agency_id
        ]);

        // Invalidate per-user count caches for all affected users
        $affected_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM (
                SELECT user_id FROM {$this->agency_table} WHERE id = %d
                UNION
                SELECT user_id FROM {$this->table} WHERE agency_id = %d AND status = 'active'
            ) AS users",
            $agency_id,
            $agency_id
        ));

        foreach ($affected_users as $user_id) {
            $this->cache->delete('employee_total_count_' . $user_id);
            $this->cache->delete('agency_stats_' . $user_id);
        }

        // Also invalidate admin stats
        $this->cache->delete('agency_stats_0');

        return true;
    }

    /**
     * Get employees in batches for efficient processing
     * This helps when dealing with large datasets
     */
    public function getInBatches(int $agency_id, int $batch_size = 1000): \Generator {
        global $wpdb;
        
        $offset = 0;
        
        while (true) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT e.*, 
                       b.name as division_name,
                       u.display_name as created_by_name
                FROM {$this->table} e
                LEFT JOIN {$this->division_table} b ON e.division_id = b.id
                LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID
                WHERE e.agency_id = %d
                LIMIT %d OFFSET %d
            ", $agency_id, $batch_size, $offset));
            
            if (empty($results)) {
                break;
            }
            
            yield $results;
            
            $offset += $batch_size;
            
            if (count($results) < $batch_size) {
                break;
            }
        }
    }

    /**
     * Bulk update employees
     * Useful for mass status changes or department updates
     */
    public function bulkUpdate(array $ids, array $data): int {
        global $wpdb;
        
        // Dapatkan agency_id dan division_id terkait
        $affected_agencies = [];
        $affected_divisions = [];
        
        foreach ($ids as $id) {
            $employee = $this->find($id);
            if ($employee) {
                $affected_agencies[$employee->agency_id] = true;
                $affected_divisions[$employee->division_id] = true;
                
                // Jika ada perubahan division_id, tambahkan division_id baru
                if (isset($data['division_id']) && $data['division_id'] != $employee->division_id) {
                    $affected_divisions[$data['division_id']] = true;
                }
            }
        }
        
        // Lakukan update seperti sebelumnya
        $validFields = [
            'division_id',
            'status',
            'finance',
            'operation',
            'legal',
            'purchase'
        ];
        
        // Filter only valid fields
        $updateData = array_intersect_key($data, array_flip($validFields));
        
        if (empty($updateData)) {
            return 0;
        }
        
        $sql = "UPDATE {$this->table} SET ";
        $updates = [];
        $values = [];
        
        foreach ($updateData as $field => $value) {
            $updates[] = "{$field} = %s";
            $values[] = $value;
        }
        
        $sql .= implode(', ', $updates);
        $sql .= " WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")";
        
        // Add updated_at timestamp
        $sql .= ", updated_at = %s";
        $values[] = current_time('mysql');
        
        $result = $wpdb->query($wpdb->prepare($sql, $values));
        
        // Invalidasi cache
        foreach ($ids as $id) {
            $this->cache->delete('agency_employee', $id);
        }
        
        foreach (array_keys($affected_agencies) as $agency_id) {
            $this->cache->delete('agency_employee_count', (string)$agency_id);
            $this->cache->delete('agency_active_employee_count', (string)$agency_id);
            $this->cache->invalidateDataTableCache('agency_employee_list', [
                'agency_id' => $agency_id
            ]);
        }
        
        foreach (array_keys($affected_divisions) as $division_id) {
            $this->cache->delete('division_agency_employee_list', (string)$division_id);
        }
        
        return $result;
    }

    public function invalidateEmployeeCache(int $id): void {
        // Dapatkan data employee
        $employee = $this->find($id);
        if (!$employee) {
            return;
        }

        // Invalidasi cache terkait employee ini
        $this->cache->delete('agency_employee', $id);
        $this->cache->delete('agency_employee_count', (string)$employee->agency_id);
        $this->cache->delete('agency_active_employee_count', (string)$employee->agency_id);
        $this->cache->delete('division_agency_employee_list', (string)$employee->division_id);
        $this->cache->invalidateDataTableCache('agency_employee_list', [
            'agency_id' => $employee->agency_id
        ]);
    }

    /**
     * Get comprehensive user information for admin bar integration
     *
     * This method retrieves complete user data including:
     * - Employee information
     * - Division details (code, name, type)
     * - Agency details (code, name)
     * - Jurisdiction codes (multiple, comma-separated)
     * - User email and capabilities
     *
     * @param int $user_id WordPress user ID
     * @return array|null Array of user info or null if not found
     */
    public function getUserInfo(int $user_id): ?array {
        global $wpdb;

        // Try to get from cache first
        $cache_key = 'agency_user_info';
        $cached_data = $this->cache->get($cache_key, $user_id);

        if ($cached_data !== null) {
            return $cached_data;
        }

        // Single comprehensive query to get ALL user data
        // This query JOINs employees, divisions, jurisdictions, agencies, users, and usermeta
        $user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM (
                SELECT
                    e.*,
                    MAX(d.code) AS division_code,
                    MAX(d.name) AS division_name,
                    MAX(d.type) AS division_type,
                    GROUP_CONCAT(j.jurisdiction_code SEPARATOR ',') AS jurisdiction_codes,
                    MAX(j.is_primary) AS is_primary_jurisdiction,
                    MAX(a.code) AS agency_code,
                    MAX(a.name) AS agency_name,
                    u.user_email,
                    MAX(um.meta_value) AS capabilities
                FROM
                    {$wpdb->prefix}app_agency_employees e
                INNER JOIN
                    {$wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
                INNER JOIN
                    {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id
                INNER JOIN
                    {$wpdb->prefix}app_agencies a ON e.agency_id = a.id
                INNER JOIN
                    {$wpdb->users} u ON e.user_id = u.ID
                INNER JOIN
                    {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
                WHERE
                    e.user_id = %d
                    AND e.status = 'active'
                    AND d.status = 'active'
                GROUP BY
                    e.id, e.user_id, u.user_email
            ) AS subquery
            GROUP BY
                subquery.id
            LIMIT 1",
            $user_id
        ));

        if (!$user_data || !$user_data->division_name) {
            // Cache null result for short time to prevent repeated queries
            $this->cache->set($cache_key, null, 5 * MINUTE_IN_SECONDS, $user_id);
            return null;
        }

        // Build result array
        $result = [
            'entity_name' => $user_data->agency_name,
            'entity_code' => $user_data->agency_code,
            'division_id' => $user_data->division_id,
            'division_code' => $user_data->division_code,
            'division_name' => $user_data->division_name,
            'division_type' => $user_data->division_type,
            'jurisdiction_codes' => $user_data->jurisdiction_codes,
            'is_primary_jurisdiction' => $user_data->is_primary_jurisdiction,
            'position' => $user_data->position,
            'user_email' => $user_data->user_email,
            'capabilities' => $user_data->capabilities,
            'relation_type' => 'agency_employee',
            'icon' => '🏛️'
        ];

        // Add role names dynamically from capabilities (Review-01)
        // Use AdminBarModel for generic capability parsing
        $admin_bar_model = new \WPAppCore\Models\AdminBarModel();

        $result['role_names'] = $admin_bar_model->getRoleNamesFromCapabilities(
            $user_data->capabilities,
            call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
            ['WP_Agency_Role_Manager', 'getRoleName']
        );

        // Add permission names list (Review-04 & Review-05)
        // IMPORTANT: Use WP_User->allcaps to get ACTUAL permissions (including inherited from roles)
        // Not from wp_usermeta which only contains role assignments!
        $permission_model = new \WPAgency\Models\Settings\PermissionModel();
        $result['permission_names'] = $admin_bar_model->getPermissionNamesFromUserId(
            $user_id,
            call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
            $permission_model->getAllCapabilities()
        );

        // Cache the result for 5 minutes
        $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS, $user_id);

        return $result;
    }

}

