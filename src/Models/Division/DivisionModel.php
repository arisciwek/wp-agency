<?php
/**
 * Division Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.1.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Division/DivisionModel.php
 *
 * Description: Model untuk mengelola data cabang di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Pure CRUD model - DataTable operations moved to DivisionDataTableModel.
 *
 * Changelog:
 * 1.1.1 - 2025-11-01 (TODO-3098 Entity Static IDs)
 * - Added 'wp_agency_division_before_insert' filter hook in create() method
 * - Allows modification of insert data before database insertion
 * - Use cases: demo data (static IDs), migration, data sync, testing
 * - Added dynamic format array handling for 'id' field injection
 *
 * 1.1.0 - 2025-11-01 (TODO-3095)
 * - OPTIMIZATION: getTotalCount() now reuses DivisionDataTableModel::get_total_count()
 * - DEPRECATED: getDataTableData() method (moved to DivisionDataTableModel)
 * - Eliminated 70+ lines of duplicated filtering logic
 * - Dashboard statistics use same logic as DataTable (DRY principle)
 * - Single source of truth for counting queries
 *
 * 1.0.7 - 2025-01-22
 * - Task-2066: Added wp_agency_division_created hook for auto entity creation
 * - Task-2066: Added wp_agency_division_before_delete and wp_agency_division_deleted hooks
 * - Implemented soft delete support (status='inactive' vs hard delete)
 * - Hook fires after successful division creation
 * - Enables automatic employee creation via AutoEntityCreator
 * - Delete hooks enable cascade cleanup and external integrations
 *
 * 1.0.0 - 2024-12-10
 * - Initial implementation
 * - Added core CRUD operations
 * - Added DataTables integration
 * - Added cache support
 */

namespace WPAgency\Models\Division;

use WPAgency\Cache\AgencyCacheManager;
use WPAgency\Models\Agency\AgencyModel;

class DivisionModel {

    // Cache keys - pindahkan dari AgencyCacheManager ke sini untuk akses langsung
    private const KEY_DIVISION = 'division';
    private const KEY_AGENCY_DIVISION_LIST = 'agency_division_list';
    private const KEY_AGENCY_DIVISION = 'agency_division';
    private const KEY_DIVISION_LIST = 'division_list';
    private const CACHE_EXPIRY = 7200; // 2 hours in seconds

    private $table;
    private $agency_table;
    private AgencyModel $agencyModel;
    private $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_agency_divisions';
        $this->agency_table = $wpdb->prefix . 'app_agencies';
        $this->agencyModel = new AgencyModel();
        $this->cache = new AgencyCacheManager();   
    }
    // Tambahkan cache untuk findPusatByAgency
    public function findPusatByAgency(int $agency_id): ?object {
        global $wpdb;
        
        // Cek cache terlebih dahulu
        $cached = $this->cache->get('agency_pusat_division', $agency_id);
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE agency_id = %d 
             AND type = 'pusat' 
             AND status = 'active'
             LIMIT 1",
            $agency_id
        ));
        
        // Simpan ke cache
        if ($result) {
            $this->cache->set('agency_pusat_division', $result, self::CACHE_EXPIRY, $agency_id);
        }
        
        return $result;
    }

    // Tambahkan cache untuk countPusatByAgency
    public function countPusatByAgency(int $agency_id): int {
        global $wpdb;
        
        // Cek cache terlebih dahulu
        $cached = $this->cache->get('agency_pusat_count', $agency_id);
        if ($cached !== null) {
            return (int) $cached;
        }
        
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} 
             WHERE agency_id = %d 
             AND type = 'pusat' 
             AND status = 'active'",
            $agency_id
        ));
        
        // Simpan ke cache
        $this->cache->set('agency_pusat_count', $count, self::CACHE_EXPIRY, $agency_id);
        
        return $count;
    }

    // Tambahkan cache untuk existsByCode
    public function existsByCode(string $code): bool {
        global $wpdb;
        
        // Cek cache terlebih dahulu
        $cached = $this->cache->get('division_code_exists', $code);
        if ($cached !== null) {
            return (bool) $cached;
        }
        
        $exists = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s) as result",
            $code
        ));
        
        // Simpan ke cache dengan expiry lebih pendek
        $this->cache->set('division_code_exists', $exists, 5 * MINUTE_IN_SECONDS, $code);
        
        return $exists;
    }

    private function generateDivisionCode(int $agency_id): string {
        // Get agency code 
        $agency = $this->agencyModel->find($agency_id);
        if (!$agency || empty($agency->code)) {
            throw new \Exception('Invalid agency code');
        }
        
        do {
            // Generate 2 digit random number (RR)
            $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            
            // Format: agency_code + '-' + RR
            $division_code = $agency->code . '-' . $random;
            
            $exists = $this->existsByCode($division_code);
        } while ($exists);
        
        return $division_code;
    }

    public function create(array $data): ?int {
        global $wpdb;

        $data['code'] = $this->generateDivisionCode($data['agency_id']);
        
        $insertData = [
            'agency_id' => $data['agency_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'nitku' => $data['nitku'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'provinsi_code' => $data['provinsi_code'] ?? null,
            'regency_code' => $data['regency_code'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'created_by' => $data['created_by'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => $data['status'] ?? 'active'
        ];

        /**
         * Filter division insert data before database insertion
         *
         * Allows modification of insert data before $wpdb->insert() call.
         *
         * Use cases:
         * - Demo data: Force static IDs for predictable test data
         * - Migration: Import divisions with preserved IDs from external system
         * - Testing: Unit tests with predictable division IDs
         * - Data sync: Synchronize with external systems while preserving IDs
         *
         * @param array $insertData Prepared data ready for $wpdb->insert
         * @param array $data Original input data from controller
         * @return array Modified insert data (can include 'id' key for static ID)
         *
         * @since 1.1.1
         */
        $insertData = apply_filters('wp_agency_division_before_insert', $insertData, $data);

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
            '%s', // code
            '%s', // name
            '%s', // type
            '%s', // nitku
            '%s', // postal_code
            '%f', // latitude
            '%f', // longitude
            '%s', // address
            '%s', // phone
            '%s', // email
            '%s', // provinsi_code
            '%s', // regency_code
            '%d', // user_id
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
            error_log('Failed to insert division: ' . $wpdb->last_error);
            error_log('Insert data: ' . print_r($insertData, true));
            return null;
        }

        $new_id = (int) $wpdb->insert_id;

        // Task-2066: Fire hook for auto-create employee
        if ($new_id) {
            do_action('wp_agency_division_created', $new_id, $insertData);
        }

        // Invalidate unrestricted count cache
        $this->cache->delete('division_total_count_unrestricted');

        // Invalidate dashboard stats cache
        $this->cache->delete('agency_stats_0');

        return $new_id;
    }

    public function find(int $id): ?object {
        global $wpdb;

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($trace[1]['class']) ? $trace[1]['class'] . '::' . $trace[1]['function'] : 'unknown';
        error_log("[DivisionModel] DEBUG: find({$id}) called from {$caller}");

        // Cek cache dulu
        $cached = $this->cache->get(self::KEY_DIVISION, $id);
        if ($cached !== null) {
            return $cached;
        }
        
        // Jika tidak ada di cache, ambil dari database
        $result  = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, p.name as agency_name
            FROM {$this->table} r
            LEFT JOIN {$this->agency_table} p ON r.agency_id = p.id
            WHERE r.id = %d
        ", $id));

        // Simpan ke cache
        if ($result) {
            $this->cache->set(self::KEY_DIVISION, $result, self::CACHE_EXPIRY, $id);
        }        
        return $result;
    }

    public function update(int $id, array $data): bool {
        global $wpdb;

        $updateData = [
            'name' => $data['name'] ?? null,
            'type' => $data['type'] ?? null,
            'nitku' => $data['nitku'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'provinsi_code' => $data['provinsi_code'] ?? null,
            'regency_code' => $data['regency_code'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'status' => $data['status'] ?? null,
                'updated_at' => current_time('mysql')
            ];

        // Remove null values
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });

        $formats = array_map(function($key) {
            switch($key) {
                case 'latitude':
                case 'longitude':
                    return '%f';
                case 'provinsi_code':
                case 'regency_code':
                    return '%s';
                default:
                    return '%s';
            }
        }, array_keys($updateData));

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $formats,
            ['%d']
        );

        if ($result === false) {
            error_log('Update division error: ' . $wpdb->last_error);
            return false;
        }

        // Invalidate all related caches
        $this->cache->delete(self::KEY_DIVISION, $id);
        $this->cache->delete(self::KEY_DIVISION_LIST);
        $this->cache->delete('division_total_count_unrestricted');

        // Invalidate per-user count caches for all affected users
        // Get all users who are owners or employees of this division's agency
        $division = $this->find($id);
        if ($division && $division->agency_id) {
            $affected_users = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT user_id FROM (
                    SELECT user_id FROM {$this->agency_table} WHERE id = %d
                    UNION
                    SELECT user_id FROM {$wpdb->prefix}app_agency_employees WHERE agency_id = %d AND status = 'active'
                ) AS users",
                $division->agency_id,
                $division->agency_id
            ));

            foreach ($affected_users as $user_id) {
                $this->cache->delete('division_total_count_' . $user_id);
                $this->cache->delete('agency_stats_' . $user_id);
            }
        }

        // Also invalidate admin stats
        $this->cache->delete('agency_stats_0');

        return true;
    }

    public function delete(int $id): bool {
        global $wpdb;

        // 1. Get division data before deletion
        $division = $this->find($id);
        if (!$division) {
            return false; // Division not found
        }

        // 2. Prepare division data array for hooks
        $division_data = [
            'id' => $division->id,
            'agency_id' => $division->agency_id,
            'code' => $division->code,
            'name' => $division->name,
            'type' => $division->type,
            'status' => $division->status ?? 'active',
            'nitku' => $division->nitku ?? null,
            'postal_code' => $division->postal_code ?? null,
            'latitude' => $division->latitude ?? null,
            'longitude' => $division->longitude ?? null,
            'address' => $division->address ?? null,
            'phone' => $division->phone ?? null,
            'email' => $division->email ?? null,
            'provinsi_code' => $division->provinsi_code ?? null,
            'regency_code' => $division->regency_code ?? null,
            'user_id' => $division->user_id ?? null,
            'created_by' => $division->created_by ?? null,
            'created_at' => $division->created_at ?? null,
            'updated_at' => $division->updated_at ?? null
        ];

        // 3. Fire before delete hook (for validation/prevention)
        do_action('wp_agency_division_before_delete', $id, $division_data);

        // 4. Check if hard delete is enabled (same setting as Employee/Agency for consistency)
        $settings = get_option('wp_agency_general_options', []);
        $is_hard_delete = isset($settings['enable_hard_delete_branch']) &&
                         $settings['enable_hard_delete_branch'] === true;

        // 5. Perform delete (soft or hard)
        if ($is_hard_delete) {
            // Hard delete - actual DELETE from database
            error_log("[DivisionModel] Hard deleting division {$id} ({$division_data['name']})");

            $result = $wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete - set status to 'inactive'
            error_log("[DivisionModel] Soft deleting division {$id} ({$division_data['name']})");

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
        if ($result !== false && $result !== 0) {
            // Fire after delete hook (for cascade cleanup)
            do_action('wp_agency_division_deleted', $id, $division_data, $is_hard_delete);

            // Invalidate unrestricted count cache
            $this->cache->delete('division_total_count_unrestricted');

            // Invalidate per-user count caches for all affected users
            if ($division_data['agency_id']) {
                $affected_users = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT user_id FROM (
                        SELECT user_id FROM {$this->agency_table} WHERE id = %d
                        UNION
                        SELECT user_id FROM {$wpdb->prefix}app_agency_employees WHERE agency_id = %d AND status = 'active'
                    ) AS users",
                    $division_data['agency_id'],
                    $division_data['agency_id']
                ));

                foreach ($affected_users as $user_id) {
                    $this->cache->delete('division_total_count_' . $user_id);
                    $this->cache->delete('agency_stats_' . $user_id);
                }
            }

            // Also invalidate admin stats
            $this->cache->delete('agency_stats_0');

            error_log("[DivisionModel] Division {$id} deleted successfully (hard_delete: " .
                     ($is_hard_delete ? 'YES' : 'NO') . ")");

            return true;
        }

        return false;
    }

    public function existsByNameInAgency(string $name, int $agency_id, ?int $excludeId = null): bool {
        global $wpdb;
        
        // Buat cache key yang unik termasuk excludeId jika ada
        $cache_key = 'division_name_exists_' . $agency_id . '_' . md5($name);
        if ($excludeId) {
            $cache_key .= '_exclude_' . $excludeId;
        }
        
        // Cek cache terlebih dahulu
        $cached_result = $this->cache->get($cache_key);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }
        
        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table}
                WHERE name = %s AND agency_id = %d";
        $params = [$name, $agency_id];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        $exists = (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
        
        // Simpan ke cache dengan waktu yang lebih singkat (5 menit)
        // karena nama division bisa berubah
        $this->cache->set($cache_key, $exists, 5 * MINUTE_IN_SECONDS);
        
        return $exists;
    }

    /**
     * @deprecated Use DivisionDataTableModel::get_datatable_data() instead
     *
     * This method has been moved to DivisionDataTableModel to follow wp-app-core pattern.
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
        error_log('[DivisionModel] DEPRECATED: getDataTableData() called. Use DivisionDataTableModel instead.');

        // Return empty result to prevent errors
        return [
            'data' => [],
            'total' => 0,
            'filtered' => 0
        ];
    }

    /**
     * Get total division count based on user permission
     * Only users with 'view_division_list' capability can see all divisions
     *
     * @param int|null $agency_id Optional agency ID for filtering
     * @return int Total number of divisions
     */
    public function getTotalCount(?int $agency_id = null): int {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $cache_key = 'division_total_count_' . $current_user_id;
        if ($agency_id) {
            $cache_key .= '_' . $agency_id;
        }

        // Cek cache terlebih dahulu
        $cached_count = $this->cache->get($cache_key);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        $employee_table = $wpdb->prefix . 'app_agency_employees';

        // Check if user is agency owner
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->agency_table} WHERE user_id = %d",
            $current_user_id
        ));

        // Check if user is employee (active status only)
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$employee_table} WHERE user_id = %d AND status = 'active'",
            $current_user_id
        ));

        // If agency_id provided, use DivisionDataTableModel (no duplication!)
        if ($agency_id) {
            $datatable_model = new \WPAgency\Models\Division\DivisionDataTableModel();
            $total = $datatable_model->get_total_count($agency_id, 'active');
        } else {
            // Global count: permission based filtering
            if (current_user_can('edit_all_agencies') || current_user_can('edit_all_divisions')) {
                // Admin: statistics always show active count only
                $sql = "SELECT COUNT(DISTINCT r.id) FROM {$this->table} r WHERE r.status = 'active'";
                $total = (int) $wpdb->get_var($sql);
            } elseif ($has_agency > 0 || $is_employee > 0) {
                // User owns agency OR is employee: count divisions from their agencies (active only)
                $sql = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT r.id)
                     FROM {$this->table} r
                     INNER JOIN {$this->agency_table} p ON r.agency_id = p.id
                     LEFT JOIN {$employee_table} e ON p.id = e.agency_id AND e.status = 'active'
                     WHERE (p.user_id = %d OR e.user_id = %d)
                       AND p.status = 'active'
                       AND r.status = 'active'",
                    $current_user_id,
                    $current_user_id
                );
                $total = (int) $wpdb->get_var($sql);
            } else {
                $total = 0;
            }
        }

        // Simpan ke cache - 10 menit
        $this->cache->set($cache_key, $total, 10 * MINUTE_IN_SECONDS);

        return $total;
    }

    /**
     * Get total division count without permission restrictions
     * Used for dashboard statistics that should show global totals
     *
     * @return int Total number of divisions in database
     */
    public function getTotalCountUnrestricted(): int {
        global $wpdb;

        // Check cache first
        $cached_total = $this->cache->get('division_total_count_unrestricted');
        if ($cached_total !== null) {
            return (int) $cached_total;
        }

        // Simple count query without any restrictions
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        // Cache for 5 minutes
        $this->cache->set('division_total_count_unrestricted', $total, 300);

        return $total;
    }
    
    public function getByAgency($agency_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'app_agency_divisions';
        
        $query = $wpdb->prepare(
            "SELECT id, name, address, phone, email, status 
             FROM {$table} 
             WHERE agency_id = %d 
             AND status = 'active'
             ORDER BY name ASC",
            $agency_id
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Cek apakah user adalah employee aktif untuk division tertentu
     * 
     * @param int $division_id Division ID
     * @param int|null $user_id User ID (default: current user)
     * @return bool True jika user adalah employee aktif
     */
    public function isEmployeeActive(int $division_id, ?int $user_id = null): bool {
        global $wpdb;
        
        // Gunakan current user jika user_id tidak diberikan
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Kunci cache
        $cache_key = 'employee_status';
        
        // Cek apakah hasil sudah ada di cache
        $cached_result = $this->cache->get($cache_key, $user_id, $division_id);
        if ($cached_result !== null) {
            return (bool) $cached_result;
        }
        
        // Query untuk cek status employee
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees 
             WHERE division_id = %d 
             AND user_id = %d 
             AND status = 'active'",
            $division_id,
            $user_id
        ));
        
        $result = (int)$is_employee > 0;
        
        // Simpan hasil ke cache (15 menit)
        $this->cache->set($cache_key, $result, 15 * MINUTE_IN_SECONDS, $user_id, $division_id);
        
        return $result;
    }

    /**
     * Invalidasi cache status employee
     *
     * @param int $division_id Division ID
     * @param int|null $user_id User ID (default: current user)
     */
    public function invalidateEmployeeStatusCache(int $division_id, ?int $user_id = null): void {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $this->cache->delete('employee_status', $user_id, $division_id);
    }









}
