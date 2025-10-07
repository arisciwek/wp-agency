<?php
/**
 * New Company Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Company/NewCompanyModel.php
 *
 * Description: Model untuk mengelola data company (branch) yang belum memiliki inspector.
 *              Handles operasi query dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 * - Added DataTables integration
 * - Added cache support
 */

namespace WPAgency\Models\Company;

use WPAgency\Cache\AgencyCacheManager;

class NewCompanyModel {
    private $branches_table;
    private $customers_table;
    private $agencies_table;
    private $divisions_table;
    private $regencies_table;
    private AgencyCacheManager $cache;
    
    // Cache configuration
    private const CACHE_KEY_LIST = 'new_company_list';
    private const CACHE_KEY_BRANCH = 'branch_without_inspector';
    private const CACHE_EXPIRY = 7200; // 2 hours

    public function __construct() {
        global $wpdb;
        $this->branches_table = $wpdb->prefix . 'app_agency_branches';
        $this->customers_table = $wpdb->prefix . 'app_customers';
        $this->agencies_table = $wpdb->prefix . 'app_agencies';
        $this->divisions_table = $wpdb->prefix . 'app_divisions';
        $this->regencies_table = $wpdb->prefix . 'wi_regencies';
        $this->cache = new AgencyCacheManager();
    }

    /**
     * Get DataTable data for branches without inspector
     */
    public function getDataTableData(int $agency_id, int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Normalize orderDir for cache consistency
        $orderDir = strtolower($orderDir);

        error_log("DEBUG NewCompanyModel::getDataTableData - Called with agency_id={$agency_id}, start={$start}, length={$length}, search='{$search}', orderColumn='{$orderColumn}', orderDir='{$orderDir}'");

        // Check cache first using DataTable cache method
        $cached_result = $this->cache->getDataTableCache(
            self::CACHE_KEY_LIST,
            'agency_' . $agency_id,
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            ['agency_id' => $agency_id]
        );

        if ($cached_result !== null) {
            error_log("DEBUG NewCompanyModel::getDataTableData - CACHE HIT for agency_{$agency_id}, returning cached data");
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("NewCompanyModel cache hit for DataTable - Key: " . self::CACHE_KEY_LIST . "_agency_{$agency_id}");
            }
            return $cached_result;
        }

        error_log("DEBUG NewCompanyModel::getDataTableData - CACHE MISS for agency_{$agency_id}, executing query");
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("NewCompanyModel cache miss for DataTable - Key: " . self::CACHE_KEY_LIST . "_agency_{$agency_id}");
        }

        // Base query
        $select = "SELECT SQL_CALC_FOUND_ROWS 
                    b.id,
                    b.code,
                    c.name as company_name,
                    d.name as division_name,
                    r.name as regency_name,
                    b.agency_id,
                    b.division_id,
                    b.regency_id";
        
        $from = " FROM {$this->branches_table} b";
        
        $join = " LEFT JOIN {$this->customers_table} c ON b.customer_id = c.id
                  LEFT JOIN {$this->agencies_table} a ON b.agency_id = a.id
                  LEFT JOIN {$this->divisions_table} d ON b.division_id = d.id
                  LEFT JOIN {$this->regencies_table} r ON b.regency_id = r.id";
        
        $where = " WHERE a.id = %d 
                   AND b.inspector_id IS NULL
                   AND b.status = 'active'";
        
        $params = [$agency_id];

        // Add search if provided
        if (!empty($search)) {
            $where .= " AND (
                b.code LIKE %s OR 
                c.name LIKE %s OR 
                d.name LIKE %s OR 
                r.name LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Validate and apply ordering
        $valid_columns = ['code', 'company_name', 'division_name', 'regency_name'];
        if (!in_array($orderColumn, $valid_columns)) {
            $orderColumn = 'code';
        }

        // Map column names to actual database columns
        $column_map = [
            'code' => 'b.code',
            'company_name' => 'c.name',
            'division_name' => 'd.name',
            'regency_name' => 'r.name'
        ];
        
        $order_by = $column_map[$orderColumn] ?? 'b.code';
        $sqlOrderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';
        $order = " ORDER BY " . $order_by . " " . $sqlOrderDir;

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Log query for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query_log = $wpdb->prepare($sql, $params);
            error_log("New Company DataTable Query: " . $query_log);
        }

        // Get paginated results
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));

        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count for agency
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$this->branches_table} b
             LEFT JOIN {$this->agencies_table} a ON b.agency_id = a.id
             WHERE a.id = %d
             AND b.inspector_id IS NULL
             AND b.status = 'active'",
            $agency_id
        ));

        error_log("DEBUG NewCompanyModel::getDataTableData - Query results: total={$total}, filtered={$filtered}, data_count=" . count($results));

        // Prepare result
        $result = [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];

        // Cache the result using DataTable cache method
        $this->cache->setDataTableCache(
            self::CACHE_KEY_LIST,
            'agency_' . $agency_id,
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            $result,
            ['agency_id' => $agency_id]
        );

        return $result;
    }

    /**
     * Get branches without inspector for specific agency
     */
    public function getBranchesWithoutInspector(int $agency_id): array {
        global $wpdb;

        // Check cache first
        $cache_key = self::CACHE_KEY_BRANCH . '_' . $agency_id;
        $cached = $this->cache->get(self::CACHE_KEY_BRANCH, $agency_id);
        
        if ($cached !== null) {
            return $cached;
        }

        $sql = "SELECT 
                    b.id,
                    b.code,
                    c.name as company_name,
                    d.name as division_name,
                    r.name as regency_name
                FROM {$this->branches_table} b
                LEFT JOIN {$this->customers_table} c ON b.customer_id = c.id
                LEFT JOIN {$this->agencies_table} a ON b.agency_id = a.id
                LEFT JOIN {$this->divisions_table} d ON b.division_id = d.id
                LEFT JOIN {$this->regencies_table} r ON b.regency_id = r.id
                WHERE a.id = %d 
                AND b.inspector_id IS NULL
                AND b.status = 'active'
                ORDER BY b.code ASC";

        $results = $wpdb->get_results($wpdb->prepare($sql, $agency_id));

        // Cache the results
        if ($results) {
            $this->cache->set(self::CACHE_KEY_BRANCH, $results, self::CACHE_EXPIRY, $agency_id);
        }

        return $results;
    }

    /**
     * Assign inspector to branch
     */
    public function assignInspector(int $branch_id, int $inspector_id): bool {
        global $wpdb;

        error_log("DEBUG NewCompanyModel::assignInspector - Starting assignment: branch_id={$branch_id}, inspector_id={$inspector_id}");

        $result = $wpdb->update(
            $this->branches_table,
            [
                'inspector_id' => $inspector_id,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $branch_id],
            ['%d', '%s'],
            ['%d']
        );

        error_log("DEBUG NewCompanyModel::assignInspector - Update result: " . ($result !== false ? 'SUCCESS' : 'FAILED') . ", affected rows: {$result}");

        if ($result !== false) {
            // Verify the update
            $updated_branch = $this->getBranchById($branch_id);
            error_log("DEBUG NewCompanyModel::assignInspector - After update, inspector_id: " . ($updated_branch ? $updated_branch->inspector_id : 'NULL'));

            // Clear related caches
            $branch = $this->getBranchById($branch_id);
            if ($branch && $branch->agency_id) {
                error_log("DEBUG NewCompanyModel::assignInspector - Clearing cache for agency_id: {$branch->agency_id}");
                $this->cache->delete(self::CACHE_KEY_BRANCH, $branch->agency_id);
                $this->cache->invalidateDataTableCache(self::CACHE_KEY_LIST, [
                    'agency_id' => $branch->agency_id
                ]);
            }
        }

        return $result !== false;
    }

    /**
     * Get branch by ID
     */
    public function getBranchById(int $branch_id): ?object {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->branches_table} WHERE id = %d",
            $branch_id
        ));
    }

    /**
     * Check if inspector is already assigned to other branches
     */
    public function getInspectorAssignments(int $inspector_id): array {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, c.name as company_name
             FROM {$this->branches_table} b
             LEFT JOIN {$this->customers_table} c ON b.customer_id = c.id
             WHERE b.inspector_id = %d
             AND b.status = 'active'",
            $inspector_id
        ));
    }

    /**
     * Get total count of branches without inspector for an agency
     */
    public function getTotalBranchesWithoutInspector(int $agency_id): int {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$this->branches_table} b
             LEFT JOIN {$this->agencies_table} a ON b.agency_id = a.id
             WHERE a.id = %d 
             AND b.inspector_id IS NULL
             AND b.status = 'active'",
            $agency_id
        ));
    }
}
