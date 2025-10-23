<?php
/**
 * Agency Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Agency/AgencyModel.php
 *
 * Description: Model untuk mengelola data agency di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 2.1.2 - 2025-01-22 (Task-2067 Runtime Flow)
 * - Deleted createDemoData() method (production code pollution)
 * - Demo generation now uses standard create() method
 * - Ensures hooks are properly fired during demo generation
 *
 * 2.1.1 - 2025-01-22 (Task-2065 Follow-up)
 * - Fixed: Added reg_type field to create() method insert_data
 * - Now properly saves reg_type value from controller ('self', 'by_admin', 'generate')
 * - Prevents reg_type always defaulting to 'self' in database
 *
 * 2.1.0 - 2025-01-22
 * - Task-2066: Added wp_agency_agency_created hook for auto entity creation
 * - Task-2066: Added wp_agency_agency_before_delete and wp_agency_agency_deleted hooks
 * - Implemented soft delete support (status='inactive' vs hard delete)
 * - Hook fires after successful agency creation
 * - Enables automatic division pusat creation via AutoEntityCreator
 * - Delete hooks enable cascade cleanup and external integrations
 *
 * 2.0.0 - 2024-12-03 15:00:00
 * - Refactor create/update untuk return complete data
 * - Added proper error handling dan validasi
 * - Improved cache integration
 * - Added method untuk DataTables server-side
 */

 namespace WPAgency\Models\Agency;

 use WPAgency\Cache\AgencyCacheManager;
 
 class AgencyModel {
     private $table;
     private $division_table;
     private $employee_table;
     private $cache;
     static $used_codes = [];


     public function __construct() {
         global $wpdb;
         $this->table = $wpdb->prefix . 'app_agencies';
         $this->division_table = $wpdb->prefix . 'app_agency_divisions';
         $this->employee_table = $wpdb->prefix . 'app_agency_employees';
         $this->cache = new AgencyCacheManager();
     }

    public function find($id): ?object {
        global $wpdb;
        $id = (int) $id;

        // Check cache first
        $cached_result = $this->cache->get('agency', $id);
        if ($cached_result !== null) {
            return $cached_result;
        }

        try {
            $sql = $wpdb->prepare("
                SELECT 
                    c.*,
                    COUNT(DISTINCT b.id) as division_count,
                    COUNT(DISTINCT e.id) as employee_count,
                    u.display_name as owner_name,
                    creator.display_name as created_by_name,
                    bp.name as pusat_name,
                    bp.code as pusat_code,
                    bp.address as pusat_address,
                    bp.postal_code as pusat_postal_code,
                    bp.latitude as latitude,
                    bp.longitude as longitude,
                    wp.name as province_name,
                    wr.name as regency_name
                FROM {$this->table} c
                LEFT JOIN {$this->division_table} b ON c.id = b.agency_id
                LEFT JOIN {$this->employee_table} e ON c.id = e.agency_id
                LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                LEFT JOIN {$wpdb->users} creator ON c.created_by = creator.ID
                LEFT JOIN {$this->division_table} bp ON (c.id = bp.agency_id AND bp.type = 'pusat')
                LEFT JOIN wp_wi_provinces wp ON c.provinsi_code = wp.code
                LEFT JOIN wp_wi_regencies wr ON c.regency_code = wr.code
                WHERE c.id = %d
                GROUP BY c.id
            ", $id);

            $result = $wpdb->get_row($sql);

            if ($wpdb->last_error) {
                throw new \Exception("Database error: " . $wpdb->last_error);
            }

            if ($result) {
                // Cache the result for 2 minutes
                $this->cache->set('agency', $result, 120, $id);
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Error in AgencyModel::find(): " . $e->getMessage());
            throw $e;
        }
    }

    public function getAgency(?int $id = null): ?object {
        // Check cache first
        if ($id !== null) {
            $cached_result = $this->cache->get('agency', $id);
            if ($cached_result !== null) {
                return $cached_result;
            }
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        // Base query structure
        $select = "SELECT p.*, 
                   COUNT(r.id) as division_count,
                   u.display_name as owner_name";
        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->division_table} r ON p.id = r.agency_id
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";

        // Handle different cases
        if (current_user_can('edit_all_agencies')) {
            $where = $id ? $wpdb->prepare(" WHERE p.id = %d", $id) : "";
        } else {
            $where = $wpdb->prepare(" WHERE p.user_id = %d", $current_user_id);
            if ($id) {
                $where .= $wpdb->prepare(" AND p.id = %d", $id);
            }
        }

        $group = " GROUP BY p.id";
        $sql = $select . $from . $join . $where . $group;

        $result = $wpdb->get_row($sql);

        if ($result && $id !== null) {
            // Cache for 2 minutes
            $this->cache->set('agency', $result, 120, $id);
        }

        return $result;
    }

    /**
     * Generate unique agency code
     * Format: TTTTRRXxRRXx
     * TTTT = 4 digit timestamp
     * Xx = 1 uppercase + 1 lowercase letters
     * RR = 2 digit random number
     * Xx = 1 uppercase + 1 lowercase letters
     */
    public static function generateAgencyCode(): string {
        do {
            // Get 4 digits from timestamp
            $timestamp = substr(time(), -4);
                        
            // Generate first Xx (1 upper + 1 lower)
            $upperLetter1 = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
            $lowerLetter1 = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 1);
            
            // Generate second RR (2 random digits)
            $random2 = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            
            // Generate second Xx (1 upper + 1 lower)
            $upperLetter2 = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
            $lowerLetter2 = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 1);
            
            $code = sprintf('%s%s%s%s%s%s', 
                $timestamp,
                $upperLetter1,
                $lowerLetter1,
                $random2,
                $upperLetter2,
                $lowerLetter2
            );
            
            $exists = in_array($code, self::$used_codes) || self::codeExists($code);
        } while ($exists);

        self::$used_codes[] = $code;
        return $code;
    }

    /**
     * Check if code exists in database
     */
    public static function codeExists(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}app_agencies 
                WHERE code = %s
            ) as result",
            $code
        ));
    }

    public function create(array $data): ?int {
        global $wpdb;
        
        // Debug incoming data
        error_log('AgencyModel::create() - Input data: ' . print_r($data, true));
        
        $data['code'] = $this->generateAgencyCode();
        
        // Prepare insert data
        $insert_data = [
            'code' => $data['code'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'],
            'provinsi_code' => $data['provinsi_code'] ?? null,
            'regency_code' => $data['regency_code'] ?? null,
            'reg_type' => $data['reg_type'] ?? 'self',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Debug prepared data
        error_log('AgencyModel::create() - Prepared data for insert: ' . print_r($insert_data, true));

        // Prepare format array for $wpdb->insert
        $format = [
            '%s',  // code
            '%s',  // name
            '%s',  // status
            '%d',  // user_id
            '%s',  // provinsi_code (nullable)
            '%s',  // regency_code (nullable)
            '%s',  // reg_type
            '%d',  // created_by
            '%s',  // created_at
            '%s'   // updated_at
        ];

        // Attempt the insert
        $result = $wpdb->insert(
            $this->table,
            $insert_data,
            $format
        );

        // Debug insert result
        if ($result === false) {
            error_log('AgencyModel::create() - Insert failed. Last error: ' . $wpdb->last_error);
            return null;
        }

        $new_id = (int) $wpdb->insert_id;
        error_log('AgencyModel::create() - Insert successful. New ID: ' . $new_id);

        // Task-2066: Fire hook for auto-create division pusat
        if ($new_id) {
            do_action('wp_agency_agency_created', $new_id, $insert_data);
        }

        $this->cache->invalidateAgencyCache($new_id);

        // Invalidate unrestricted count cache
        $this->cache->delete('agency_total_count_unrestricted');

        // Invalidate dashboard stats cache
        $this->cache->delete('agency_stats_0');

        return $new_id;
    }



    public function update(int $id, array $data): bool {
        global $wpdb;

        $updateData = array_merge($data, ['updated_at' => current_time('mysql')]);

        // Remove null values
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });

        $formats = [];
        foreach ($updateData as $key => $value) {
            switch ($key) {
                case 'provinsi_code':
                case 'regency_code':
                case 'user_id':
                    $formats[] = '%s';
                    break;
                default:
                    $formats[] = '%s';
            }
        }

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $formats,
            ['%d']
        );

        if ($result === false) {
            error_log('Update failed. Last error: ' . $wpdb->last_error);
            error_log('Update data: ' . print_r($updateData, true));
            return false;
        }

        return true;
    }

    // Di AgencyModel.php
    // VERSION 1: getTotalCount dengan query terpisah + cache
    public function getTotalCount(): int {
        global $wpdb;
        
        error_log('--- Debug AgencyModel getTotalCount ---');
        error_log('Checking cache first...');
        
        // Cek cache
        $cached_total = $this->cache->get('agency_total_count', get_current_user_id());
        if ($cached_total !== null) {
            error_log('Found cached total: ' . $cached_total);
            return (int) $cached_total;
        }

        error_log('No cache found, getting fresh count...');
        error_log('User ID: ' . get_current_user_id());
        error_log('Can view_agency_list: ' . (current_user_can('view_agency_list') ? 'yes' : 'no'));
        error_log('Can view_own_agency: ' . (current_user_can('view_own_agency') ? 'yes' : 'no'));
        error_log('Can edit_all_agencies: ' . (current_user_can('edit_all_agencies') ? 'yes' : 'no'));

        $current_user_id = get_current_user_id();

        // Check if user is agency owner
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has agency as owner: ' . ($has_agency > 0 ? 'yes' : 'no'));

        // Check if user is employee (active status only)
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->employee_table} WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));
        error_log('User is active employee: ' . ($is_employee > 0 ? 'yes' : 'no'));

        // Permission based filtering
        if (current_user_can('edit_all_agencies')) {
            // Admin: statistics always show active count only
            error_log('Admin - show active only for statistics');
            $sql = "SELECT COUNT(DISTINCT p.id) FROM {$this->table} p WHERE p.status = 'active'";
        } elseif ($has_agency > 0 || $is_employee > 0) {
            // User punya agency atau employee: filter by owner OR employee (active only)
            error_log('User has agency or is employee - filtering by owner OR employee (active only)');
            $sql = $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.id)
                 FROM {$this->table} p
                 LEFT JOIN {$this->employee_table} e ON p.id = e.agency_id AND e.status = 'active'
                 WHERE (p.user_id = %d OR e.user_id = %d)
                   AND p.status = 'active'",
                $current_user_id,
                $current_user_id
            );
        } else {
            // User tidak punya akses
            error_log('User has no access to agencies');
            $sql = "SELECT 0";
        }

        error_log('Final Query: ' . $sql);

        $total = (int) $wpdb->get_var($sql);
        
        // Set cache
        $this->cache->set('agency_total_count', $total, 120, get_current_user_id());
        error_log('Set new cache value: ' . $total);
        
        error_log('Total count result: ' . $total);
        error_log('--- End Debug ---');

        return $total;
    }

    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir, string $status_filter = 'active'): array {
        global $wpdb;

        error_log("=== getDataTableData start ===");
        error_log("Query params: start=$start, length=$length, search=$search, status_filter=$status_filter");

        $current_user_id = get_current_user_id();

        // Debug capabilities
        error_log('--- Debug User Capabilities ---');
        error_log('User ID: ' . $current_user_id);
        error_log('Can view_agency_list: ' . (current_user_can('view_agency_list') ? 'yes' : 'no'));
        error_log('Can view_own_agency: ' . (current_user_can('view_own_agency') ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS p.*, COUNT(r.id) as division_count, u.display_name as owner_name";

        $from = " FROM {$this->table} p";
        $join = " LEFT JOIN {$this->division_table} r ON p.id = r.agency_id";
        $join .= " LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID";
        $where = " WHERE 1=1";

        // Add status filter (soft delete aware)
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND p.status = %s", $status_filter);
            error_log('Status filter applied: ' . $status_filter);
        }

        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Cek relasi user dengan agency (as owner)
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has agency as owner: ' . ($has_agency > 0 ? 'yes' : 'no'));

        // Cek status employee (active only)
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->employee_table} WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));
        error_log('User is active employee: ' . ($is_employee > 0 ? 'yes' : 'no'));

        // Permission based filtering
        if (current_user_can('edit_all_agencies')) {
            error_log('User can edit all agencies - no additional restrictions');
        }
        else if (($has_agency > 0 || $is_employee > 0) && current_user_can('view_own_agency')) {
            // User punya agency ATAU employee: filter by owner OR employee
            // Tambah LEFT JOIN ke employee table untuk filter
            $join .= " LEFT JOIN {$this->employee_table} e ON p.id = e.agency_id AND e.user_id = {$current_user_id} AND e.status = 'active'";
            $where .= $wpdb->prepare(" AND (p.user_id = %d OR e.user_id IS NOT NULL)", $current_user_id);
            error_log('Added owner OR employee restriction');
        }
        else {
            $where .= " AND 1=0";
            error_log('User has no access - restricting all results');
        }

        // Add search condition if present
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.name LIKE %s OR p.code LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
            error_log('Added search condition: ' . $where);
        }

        // Complete query parts
        $group = " GROUP BY p.id";
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        $sql = $select . $from . $join . $where . $group . $order . $limit;
        error_log('Final Query: ' . $sql);

        // Execute query
        $results = $wpdb->get_results($sql);

        // Get filtered count from SQL_CALC_FOUND_ROWS
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");
        
        // Calculate total count using same WHERE clause but without search
        $total_sql = "SELECT COUNT(DISTINCT p.id)" . $from . $join . $where;
        // Remove search condition from WHERE for total count if it exists
        if (!empty($search)) {
            $total_sql = str_replace(
                $wpdb->prepare(
                    " AND (p.name LIKE %s OR p.code LIKE %s)",
                    '%' . $wpdb->esc_like($search) . '%',
                    '%' . $wpdb->esc_like($search) . '%'
                ),
                '',
                $total_sql
            );
        }
        $total = (int) $wpdb->get_var($total_sql);

        error_log("Found rows (filtered): " . $filtered);
        error_log("Total count: " . $total);
        error_log("Results count: " . count($results));
        error_log("=== getDataTableData end ===");

        return [
            'data' => $results,
            'total' => $total,
            'filtered' => (int) $filtered
        ];
    }

    public function delete(int $id): bool {
        global $wpdb;

        // 1. Get agency data before deletion
        $agency = $this->find($id);
        if (!$agency) {
            return false; // Agency not found
        }

        // 2. Prepare agency data array for hooks
        $agency_data = [
            'id' => $agency->id,
            'code' => $agency->code,
            'name' => $agency->name,
            'status' => $agency->status,
            'provinsi_code' => $agency->provinsi_code ?? null,
            'regency_code' => $agency->regency_code ?? null,
            'user_id' => $agency->user_id ?? null,
            'reg_type' => $agency->reg_type ?? 'self',
            'created_by' => $agency->created_by ?? null,
            'created_at' => $agency->created_at ?? null,
            'updated_at' => $agency->updated_at ?? null
        ];

        // 3. Fire before delete hook (for validation/prevention)
        do_action('wp_agency_agency_before_delete', $id, $agency_data);

        // 4. Check if hard delete is enabled (same setting as Division/Employee for consistency)
        $settings = get_option('wp_agency_general_options', []);
        $is_hard_delete = isset($settings['enable_hard_delete_branch']) &&
                         $settings['enable_hard_delete_branch'] === true;

        // 5. Perform delete (soft or hard)
        if ($is_hard_delete) {
            // Hard delete - actual DELETE from database
            error_log("[AgencyModel] Hard deleting agency {$id} ({$agency_data['name']})");

            $result = $wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete - set status to 'inactive'
            error_log("[AgencyModel] Soft deleting agency {$id} ({$agency_data['name']})");

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

        // 6. If successful, fire after delete hook and invalidate cache
        if ($result !== false) {
            // Fire after delete hook (for cascade cleanup)
            do_action('wp_agency_agency_deleted', $id, $agency_data, $is_hard_delete);

            // Clear all related cache
            $this->cache->invalidateAgencyCache($id);

            // If agency had a user_id, clear that user's cache too
            if (!empty($agency->user_id)) {
                $this->cache->delete('user_agencies', $agency->user_id);
            }

            // Invalidate unrestricted count cache
            $this->cache->delete('agency_total_count_unrestricted');

            // Invalidate dashboard stats cache
            $this->cache->delete('agency_stats_0');

            error_log("[AgencyModel] Agency {$id} deleted successfully (hard_delete: " .
                     ($is_hard_delete ? 'YES' : 'NO') . ")");

            return true;
        }

        return false;
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool {
        // Generate unique cache key based on parameters
        $cache_key = 'code_exists_' . md5($code . ($excludeId ?? ''));
        
        // Check cache first
        $cached_result = $this->cache->get('code_exists', $cache_key);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }

        global $wpdb;
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s";
        $params = [$code];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));

        // Cache for 5 minutes since this rarely changes
        $this->cache->set('code_exists', $exists, 300, $cache_key);

        return $exists;
    }
    /**
     * Get total division count for a specific agency
     * 
     * Used for:
     * 1. Agency deletion validation - prevent deletion if agency has divisions
     * 2. Display division count in agency detail panel
     * 
     * Note: This method does NOT handle permission filtering as it's used for 
     * internal validation and UI display where the agency ID is already validated.
     * For permission-based division counting, use getTotalDivisionsByPermission() instead.
     *
     * @param int $id Agency ID
     * @return int Total number of divisions owned by the agency
     */
    public function getDivisionCount(int $id): int {
        // Check cache first
        $cached_count = $this->cache->get('division_count', $id);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        global $wpdb;
        // Only count active divisions (soft delete aware)
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->division_table}
            WHERE agency_id = %d AND status = 'active'
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('division_count', $count, 120, $id);

        return $count;
    }

    /**
     * Get total employee count for agency (only active employees)
     *
     * @param int $id Agency ID
     * @return int Total number of active employees in the agency
     */
    public function getEmployeeCount(int $id): int {
        // Check cache first
        $cached_count = $this->cache->get('agency_active_employee_count', $id);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        global $wpdb;
        // Only count active employees (soft delete aware)
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->employee_table}
            WHERE agency_id = %d AND status = 'active'
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('agency_active_employee_count', $count, 120, $id);

        return $count;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool {
        // Generate unique cache key based on parameters
        $cache_key = 'name_exists_' . md5($name . ($excludeId ?? ''));
        
        // Check cache first
        $cached_result = $this->cache->get('name_exists', $cache_key);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }

        global $wpdb;
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE name = %s";
        $params = [$name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));

        // Cache for 5 minutes since this rarely changes
        $this->cache->set('name_exists', $exists, 300, $cache_key);

        return $exists;
    }



    /**
     * Get all active agency IDs with cache implementation
     *
     * @return array Array of agency IDs
     */
    public function getAllAgencyIds(): array {
        try {
            // Try to get from cache first using the cache manager
            $cached_ids = $this->cache->get('agency_ids', 'active');
            
            if ($cached_ids !== null) {
                error_log('Cache hit: getAllAgencyIds');
                return $cached_ids;
            }
            
            error_log('Cache miss: getAllAgencyIds - fetching from database');
            
            global $wpdb;
            
            // Get fresh data from database
            $results = $wpdb->get_col("
                SELECT id 
                FROM {$this->table}
                WHERE status = 'active'
                ORDER BY id ASC
            ");
            
            if ($wpdb->last_error) {
                throw new \Exception('Database error: ' . $wpdb->last_error);
            }
            
            // Convert all IDs to integers
            $agency_ids = array_map('intval', $results);
            
            // Cache the results using cache manager
            // Using 2 minutes cache time to match other cache durations in the system
            $this->cache->set('agency_ids', $agency_ids, 120, 'active');
            
            return $agency_ids;
            
        } catch (\Exception $e) {
            error_log('Error in getAllAgencyIds: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total agency count without permission restrictions
     * Used for dashboard statistics that should show global totals
     *
     * @return int Total number of agencies in database
     */
    public function getTotalCountUnrestricted(): int {
        global $wpdb;

        // Check cache first
        $cached_total = $this->cache->get('agency_total_count_unrestricted');
        if ($cached_total !== null) {
            return (int) $cached_total;
        }

        // Simple count query without any restrictions
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        // Cache for 5 minutes
        $this->cache->set('agency_total_count_unrestricted', $total, 300);

        return $total;
    }

    // Di AgencyModel.php
    public function getProvinsiOptions() {
        return apply_filters('wilayah_indonesia_get_province_options', [
            '' => __('Pilih Provinsi', 'wp-agency')
        ], true);
    }

    public function getRegencyOptions($provinsi_id) {
        return apply_filters(
            'wilayah_indonesia_get_regency_options',
            [],
            $provinsi_id,
            true
        );
    }

    private function getFormatArray(array $data): array {
        $formats = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

 }
