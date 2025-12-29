<?php
/**
 * Agency Model
 *
 * @package     WP_Agency
 * @subpackage  Models/Agency
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Agency/AgencyModel.php
 *
 * Description: CRUD model untuk Agency entity.
 *              Extends AbstractCrudModel dari wp-app-core.
 *              Handles create, read, update, delete operations.
 *              All CRUD operations INHERITED from AbstractCrudModel.
 *              AUTO-TRACKING: Uses Auditable trait for audit logging.
 *
 * Changelog:
 * 2.0.0 - 2025-12-28 (Refactor to AbstractCrudModel)
 * - BREAKING: Refactored to extend AbstractCrudModel
 * - Adapted from CustomerModel pattern
 * - CRUD methods INHERITED: find(), create(), update(), delete()
 * - Implements 7 abstract methods
 * - Custom methods: generateAgencyCode(), getDivisionCount()
 * - Auditable trait for automatic audit logging
 */

namespace WPAgency\Models\Agency;

use WPAppCore\Models\Abstract\AbstractCrudModel;
use WPAgency\Cache\AgencyCacheManager;
use WPAgency\Traits\Auditable;

defined('ABSPATH') || exit;

class AgencyModel extends AbstractCrudModel {
    use Auditable;

    /**
     * Auditable configuration
     */
    protected $auditable_type = 'agency';
    protected $auditable_excluded = ['updated_at', 'created_at', 'created_by'];

    /**
     * Reference field mappings for audit log
     */
    protected $auditable_references = [
        'province_id' => ['table' => 'wi_provinces', 'key' => 'id', 'label' => 'name'],
        'regency_id' => ['table' => 'wi_regencies', 'key' => 'id', 'label' => 'name'],
        'user_id' => ['table' => 'users', 'key' => 'ID', 'label' => 'display_name'],
    ];

    /**
     * Static code tracker (untuk uniqueness)
     */
    private static $used_codes = [];

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(AgencyCacheManager::getInstance());
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (7 required)
    // ========================================

    /**
     * Get database table name
     *
     * @return string
     */
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_agencies';
    }

    /**
     * Get cache method name prefix
     *
     * @return string
     */
    protected function getCacheKey(): string {
        return 'Agency';
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'agency';
    }

    /**
     * Get plugin prefix for hooks
     *
     * @return string
     */
    protected function getPluginPrefix(): string {
        return 'wp_agency';
    }

    /**
     * Get allowed fields for update operations
     *
     * @return array
     */
    protected function getAllowedFields(): array {
        return [
            'name',
            'status',
            'province_id',
            'regency_id',
            'user_id'
        ];
    }

    /**
     * Prepare insert data from request
     *
     * @param array $data Raw request data
     * @return array Prepared insert data
     */
    protected function prepareInsertData(array $data): array {
        // Generate unique code
        $data['code'] = $this->generateAgencyCode();

        return [
            'code' => $data['code'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'reg_type' => $data['reg_type'] ?? 'self',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
    }

    /**
     * Get format map for wpdb operations
     *
     * @return array
     */
    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'code' => '%s',
            'name' => '%s',
            'status' => '%s',
            'user_id' => '%d',
            'province_id' => '%d',
            'regency_id' => '%d',
            'reg_type' => '%s',
            'created_by' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
    }

    // ========================================
    // AUDIT LOG INTEGRATION
    // ========================================

    /**
     * Create agency with audit logging and static ID injection support
     *
     * @param array $data Agency data
     * @return int|null Agency ID or null on failure
     */
    public function create(array $data): ?int {
        global $wpdb;

        // Prepare insert data
        $insertData = [
            'code' => $data['code'] ?? $this->generateAgencyCode(),
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'user_id' => $data['user_id'] ?? null,
            'province_id' => $data['province_id'] ?? null,
            'regency_id' => $data['regency_id'] ?? null,
            'reg_type' => $data['reg_type'] ?? null,
            'created_by' => $data['created_by'] ?? get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        /**
         * Filter: Allow modification of insert data before insertion
         *
         * Use cases:
         * - Demo data: Force static IDs for predictable test data
         * - Migration: Import agencies with preserved IDs from external system
         *
         * @param array $insertData Prepared data ready for $wpdb->insert
         * @param array $data Original input data
         * @return array Modified insert data (can include 'id' key for static ID)
         */
        $insertData = apply_filters('wp_agency_before_insert', $insertData, $data);

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
            '%s', // code
            '%s', // name
            '%s', // status
            '%d', // user_id
            '%d', // province_id
            '%d', // regency_id
            '%s', // reg_type
            '%d', // created_by
            '%s', // created_at
            '%s'  // updated_at
        ]);

        $result = $wpdb->insert(
            $this->getTableName(),
            $insertData,
            $format
        );

        if ($result === false) {
            error_log('Failed to insert agency: ' . $wpdb->last_error);
            error_log('Insert data: ' . print_r($insertData, true));
            return null;
        }

        $agency_id = (int) $wpdb->insert_id;

        // Log creation
        if ($agency_id) {
            $this->logAudit('created', $agency_id, null, $insertData);

            // Fire hook for auto-create division pusat and employee
            do_action('wp_agency_agency_created', $agency_id, $data);
        }

        return $agency_id;
    }

    /**
     * Update agency with audit logging
     *
     * @param int $id Agency ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        error_log('[AgencyModel] Updating agency ID: ' . $id . ' | Data: ' . json_encode($data));

        // Get old data before update
        $old_data = $this->find($id);

        // Call parent update method
        error_log('[AgencyModel] Calling parent::update()...');
        $result = parent::update($id, $data);
        error_log('[AgencyModel] Parent update result: ' . ($result ? 'SUCCESS' : 'FAILED'));

        // Log update (only changed fields will be logged)
        if ($result && $old_data) {
            error_log('[AgencyModel] Logging audit for agency update...');
            $this->logAudit('updated', $id, $old_data, $data);
        } else {
            error_log('[AgencyModel] No audit log - update failed or no old data');
        }

        return $result;
    }

    /**
     * Delete agency with audit logging
     *
     * @param int $id Agency ID
     * @return bool Success status
     */
    public function delete(int $id): bool {
        // Get data before deletion
        $old_data = $this->find($id);

        // Call parent delete method
        $result = parent::delete($id);

        // Log deletion
        if ($result && $old_data) {
            $this->logAudit('deleted', $id, $old_data, null);
        }

        return $result;
    }

    // ========================================
    // CUSTOM METHODS (Entity-specific)
    // ========================================

    /**
     * Generate unique agency code
     *
     * Format: AGC-TTTTRR
     * - AGC = Agency prefix
     * - TTTT = 4 digit timestamp (last 4 digits)
     * - RR = 2 random digits
     *
     * @return string Unique agency code
     */
    public function generateAgencyCode(): string {
        $max_attempts = 100;
        $attempt = 0;

        do {
            // Get 4 digits from timestamp
            $timestamp = substr(time(), -4);

            // Generate 2 random digits
            $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

            // Format: AGC-TTTTRR
            $code = sprintf('AGC-%s%s', $timestamp, $random);

            // Check uniqueness
            $exists = in_array($code, self::$used_codes) || $this->codeExists($code);

            $attempt++;

            // Safety: If too many attempts, add microseconds
            if ($attempt > 50) {
                $micro = substr((string)microtime(true), -2);
                $code = sprintf('AGC-%s%s', $timestamp, $micro);
                $exists = in_array($code, self::$used_codes) || $this->codeExists($code);
            }

        } while ($exists && $attempt < $max_attempts);

        if ($attempt >= $max_attempts) {
            // Fallback: use uniqid for guaranteed uniqueness
            $code = 'AGC-' . substr(uniqid(), -6);
            error_log("[AgencyModel] WARNING: Code generation max attempts reached, using uniqid: {$code}");
        }

        self::$used_codes[] = $code;
        return $code;
    }

    /**
     * Check if code exists in database
     *
     * @param string $code Agency code
     * @return bool
     */
    public function codeExists(string $code): bool {
        global $wpdb;
        $table = $this->getTableName();

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$table} WHERE code = %s) as result",
            $code
        ));
    }

    /**
     * Check if name exists (untuk validation)
     *
     * @param string $name Agency name
     * @param int|null $excludeId Exclude ID (for update)
     * @return bool
     */
    public function existsByName(string $name, ?int $excludeId = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT EXISTS (SELECT 1 FROM {$table} WHERE name = %s";
        $params = [$name];

        if ($excludeId) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }

        $sql .= ") as result";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    /**
     * Get division count for agency (untuk validation)
     *
     * @param int $id Agency ID
     * @return int Division count
     */
    public function getDivisionCount(int $id): int {
        // Check cache first
        $cached_count = $this->cache->get('division_count', $id);
        if ($cached_count !== false) {
            return (int) $cached_count;
        }

        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}app_agency_divisions
            WHERE agency_id = %d
        ", $id));

        // Cache for 2 minutes
        $this->cache->set('division_count', $count, 120, $id);

        return $count;
    }

    /**
     * Get all agency IDs
     * Used by demo data generators
     *
     * @param string $status Filter by status (default: 'active')
     * @return array Array of agency IDs
     */
    public function getAllAgencyIds(string $status = 'active'): array {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT id FROM {$table}";

        if ($status !== 'all') {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }

        $sql .= " ORDER BY id ASC";

        $results = $wpdb->get_col($sql);

        return $results ? array_map('intval', $results) : [];
    }

    /**
     * Get admin user data for agency
     *
     * @param int $agency_id Agency ID
     * @return object|null Admin user data or null if not found
     */
    public function getAdminUser(int $agency_id): ?object {
        $agency = $this->find($agency_id);

        if (!$agency || !$agency->user_id) {
            return null;
        }

        $user = get_userdata($agency->user_id);

        if (!$user) {
            return null;
        }

        return (object) [
            'id' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_login' => $user->user_login
        ];
    }

    /**
     * Get total count (untuk statistics)
     * Reuse from AgencyDataTableModel for consistency
     *
     * @return int
     */
    public function getTotalCount(): int {
        $datatable_model = new \WPAgency\Models\Agency\AgencyDataTableModel();
        return $datatable_model->get_total_count();
    }

    /**
     * Get user relation with agency (for permission checking)
     *
     * @param int $agency_id Agency ID
     * @param int|null $user_id User ID (defaults to current user)
     * @return array Relation data
     */
    public function getUserRelation(int $agency_id, ?int $user_id = null): array {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Check cache first
        $cache_key = "user_relation_{$user_id}_{$agency_id}";
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $agency = $this->find($agency_id);

        $relation = [
            'is_admin' => current_user_can('edit_all_agencies'),
            'is_agency_admin' => $agency && $agency->user_id == $user_id,
            'is_agency_employee' => false,
            'is_division_head' => false,
            'agency_id' => $agency_id,
            'user_id' => $user_id,
            'access_type' => 'none'
        ];

        // Check if employee
        $is_employee = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT EXISTS (SELECT 1 FROM {$wpdb->prefix}app_agency_employees WHERE agency_id = %d AND user_id = %d) as result",
            $agency_id,
            $user_id
        ));

        if ($is_employee) {
            $relation['is_agency_employee'] = true;
            $relation['access_type'] = 'agency_employee';

            // Check if division admin (user_id matches division.user_id)
            $is_division_head = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}app_agency_divisions
                    WHERE agency_id = %d AND user_id = %d
                ) as result",
                $agency_id,
                $user_id
            ));

            if ($is_division_head) {
                $relation['is_division_head'] = true;
                $relation['access_type'] = 'division_head';
            }
        }

        // Determine access type
        if ($relation['is_admin']) {
            $relation['access_type'] = 'admin';
        } elseif ($relation['is_agency_admin']) {
            $relation['access_type'] = 'agency_admin';
        }

        // Cache for 5 minutes
        $this->cache->set($cache_key, $relation, 300);

        return $relation;
    }
}
