<?php
/**
 * Division Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Division/DivisionModel.php
 *
 * Description: Model untuk mengelola data cabang di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.1.0 - 2025-01-22
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

        $result = $wpdb->insert(
            $this->table,
            $insertData,
            [
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
            ]
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

    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir, string $status_filter = 'active'): array {
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as agency_name, GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as jurisdictions";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->agency_table} p ON r.agency_id = p.id
                  LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions j ON r.id = j.division_id
                  LEFT JOIN {$wpdb->prefix}wi_regencies wr ON j.jurisdiction_code = wr.code";
        $where = " WHERE r.agency_id = %d";
        $params = [$agency_id];

        // Add status filter
        if ($status_filter !== 'all') {
            $where .= " AND r.status = %s";
            $params[] = $status_filter;
        }

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND (r.name LIKE %s OR r.code LIKE %s OR wr.name LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Validate order column
        $validColumns = ['code', 'name', 'type', 'status', 'jurisdictions'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add group by and limit
        $groupBy = " GROUP BY r.id";
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $groupBy . $order . $limit;

        // Get paginated results
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count for agency (with status filter applied)
        $total_where = "WHERE agency_id = %d";
        $total_params = [$agency_id];
        if ($status_filter !== 'all') {
            $total_where .= " AND status = %s";
            $total_params[] = $status_filter;
        }

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} {$total_where}",
            $total_params
        ));

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
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

        // Permission based filtering
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
