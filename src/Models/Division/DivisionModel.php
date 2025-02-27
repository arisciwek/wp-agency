<?php
/**
 * Division Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.0.0
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
    private const KEY_BRANCH = 'division';
    private const KEY_AGENCY_BRANCH_LIST = 'agency_division_list';
    private const KEY_AGENCY_BRANCH = 'agency_division';
    private const KEY_BRANCH_LIST = 'division_list';
    private const CACHE_EXPIRY = 7200; // 2 hours in seconds

    private $table;
    private $agency_table;
    private AgencyModel $agencyModel;
    private $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_divisions';
        $this->agency_table = $wpdb->prefix . 'app_agencies';
        $this->agencyModel = new AgencyModel();
        $this->cache = new AgencyCacheManager();   
    }
    
    public function findPusatByAgency(int $agency_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE agency_id = %d 
             AND type = 'pusat' 
             AND status = 'active'
             LIMIT 1",
            $agency_id
        ));
    }

    public function countPusatByAgency(int $agency_id): int {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} 
             WHERE agency_id = %d 
             AND type = 'pusat' 
             AND status = 'active'",
            $agency_id
        ));
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
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
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
                '%d', // provinsi_id
                '%d', // regency_id
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

        return (int) $wpdb->insert_id;
    }

    public function find(int $id): ?object {
        global $wpdb;

        // Cek cache dulu
        $cached = $this->cache->get(self::KEY_BRANCH, $id);
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
            $this->cache->set(self::KEY_BRANCH, $data, self::CACHE_EXPIRY, $id);
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
            'provinsi_id' => $data['provinsi_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
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
                case 'provinsi_id':
                case 'regency_id':
                    return '%d';
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
            $this->cache->delete(self::KEY_BRANCH, $id);
            $this->cache->delete(self::KEY_BRANCH_LIST);          
            return false;
        }

        return true;
    }

    public function delete(int $id): bool {
        global $wpdb;

        return $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        ) !== false;
    }
    public function existsByCode(string $code): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$this->table} WHERE code = %s) as result",
            $code
        ));
    }

    public function existsByNameInAgency(string $name, int $agency_id, ?int $excludeId = null): bool {
        global $wpdb;

        $sql = "SELECT EXISTS (SELECT 1 FROM {$this->table}
                WHERE name = %s AND agency_id = %d";
        $params = [$name, $agency_id];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";

        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as agency_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->agency_table} p ON r.agency_id = p.id";
        $where = " WHERE r.agency_id = %d";
        $params = [$agency_id];

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND r.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Validate order column
        $validColumns = ['code', 'name', 'type'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Validate order direction
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        // Build order clause
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Get paginated results
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count for agency
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE agency_id = %d",
            $agency_id
        ));
        // Di function getDataTableData()
        $datatable_query = (!empty($params)) ? $wpdb->prepare($sql, $params) : $sql;
        error_log('DataTable Query: ' . $datatable_query);

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
    }

    /**
     * Get total division count based on user permission
     * Only users with 'view_division_list' capability can see all divisiones
     * 
     * @param int|null $id Optional agency ID for filtering
     * @return int Total number of divisiones
     */

    public function getTotalCount($agency_id): int {
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as agency_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$this->agency_table} p ON r.agency_id = p.id";
        
        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);
        // Cek dulu current_user_id untuk berbagai keperluan 
        $current_user_id = get_current_user_id();

        // Dapatkan relasi User ID wordpress dengan agency User ID
        $has_agency = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->agency_table} WHERE user_id = %d",
            $current_user_id
        ));
        error_log('User has agency: ' . ($has_agency > 0 ? 'yes' : 'no'));

        if ($has_agency > 0 && current_user_can('view_own_agency') && current_user_can('view_own_division')) {
            $where .= " AND p.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added user restriction: ' . $where);
        }

        if (current_user_can('edit_all_agency') && current_user_can('edit_all_division')) {
            error_log('Added user restriction: ' . $where);
        }


        // Complete query
        $query = $select . $from . $join . $where;
        $final_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;
        
        error_log('Final Query: ' . $final_query);
        
        // Execute query
        $wpdb->get_results($final_query);
        
        // Get total and log
        $total = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Total count result: ' . $total);
        error_log('--- End Debug ---');

        return $total;
    }

    public function getByAgency($agency_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'app_divisions';
        
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

}
