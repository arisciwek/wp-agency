<?php
/**
 * Agency Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Agency/AgencyModel.php
 *
 * Description: Model untuk mengelola data agency di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Pure CRUD model - DataTable operations moved to AgencyDataTableModel.
 *
 * Changelog:
 * 1.0.12 - 2025-11-04 (FIX: Use province_id/regency_id instead of codes)
 * - CRITICAL FIX: Changed from provinsi_code/regency_code to province_id/regency_id
 * - Updated create() method: insert_data and format array
 * - Updated find() method: JOIN conditions for provinces/regencies
 * - Updated update() method: format switch cases for province_id/regency_id
 * - Updated delete() method: agency_data array for hooks
 * - Matches current AgencysDB schema (ID-based FKs, not code-based)
 * - Fixes "Unknown column 'provinsi_code'" error in demo generation
 *
 * 1.0.11 - 2025-11-01 (TODO-3098 Entity Static IDs)
 * - Added 'wp_agency_before_insert' filter hook in create() method
 * - Allows modification of insert data before database insertion
 * - Use cases: demo data (static IDs), migration, data sync, testing
 * - Added dynamic format array handling for 'id' field injection
 *
 * 1.0.10 - 2025-11-01 (TODO-3094 Follow-up: Optimization)
 * - OPTIMIZATION: getTotalCount() now reuses AgencyDataTableModel::get_total_count()
 * - Eliminated 60+ lines of duplicated permission filtering logic
 * - Dashboard statistics use same logic as DataTable (DRY principle)
 * - Single source of truth for counting queries
 * - Stats always match DataTable results (consistency guaranteed)
 *
 * 1.0.9 - 2025-11-01 (TODO-3094)
 * - MAJOR: Deprecated getDataTableData() method
 * - DataTable operations moved to AgencyDataTableModel (wp-app-core pattern)
 * - AgencyModel now pure CRUD only (find, create, update, delete)
 * - Backward compatibility maintained via deprecation stub
 * - Fixes Task-2176: customer_admin can see Disnaker list
 *
 * 1.0.8 - 2025-10-31 (TODO-3094)
 * - Added: wpapp_datatable_app_agencies_where filter hook in getDataTableData()
 * - Changed: WHERE conditions now built as array for better filtering support
 * - Fixed: Allow view_agency_list capability users to be filtered by hooks
 * - Fixed: Total count calculation now properly excludes search conditions
 * - Enables cross-plugin integration with wp-customer for customer_admin filtering
 *
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
                LEFT JOIN {$wpdb->prefix}wi_provinces wp ON c.province_id = wp.id
                LEFT JOIN {$wpdb->prefix}wi_regencies wr ON c.regency_id = wr.id
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
            'province_id' => $data['province_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'reg_type' => $data['reg_type'] ?? 'self',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Debug prepared data
        error_log('AgencyModel::create() - Prepared data for insert: ' . print_r($insert_data, true));

        /**
         * Filter agency insert data before database insertion
         *
         * Allows modification of insert data before $wpdb->insert() call.
         *
         * Use cases:
         * - Demo data: Force static IDs for predictable test data
         * - Migration: Import agencies with preserved IDs from external system
         * - Testing: Unit tests with predictable agency IDs
         * - Data sync: Synchronize with external systems while preserving IDs
         *
         * @param array $insert_data Prepared data ready for $wpdb->insert
         * @param array $data Original input data from controller
         * @return array Modified insert data (can include 'id' key for static ID)
         *
         * @since 1.0.11
         */
        $insert_data = apply_filters('wp_agency_before_insert', $insert_data, $data);

        // If 'id' field was injected via filter, reorder to put it first
        if (isset($insert_data['id'])) {
            $static_id = $insert_data['id'];
            unset($insert_data['id']);
            $insert_data = array_merge(['id' => $static_id], $insert_data);
        }

        // Prepare format array for $wpdb->insert (must match key order)
        $format = [];
        if (isset($insert_data['id'])) {
            $format[] = '%d';  // id
        }
        $format = array_merge($format, [
            '%s',  // code
            '%s',  // name
            '%s',  // status
            '%d',  // user_id
            '%d',  // province_id (nullable)
            '%d',  // regency_id (nullable)
            '%s',  // reg_type
            '%d',  // created_by
            '%s',  // created_at
            '%s'   // updated_at
        ]);

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
                case 'province_id':
                case 'regency_id':
                case 'user_id':
                    $formats[] = '%d';
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

    /**
     * Get total count of agencies accessible by current user
     *
     * Reuses AgencyDataTableModel::get_total_count() for consistency.
     * No query duplication - uses same permission filtering as DataTable.
     *
     * Benefits:
     * - Single source of truth for permission logic
     * - No code duplication (DRY principle)
     * - Stats always match DataTable results
     *
     * @return int Total count
     */
    public function getTotalCount(): int {
        // Check cache first
        $cached_total = $this->cache->get('agency_total_count', get_current_user_id());
        if ($cached_total !== null) {
            return (int) $cached_total;
        }

        // Reuse AgencyDataTableModel logic (no duplication!)
        $datatable_model = new \WPAgency\Models\Agency\AgencyDataTableModel();
        $total = $datatable_model->get_total_count('active');

        // Cache for 2 minutes
        $this->cache->set('agency_total_count', $total, 120, get_current_user_id());

        return $total;
    }

    /**
     * @deprecated Use AgencyDataTableModel::get_datatable_data() instead
     *
     * This method has been moved to AgencyDataTableModel to follow wp-app-core pattern.
     * Kept for backward compatibility. Will be removed in future version.
     */
    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir, string $status_filter = 'active'): array {
        error_log('[AgencyModel] DEPRECATED: getDataTableData() called. Use AgencyDataTableModel instead.');

        // Return empty result to prevent errors
        return [
            'data' => [],
            'total' => 0,
            'filtered' => 0
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
            'province_id' => $agency->province_id ?? null,
            'regency_id' => $agency->regency_id ?? null,
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
