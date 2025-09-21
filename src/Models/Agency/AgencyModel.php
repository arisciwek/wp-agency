<?php
/**
 * Agency Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models
 * @version     2.0.0
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
         $this->division_table = $wpdb->prefix . 'app_divisions';
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
            'npwp' => $data['npwp'] ?? null,
            'nib' => $data['nib'] ?? null,
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'],
            'provinsi_code' => $data['provinsi_code'] ?? null,
            'regency_code' => $data['regency_code'] ?? null,
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
            '%s',  // npwp (nullable)
            '%s',  // nib (nullable)
            '%s',  // status
            '%d',  // user_id
            '%s',  // provinsi_code (nullable)
            '%s',  // regency_code (nullable)
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
        
        $this->cache->invalidateAgencyCache($new_id);
        
        return $new_id;
    }

    public function getMembershipData(int $agency_id): array {
        // Check cache first
        $cached_data = $this->cache->get('agency_membership', $agency_id);
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        // Original code to get membership data
        $settings = get_option('wp_agency_membership_settings', []);
        $agency = $this->find($agency_id);
        $level = $agency->membership_level ?? $settings['default_level'] ?? 'regular';
        
        $data = [
            'level' => $level,
            'max_staff' => $settings["{$level}_max_staff"] ?? 2,
            'capabilities' => [
                'can_add_staff' => $settings["{$level}_can_add_staff"] ?? false,
                'can_export' => $settings["{$level}_can_export"] ?? false,
                'can_bulk_import' => $settings["{$level}_can_bulk_import"] ?? false,
            ]
        ];
        
        // Cache for 1 hour or more since membership rarely changes
        $this->cache->set('agency_membership', $data, 1 * HOUR_IN_SECONDS, $agency_id);
        
        return $data;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        $updateData = array_merge($data, ['updated_at' => current_time('mysql')]);
        
        // Remove null values but keep empty strings for NPWP and NIB
        $updateData = array_filter($updateData, function($value, $key) {
            if ($key === 'npwp' || $key === 'nib') {
                return $value !== null;
            }
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

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

        // Base query parts
        $select = "SELECT COUNT(DISTINCT p.id)";
        $from = " FROM {$this->table} p";
        $where = " WHERE 1=1";

        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        $current_user_id = get_current_user_id();

        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has agency: ' . ($has_agency > 0 ? 'yes' : 'no'));

        if ($has_agency > 0 && current_user_can('view_agency_list') && current_user_can('edit_own_agency')) {
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            error_log('Added own agency restriction: ' . $where);
        } elseif (current_user_can('view_agency_list') && current_user_can('edit_all_agencies')) {
            error_log('User can view all agencies - no additional restrictions');
        }

        $sql = $select . $from . $where;
        error_log('Final Query: ' . $sql);
        
        $total = (int) $wpdb->get_var($sql);
        
        // Set cache
        $this->cache->set('agency_total_count', $total, 120, get_current_user_id());
        error_log('Set new cache value: ' . $total);
        
        error_log('Total count result: ' . $total);
        error_log('--- End Debug ---');

        return $total;
    }

    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        error_log("=== getDataTableData start ===");
        error_log("Query params: start=$start, length=$length, search=$search");

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

        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Cek relasi user dengan agency
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has agency: ' . ($has_agency > 0 ? 'yes' : 'no'));

        // Cek status employee
        $employee_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT agency_id FROM {$this->employee_table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User is employee of agency: ' . ($employee_agency ? $employee_agency : 'no'));

        // Permission based filtering
        if (current_user_can('edit_all_agencies')) {
            error_log('User can edit all agencies - no additional restrictions');
        }
        else if ($has_agency > 0 && current_user_can('view_own_agency')) {
            $where .= $wpdb->prepare(" AND p.user_id = %d", $current_user_id);
            error_log('Added owner restriction: ' . $where);
        }
        else if ($employee_agency && current_user_can('view_own_agency')) {
            $where .= $wpdb->prepare(" AND p.id = %d", $employee_agency);
            error_log('Added employee restriction: ' . $where);
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
        
        // Store the current agency data before deletion (for cache invalidation)
        $agency = $this->find($id);
        if (!$agency) {
            return false; // Agency not found
        }

        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        if ($result !== false) {
            // Clear all related cache
            $this->cache->invalidateAgencyCache($id);
            
            // If agency had a user_id, clear that user's cache too
            if (!empty($agency->user_id)) {
                $this->cache->delete('user_agencies', $agency->user_id);
            }

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
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->division_table}
            WHERE agency_id = %d
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('division_count', $count, 120, $id);

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

    // Tambah method helper
    public function existsByNPWP($npwp, $excludeId = null): bool 
    {
        global $wpdb;
        
        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE npwp = %s AND id != %d)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $npwp, $excludeId));
        } else {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE npwp = %s)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $npwp));
        }
    }

    public function existsByNIB($nib, $excludeId = null): bool
    {
        global $wpdb;
        
        if ($excludeId) {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE nib = %s AND id != %d)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $nib, $excludeId));
        } else {
            $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE nib = %s)";
            return (bool)$wpdb->get_var($wpdb->prepare($sql, $nib));
        }
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

    public function createDemoData(array $data): bool {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Disable foreign key checks temporarily
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
            
            // First, delete any existing records with the same name-region combination
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table}
                 WHERE name = %s AND provinsi_code = %s AND regency_code = %s",
                $data['name'],
                $data['provinsi_code'],
                $data['regency_code']
            ));
            
            // Then delete any existing record with the same ID
            $wpdb->delete($this->table, ['id' => $data['id']], ['%d']);
            
            // Now insert the new record
            $result = $wpdb->insert(
                $this->table,
                $data,
                $this->getFormatArray($data)
            );

            if ($result === false) {
                throw new \Exception($wpdb->last_error);
            }

            // Verify insertion
            $inserted = $this->find($data['id']);
            if (!$inserted) {
                throw new \Exception("Failed to verify inserted data");
            }

            // Re-enable foreign key checks
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log("Error in createDemoData: " . $e->getMessage());
            throw $e;
        } finally {
            // Make sure foreign key checks are re-enabled
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        }
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
