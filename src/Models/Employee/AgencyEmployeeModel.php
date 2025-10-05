<?php
/**
 * Agency Employee Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Employee
 * @version     1.0.0
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
        $this->division_table = $wpdb->prefix . 'app_divisions';
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
                'finance' => $data['finance'],
                'operation' => $data['operation'],
                'legal' => $data['legal'],
                'purchase' => $data['purchase'],
                'keterangan' => $data['keterangan'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'created_by' => $data['created_by'] ?? get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'status' => $data['status'] ?? 'active'
            ],
            [
                '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s'
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

    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        error_log('=== Start Debug Employee DataTable Query ===');
        error_log('Agency ID: ' . $agency_id);
        error_log('Start: ' . $start);
        error_log('Length: ' . $length);
        error_log('Search: ' . $search);
        error_log('Order Column: ' . $orderColumn);
        error_log('Order Direction: ' . $orderDir);

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS e.*, 
                         b.name as division_name,
                         u.display_name as created_by_name";
        $from = " FROM {$this->table} e";
        $join = " LEFT JOIN {$this->division_table} b ON e.division_id = b.id
                  LEFT JOIN {$wpdb->users} u ON e.created_by = u.ID";
        $where = " WHERE e.agency_id = %d";
        $params = [$agency_id];

        error_log('Initial Query Parts:');
        error_log('Select: ' . $select);
        error_log('From: ' . $from);
        error_log('Join: ' . $join);
        error_log('Where: ' . $where);

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND (e.name LIKE %s OR e.position LIKE %s OR b.name LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
            error_log('Search Where Clause Added: ' . $where);
            error_log('Search Parameters: ' . print_r($params, true));
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

        // Get total count for agency
        $total_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE agency_id = %d",
            $agency_id
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
     * Get total employee count for a specific agency
     */
    public function getTotalCount(?int $agency_id = null): int {
        global $wpdb;

        // Generate cache key
        $cache_key = 'agency_employee_count';
        if ($agency_id) {
            $cache_key .= '_' . $agency_id;
        }
        
        // Cek cache dulu
        $cached_count = $this->cache->get($cache_key);
        if ($cached_count !== null) {
            return (int)$cached_count;
        }

        // Query database
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if ($agency_id) {
            $sql .= " WHERE agency_id = %d";
            $params[] = $agency_id;
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $count = (int) $wpdb->get_var($sql);
        
        // Simpan ke cache
        $this->cache->set($cache_key, $count, 30 * MINUTE_IN_SECONDS);
        
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

}

