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

                // Skip if already exists for this division
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_jurisdictions
                     WHERE division_id = %d AND jurisdiction_code = %s",
                    $division_id, $regency_code
                ));

                if ($exists) {
                    error_log('DEBUG JURISDICTION MODEL: Jurisdiction ' . $regency_code . ' already exists for division ' . $division_id . ', skipping');
                    continue;
                }

                // Check if jurisdiction_code is already assigned to another division in the same agency
                $regency_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT aj.id FROM {$wpdb->prefix}app_agency_jurisdictions aj
                     JOIN {$wpdb->prefix}app_agency_divisions d ON aj.division_id = d.id
                     WHERE aj.jurisdiction_code = %s AND d.agency_id = (
                         SELECT agency_id FROM {$wpdb->prefix}app_agency_divisions WHERE id = %d
                     )",
                    $regency_code, $division_id
                ));

                if ($regency_exists) {
                    error_log('DEBUG JURISDICTION MODEL: Jurisdiction ' . $regency_code . ' already assigned to another division in the same agency, throwing exception');
                    throw new \Exception('Regency already assigned to another division in the same agency');
                }

                $result = $wpdb->insert(
                    $table,
                    [
                        'division_id' => $division_id,
                        'jurisdiction_code' => $regency_code,
                        'is_primary' => $is_primary,
                        'created_by' => $current_user_id,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ],
                    ['%d', '%s', '%d', '%d', '%s', '%s']
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
        if ($cached !== null) {
            return $cached;
        }

        $query = $wpdb->prepare("
            SELECT j.*, r.id as regency_id, r.name as regency_name, r.province_id
            FROM {$wpdb->prefix}app_agency_jurisdictions j
            LEFT JOIN {$wpdb->prefix}wi_regencies r ON j.jurisdiction_code = r.code
            WHERE j.division_id = %d
            ORDER BY r.name ASC
        ", $division_id);

        $jurisdictions = $wpdb->get_results($query);

        // Cache the result
        $this->cache->set($cache_key, $jurisdictions, self::CACHE_EXPIRY);

        return $jurisdictions ?: [];
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

        $cache_key = 'available_regencies_agency_' . $agency_id . '_v11';
        $filter_province = $province_code ?: ($agency ? $agency->provinsi_code : '');
        if ($filter_province) {
            $cache_key .= '_province_' . $filter_province;
        }
        if ($exclude_division_id) {
            $cache_key .= '_exclude_' . $exclude_division_id;
        }

        // Check cache first
        $cached = $this->cache->get($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        // Delete old cache keys
        $old_cache_key = str_replace('_v7', '_v6', $cache_key);
        $this->cache->delete($old_cache_key);
        $old_cache_key2 = str_replace('_v7', '_v5', $cache_key);
        $this->cache->delete($old_cache_key2);

        // Get regencies in province that are not assigned as jurisdictions to any division in the agency
        $query = "
            SELECT r.id, r.code, r.name, p.name as province_name
            FROM {$wpdb->prefix}wi_regencies r
            JOIN {$wpdb->prefix}wi_provinces p ON p.id = r.province_id AND p.code = %s
            LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions aj ON aj.jurisdiction_code = r.code
            LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON aj.division_id = d.id AND d.agency_id = %d";
        $params = [$filter_province, $agency_id];

        if ($exclude_division_id) {
            // For edit mode: exclude current division's jurisdiction assignments from the "assigned" check
            $query .= " AND d.id != %d";
            $params[] = $exclude_division_id;
        }

        $query .= "
            WHERE aj.jurisdiction_code IS NULL
            ORDER BY r.code ASC";

        error_log("DEBUG MODEL: Query: " . $wpdb->prepare($query, $params));
        error_log("DEBUG MODEL: Params: " . print_r($params, true));

        $available_regencies = $wpdb->get_results($wpdb->prepare($query, $params));

        error_log("DEBUG MODEL: Found " . count($available_regencies) . " available regencies");
        if (!empty($available_regencies)) {
            error_log("DEBUG MODEL: Sample results: " . print_r(array_slice($available_regencies, 0, 3), true));
        }

        // Cache the result
        $this->cache->set($cache_key, $available_regencies ?: [], self::CACHE_EXPIRY);

        return $available_regencies ?: [];
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

        // Clear division-specific caches
        $this->cache->delete('division_jurisdictions_' . $division_id);

        // Clear available regencies caches (need to consider province and version)
        $agency_model = new AgencyModel();
        $agency = $agency_model->find($agency_id);
        $base_key = 'available_regencies_agency_' . $agency_id . '_v11';
        $filter_province = $agency ? $agency->provinsi_code : '';
        if ($filter_province) {
            $base_key .= '_province_' . $filter_province;
        }
        $this->cache->delete($base_key);
        $this->cache->delete($base_key . '_exclude_' . $division_id);

        // Clear DataTable cache
        $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
    }
}
