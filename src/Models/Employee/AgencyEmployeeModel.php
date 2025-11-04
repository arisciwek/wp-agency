<?php
/**
 * Agency Employee Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Employee
 * @version     1.4.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Employee/AgencyEmployeeModel.php
 *
 * Description: Model untuk mengelola data karyawan agency di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Pure CRUD model - DataTable operations moved to EmployeeDataTableModel.
 *
 * Changelog:
 * 1.4.1 - 2025-11-01 (TODO-3098 Entity Static IDs)
 * - Added 'wp_agency_employee_before_insert' filter hook in create() method
 * - Allows modification of insert data before database insertion
 * - Use cases: demo data (static IDs), migration, data sync, testing
 * - Added dynamic format array handling for 'id' field injection
 *
 * 1.4.0 - 2025-11-01 (TODO-3096 Follow-up: Complete Optimization)
 * - COMPLETE: getTotalCount() now FULLY reuses EmployeeDataTableModel
 * - Uses get_total_count() for agency-specific count
 * - Uses get_total_count_global() for global count
 * - Eliminated ALL manual counting logic (60+ additional lines)
 * - Total: 185+ lines eliminated (125 DataTable + 60 counting)
 * - 100% DRY principle compliance ✅
 *
 * 1.3.0 - 2025-11-01 (TODO-3096)
 * - OPTIMIZATION: getTotalCount() now reuses EmployeeDataTableModel::get_total_count()
 * - DEPRECATED: getDataTableData() method (moved to EmployeeDataTableModel)
 * - Eliminated 125+ lines of duplicated DataTable logic
 * - Dashboard statistics use same logic as DataTable (DRY principle)
 * - Single source of truth for counting queries
 *
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

        // Prepare insert data
        $insertData = [
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
        ];

        /**
         * Filter employee insert data before database insertion
         *
         * Allows modification of insert data before $wpdb->insert() call.
         *
         * Use cases:
         * - Demo data: Force static IDs for predictable test data
         * - Migration: Import employees with preserved IDs from external system
         * - Testing: Unit tests with predictable employee IDs
         * - Data sync: Synchronize with external systems while preserving IDs
         *
         * @param array $insertData Prepared data ready for $wpdb->insert
         * @param array $data Original input data from controller
         * @return array Modified insert data (can include 'id' key for static ID)
         *
         * @since 1.4.1
         */
        $insertData = apply_filters('wp_agency_employee_before_insert', $insertData, $data);

        // If 'id' field was injected via filter, reorder to put it first
        if (isset($insertData['id'])) {
            $static_id = $insertData['id'];
            unset($insertData['id']);
            $insertData = array_merge(['id' => $static_id], $insertData);
        }

        // Prepare format array (must match key order)
        $format = [];
        if (isset($insertData['id'])) {
            $format[] = '%d';  // id
        }
        $format = array_merge($format, [
            '%d', // agency_id
            '%d', // division_id
            '%d', // user_id
            '%s', // name
            '%s', // position
            '%s', // keterangan
            '%s', // email
            '%s', // phone
            '%d', // created_by
            '%s', // created_at
            '%s', // updated_at
            '%s'  // status
        ]);

        $result = $wpdb->insert(
            $this->table,
            $insertData,
            $format
        );

        if ($result === false) {
            return null;
        }

        $employee_id = (int) $wpdb->insert_id;

        // Task-2070: Fire hook after successful employee creation
        // Allows plugins/handlers to respond to new employee (email, notification, audit log, etc)
        do_action('wp_agency_employee_created', $employee_id, $data);

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

    /**
     * Delete employee with HOOK support
     *
     * Supports soft/hard delete based on settings
     * - Fires wp_agency_employee_before_delete HOOK
     * - Performs soft delete (status='inactive') or hard delete (actual DELETE)
     * - Fires wp_agency_employee_deleted HOOK
     *
     * @param int $id Employee ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        global $wpdb;

        // 1. Get employee data BEFORE deletion for HOOK
        $employee = $this->find($id);
        if (!$employee) {
            return false;
        }

        // 2. Convert to array for HOOK
        $employee_data = [
            'id' => $employee->id,
            'agency_id' => $employee->agency_id,
            'division_id' => $employee->division_id,
            'user_id' => $employee->user_id,
            'name' => $employee->name,
            'position' => $employee->position,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'finance' => $employee->finance ?? null,
            'operation' => $employee->operation ?? null,
            'legal' => $employee->legal ?? null,
            'purchase' => $employee->purchase ?? null,
            'keterangan' => $employee->keterangan ?? null,
            'status' => $employee->status ?? 'active',
            'created_by' => $employee->created_by ?? null,
            'created_at' => $employee->created_at ?? null,
            'updated_at' => $employee->updated_at ?? null
        ];

        $agency_id = $employee->agency_id;
        $division_id = $employee->division_id;

        // 3. Fire before delete HOOK (for validation, logging, pre-deletion notifications)
        do_action('wp_agency_employee_before_delete', $id, $employee_data);

        // 4. Check hard delete setting (same setting as Division/Agency for consistency)
        $settings = get_option('wp_agency_general_options', []);
        $is_hard_delete = isset($settings['enable_hard_delete_branch']) &&
                         $settings['enable_hard_delete_branch'] === true;

        // 5. Perform delete (soft or hard)
        if ($is_hard_delete) {
            // Hard delete - actual DELETE from database
            $result = $wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete - status = 'inactive'
            $result = $wpdb->update(
                $this->table,
                [
                    'status' => 'inactive',
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $id],
                ['%s', '%s'],
                ['%d']
            );
        }

        // 6. Fire after delete HOOK and handle cleanup
        if ($result !== false) {
            // Fire HOOK - passing $is_hard_delete as third parameter
            do_action('wp_agency_employee_deleted', $id, $employee_data, $is_hard_delete);

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

        return false;
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

    /**
     * @deprecated Use EmployeeDataTableModel::get_datatable_data() instead
     *
     * This method has been moved to EmployeeDataTableModel to follow wp-app-core pattern.
     * Kept for backward compatibility. Will be removed in future version.
     *
     * @param int $agency_id Agency ID
     * @param int $start Offset
     * @param int $length Limit
     * @param string $search Search term
     * @param string $orderColumn Column to order by
     * @param string $orderDir Order direction
     * @param string $status_filter Status filter
     * @return array Empty result to prevent errors
     */
    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir, string $status_filter = 'active'): array {
        error_log('[AgencyEmployeeModel] DEPRECATED: getDataTableData() called. Use EmployeeDataTableModel instead.');

        // Return empty result to prevent errors
        return [
            'data' => [],
            'total' => 0,
            'filtered' => 0
        ];
    }
    
    
    /**
     * Get total employee count accessible by current user
     *
     * Reuses EmployeeDataTableModel for consistency.
     * No query duplication - uses same permission filtering as DataTable.
     *
     * Benefits:
     * - Single source of truth for permission logic
     * - No code duplication (DRY principle)
     * - Stats always match DataTable results
     *
     * @param int|null $agency_id Optional agency ID filter
     * @return int Total count
     */
    public function getTotalCount(?int $agency_id = null): int {
        $current_user_id = get_current_user_id();

        // Generate cache key with user_id for permission-based caching
        $cache_key = 'employee_total_count_' . $current_user_id;
        if ($agency_id) {
            $cache_key .= '_' . $agency_id;
        }

        // Check cache first
        $cached_count = $this->cache->get($cache_key);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        // Reuse EmployeeDataTableModel logic (no duplication!)
        $datatable_model = new \WPAgency\Models\Employee\EmployeeDataTableModel();

        if ($agency_id) {
            // Agency-specific count
            $count = $datatable_model->get_total_count($agency_id, 'active');
        } else {
            // Global count across all accessible agencies
            $count = $datatable_model->get_total_count_global('active');
        }

        // Cache for 10 minutes
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
                    GROUP_CONCAT(wr.code SEPARATOR ',') AS jurisdiction_codes,
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
                    {$wpdb->prefix}wi_regencies wr ON j.jurisdiction_regency_id = wr.id
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

