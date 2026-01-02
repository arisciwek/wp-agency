<?php
/**
 * Jurisdiction Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Division/JurisdictionModel.php
 *
 * Description: Model untuk mengelola data jurisdiction di database.
 *              Handles operasi CRUD dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial implementation
 * - Moved from DivisionModel
 */

namespace WPAgency\Models\Division;

use WPAgency\Cache\AgencyCacheManager;
use WPAgency\Models\Agency\AgencyModel;

class JurisdictionModel {

    // Cache keys
    private const CACHE_EXPIRY = 7200; // 2 hours in seconds

    private $cache;

    public function __construct() {
        $this->cache = new AgencyCacheManager();
    }

    /**
     * Save jurisdictions for a division
     *
     * @param int $division_id Division ID
     * @param array $jurisdiction_ids Array of regency IDs
     * @param array $primary_jurisdictions Array of regency IDs that should be marked as primary
     * @return bool Success status
     */
    public function saveJurisdictions(int $division_id, array $jurisdiction_ids, array $primary_jurisdictions = []): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'app_agency_jurisdictions';
        $current_user_id = get_current_user_id();

        // Debug logging: Log input parameters
        error_log('DEBUG JURISDICTION MODEL: Division ID: ' . $division_id);
        error_log('DEBUG JURISDICTION MODEL: Jurisdiction IDs: ' . print_r($jurisdiction_ids, true));
        error_log('DEBUG JURISDICTION MODEL: Primary jurisdictions: ' . print_r($primary_jurisdictions, true));

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Delete existing jurisdictions for this division
            $deleted_count = $wpdb->delete($table, ['division_id' => $division_id], ['%d']);
            error_log('DEBUG JURISDICTION MODEL: Deleted ' . $deleted_count . ' existing jurisdictions for division ' . $division_id);

            // Insert new jurisdictions
            foreach ($jurisdiction_ids as $regency_code) {
                $is_primary = in_array($regency_code, $primary_jurisdictions) ? 1 : 0;

                // Debug logging: Log each jurisdiction being processed
                error_log('DEBUG JURISDICTION MODEL: Processing jurisdiction: ' . $regency_code . ', is_primary: ' . $is_primary);

                // Convert regency_code to regency_id
                $regency_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wi_regencies WHERE code = %s",
                    $regency_code
                ));

                if (!$regency_id) {
                    error_log('DEBUG JURISDICTION MODEL: Regency code ' . $regency_code . ' not found, skipping');
                    continue;
                }

                // Skip if already exists for this division
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_jurisdictions
                     WHERE division_id = %d AND jurisdiction_regency_id = %d",
                    $division_id, $regency_id
                ));

                if ($exists) {
                    error_log('DEBUG JURISDICTION MODEL: Jurisdiction ' . $regency_code . ' already exists for division ' . $division_id . ', skipping');
                    continue;
                }

                // Check if jurisdiction_regency_id is already assigned to another division in the same agency
                $regency_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT aj.id FROM {$wpdb->prefix}app_agency_jurisdictions aj
                     JOIN {$wpdb->prefix}app_agency_divisions d ON aj.division_id = d.id
                     WHERE aj.jurisdiction_regency_id = %d AND d.agency_id = (
                         SELECT agency_id FROM {$wpdb->prefix}app_agency_divisions WHERE id = %d
                     )",
                    $regency_id, $division_id
                ));

                if ($regency_exists) {
                    error_log('DEBUG JURISDICTION MODEL: Jurisdiction ' . $regency_code . ' already assigned to another division in the same agency, throwing exception');
                    throw new \Exception('Regency already assigned to another division in the same agency');
                }

                $result = $wpdb->insert(
                    $table,
                    [
                        'division_id' => $division_id,
                        'jurisdiction_regency_id' => $regency_id,
                        'is_primary' => $is_primary,
                        'created_by' => $current_user_id,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%d', '%d', '%s', '%s']
                );

                if ($result === false) {
                    error_log('DEBUG JURISDICTION MODEL: Failed to insert jurisdiction ' . $regency_code . ': ' . $wpdb->last_error);
                    throw new \Exception('Failed to insert jurisdiction: ' . $wpdb->last_error);
                } else {
                    error_log('DEBUG JURISDICTION MODEL: Successfully inserted jurisdiction ' . $regency_code . ' for division ' . $division_id);
                }
            }

            $wpdb->query('COMMIT');
            error_log('DEBUG JURISDICTION MODEL: Transaction committed successfully for division ' . $division_id);

            // Invalidate related caches
            $this->invalidateJurisdictionCache($division_id);

            error_log('DEBUG JURISDICTION MODEL: Jurisdictions saved successfully for division ' . $division_id);
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('DEBUG JURISDICTION MODEL: Error saving jurisdictions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get jurisdictions for a division
     *
     * @param int $division_id Division ID
     * @return array Array of jurisdiction objects with regency details
     */
    public function getJurisdictionsByDivision(int $division_id): array {
        global $wpdb;

        $cache_key = 'division_jurisdictions_' . $division_id;

        // Check cache first
        $cached = $this->cache->get($cache_key);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $query = $wpdb->prepare("
            SELECT j.*, r.id as regency_id, r.name as regency_name, r.code as regency_code, r.province_id
            FROM {$wpdb->prefix}app_agency_jurisdictions j
            LEFT JOIN {$wpdb->prefix}wi_regencies r ON j.jurisdiction_regency_id = r.id
            WHERE j.division_id = %d
            ORDER BY r.name ASC
        ", $division_id);

        $jurisdictions = $wpdb->get_results($query);

        // Ensure we always return an array
        $result = is_array($jurisdictions) ? $jurisdictions : [];

        // Cache the result
        $this->cache->set($cache_key, $result, self::CACHE_EXPIRY);

        return $result;
    }

    /**
     * Get available regencies for an agency in a province
     * For create mode: excludes regencies already assigned to any division in the agency
     * For edit mode: excludes regencies assigned to other divisions in the agency (allows current division's assignments)
     *
     * @param int $agency_id Agency ID
     * @param int|null $exclude_division_id Division ID to exclude from assignment check (for edit mode)
     * @param string $province_code Province code to filter regencies
     * @return array Array of regency objects
     */
    public function getAvailableRegenciesForAgency(int $agency_id, ?int $exclude_division_id = null, string $province_code = ''): array {
        global $wpdb;

        // Get agency province to filter regencies
        $agency_model = new AgencyModel();
        $agency = $agency_model->find($agency_id);

        // Determine province code to filter
        $filter_province = $province_code;
        if (!$filter_province && $agency && $agency->province_id) {
            $filter_province = $wpdb->get_var($wpdb->prepare(
                "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
                $agency->province_id
            ));
        }

        $cache_key = 'available_regencies_agency_' . $agency_id . '_v12';
        if ($filter_province) {
            $cache_key .= '_province_' . $filter_province;
        }
        if ($exclude_division_id) {
            $cache_key .= '_exclude_' . $exclude_division_id;
        }

        error_log('DEBUG AVAILABLE REGENCIES: Cache key: ' . $cache_key);

        // Check cache first
        $cached = $this->cache->get($cache_key);
        if ($cached !== null && is_array($cached)) {
            error_log('DEBUG AVAILABLE REGENCIES: CACHE HIT - Returning ' . count($cached) . ' regencies from cache');
            return $cached;
        }

        error_log('DEBUG AVAILABLE REGENCIES: CACHE MISS - Fetching fresh data from database');

        // Delete old cache keys
        $old_versions = ['_v11', '_v10', '_v9', '_v8', '_v7', '_v6', '_v5'];
        foreach ($old_versions as $old_ver) {
            $old_cache_key = str_replace('_v12', $old_ver, $cache_key);
            $this->cache->delete($old_cache_key);
        }

        // Get regencies in province that are available for this division
        if ($exclude_division_id) {
            // EDIT MODE: Show regencies that are either:
            // 1. Not assigned to any division in this agency, OR
            // 2. Assigned to the current division being edited
            $query = "
                SELECT DISTINCT r.id, r.code, r.name, p.name as province_name
                FROM {$wpdb->prefix}wi_regencies r
                JOIN {$wpdb->prefix}wi_provinces p ON p.id = r.province_id AND p.code = %s
                WHERE r.id NOT IN (
                    SELECT aj2.jurisdiction_regency_id
                    FROM {$wpdb->prefix}app_agency_jurisdictions aj2
                    JOIN {$wpdb->prefix}app_agency_divisions d2 ON aj2.division_id = d2.id
                    WHERE d2.agency_id = %d
                    AND d2.id != %d
                )
                ORDER BY r.code ASC";
            $params = [$filter_province, $agency_id, $exclude_division_id];
        } else {
            // CREATE MODE: Show only truly unassigned regencies
            $query = "
                SELECT r.id, r.code, r.name, p.name as province_name
                FROM {$wpdb->prefix}wi_regencies r
                JOIN {$wpdb->prefix}wi_provinces p ON p.id = r.province_id AND p.code = %s
                LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions aj ON aj.jurisdiction_regency_id = r.id
                LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON aj.division_id = d.id AND d.agency_id = %d
                WHERE aj.jurisdiction_regency_id IS NULL
                ORDER BY r.code ASC";
            $params = [$filter_province, $agency_id];
        }

        $prepared_query = $wpdb->prepare($query, $params);
        error_log("DEBUG MODEL: Query: " . $prepared_query);
        error_log("DEBUG MODEL: Params: " . print_r($params, true));

        $available_regencies = $wpdb->get_results($prepared_query);

        // Check for SQL errors
        if ($wpdb->last_error) {
            error_log("DEBUG MODEL: SQL Error: " . $wpdb->last_error);
        }

        // Ensure we always have an array
        $result = is_array($available_regencies) ? $available_regencies : [];

        error_log("DEBUG MODEL: Found " . count($result) . " available regencies");
        if (!empty($result)) {
            error_log("DEBUG MODEL: Sample results: " . print_r(array_slice($result, 0, 3), true));
        } else {
            error_log("DEBUG MODEL: No available regencies found - all might be assigned or query issue");
        }

        // Cache the result
        $this->cache->set($cache_key, $result, self::CACHE_EXPIRY);
        error_log('DEBUG AVAILABLE REGENCIES: Cached ' . count($result) . ' regencies with key: ' . $cache_key);

        return $result;
    }

    /**
     * Invalidate jurisdiction-related caches
     *
     * @param int $division_id Division ID
     */
    private function invalidateJurisdictionCache(int $division_id): void {
        // Get division to find agency_id
        $division_model = new \WPAgency\Models\Division\DivisionModel();
        $division = $division_model->find($division_id);
        if (!$division) {
            return;
        }

        $agency_id = $division->agency_id;

        error_log('DEBUG CACHE INVALIDATION: Starting for division ' . $division_id . ', agency ' . $agency_id);

        // Clear division-specific caches
        $this->cache->delete('division_jurisdictions_' . $division_id);
        error_log('DEBUG CACHE INVALIDATION: Deleted division_jurisdictions_' . $division_id);

        // Clear available regencies caches (need to consider province and version)
        $agency_model = new AgencyModel();
        $agency = $agency_model->find($agency_id);

        // Get province code from province_id
        global $wpdb;
        $filter_province = '';
        if ($agency && $agency->province_id) {
            $filter_province = $wpdb->get_var($wpdb->prepare(
                "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
                $agency->province_id
            ));
        }

        $base_key = 'available_regencies_agency_' . $agency_id . '_v12';
        if ($filter_province) {
            $base_key .= '_province_' . $filter_province;
        }

        // Delete base cache key (without exclude parameter)
        $this->cache->delete($base_key);
        error_log('DEBUG CACHE INVALIDATION: Deleted base key: ' . $base_key);

        // Get ALL divisions in this agency and delete their exclude caches
        $all_divisions = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d",
            $agency_id
        ));

        error_log('DEBUG CACHE INVALIDATION: Found ' . count($all_divisions) . ' divisions in agency');

        // Delete cache for ALL divisions (not just the current one)
        foreach ($all_divisions as $div_id) {
            $exclude_key = $base_key . '_exclude_' . $div_id;
            $this->cache->delete($exclude_key);
            error_log('DEBUG CACHE INVALIDATION: Deleted: ' . $exclude_key);
        }

        // Also delete old version caches for ALL divisions
        $old_versions = ['_v11', '_v10', '_v9'];
        foreach ($old_versions as $old_ver) {
            $old_base_key = str_replace('_v12', $old_ver, $base_key);
            $this->cache->delete($old_base_key);

            foreach ($all_divisions as $div_id) {
                $old_exclude_key = $old_base_key . '_exclude_' . $div_id;
                $this->cache->delete($old_exclude_key);
            }
        }

        error_log('DEBUG CACHE INVALIDATION: Deleted old version caches');

        // Clear DataTable cache
        $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
        error_log('DEBUG CACHE INVALIDATION: Invalidated DataTable cache for agency ' . $agency_id);
    }
}
