<?php
/**
 * Jurisdiction Validator Class
 *
 * @package     WP_Agency
 * @subpackage  Validators/Division
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: src/Validators/Division/JurisdictionValidator.php
 *
 * Description: Validator untuk operasi Jurisdiction.
 *              Memastikan semua input data valid sebelum diproses model.
 *              Menyediakan validasi untuk jurisdiction assignment.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial release
 * - Moved from DivisionValidator
 */

namespace WPAgency\Validators\Division;

use WPAgency\Cache\AgencyCacheManager;

class JurisdictionValidator {
    private AgencyCacheManager $cache;

    public function __construct() {
        $this->cache = new AgencyCacheManager();
    }

    /**
     * Validate jurisdiction assignment for division
     * Ensures that selected jurisdictions are available (not assigned to other divisions)
     *
     * @param int $agency_id Agency ID
     * @param array $jurisdiction_ids_or_codes Array of regency IDs or codes to validate
     * @param int|null $exclude_division_id Division ID to exclude from conflict check (for edit mode)
     * @param bool $is_codes Whether the array contains codes (true) or IDs (false)
     * @return array Validation result with 'valid' boolean and optional 'message'
     */
    public function validateJurisdictionAssignment(int $agency_id, array $jurisdiction_ids_or_codes, ?int $exclude_division_id = null, bool $is_codes = false): array {
        global $wpdb;

        // Debug logging: Log input parameters
        error_log('DEBUG JURISDICTION VALIDATOR: Agency ID: ' . $agency_id);
        error_log('DEBUG JURISDICTION VALIDATOR: Jurisdiction IDs/Codes: ' . print_r($jurisdiction_ids_or_codes, true));
        error_log('DEBUG JURISDICTION VALIDATOR: Exclude Division ID: ' . $exclude_division_id);
        error_log('DEBUG JURISDICTION VALIDATOR: Is Codes: ' . $is_codes);

        if (empty($jurisdiction_ids_or_codes)) {
            return ['valid' => true];
        }

        // Convert IDs to codes if needed
        $jurisdiction_codes = $jurisdiction_ids_or_codes;
        if (!$is_codes) {
            // Convert IDs to codes
            $placeholders = str_repeat('%d,', count($jurisdiction_ids_or_codes) - 1) . '%d';
            $query = $wpdb->prepare("
                SELECT code FROM {$wpdb->prefix}wi_regencies
                WHERE id IN ($placeholders)
            ", $jurisdiction_ids_or_codes);

            // Debug logging: Log conversion query
            error_log('DEBUG JURISDICTION VALIDATOR: Conversion query: ' . $query);

            $codes_result = $wpdb->get_col($query);
            if (count($codes_result) !== count($jurisdiction_ids_or_codes)) {
                return [
                    'valid' => false,
                    'message' => 'Beberapa wilayah yang dipilih tidak valid.'
                ];
            }
            $jurisdiction_codes = $codes_result;

            // Debug logging: Log converted codes
            error_log('DEBUG JURISDICTION VALIDATOR: Converted codes: ' . print_r($jurisdiction_codes, true));
        }

        // Create cache key
        $cache_key = 'jurisdiction_assignment_validation_' . $agency_id . '_' . md5(implode(',', $jurisdiction_codes));
        if ($exclude_division_id) {
            $cache_key .= '_exclude_' . $exclude_division_id;
        }

        // Check cache first
        $cached_result = $this->cache->get($cache_key);
        if ($cached_result !== null) {
            return $cached_result;
        }

        // Build query to check for conflicts
        $placeholders = str_repeat('%s,', count($jurisdiction_codes) - 1) . '%s';
        $params = $jurisdiction_codes;
        $params[] = $agency_id;

        $exclude_condition = "";
        if ($exclude_division_id) {
            $exclude_condition = "AND aj.division_id != %d";
            $params[] = $exclude_division_id;
        }

        $query = $wpdb->prepare("
            SELECT aj.jurisdiction_code, d.name as division_name, d.code as division_code
            FROM {$wpdb->prefix}app_agency_jurisdictions aj
            JOIN {$wpdb->prefix}app_agency_divisions d ON aj.division_id = d.id
            WHERE aj.jurisdiction_code IN ($placeholders)
            AND d.agency_id = %d
            $exclude_condition
            LIMIT 1
        ", $params);

        // Debug logging: Log query and params
        error_log('DEBUG JURISDICTION VALIDATOR: Query: ' . $query);
        error_log('DEBUG JURISDICTION VALIDATOR: Params: ' . print_r($params, true));

        $conflict = $wpdb->get_row($query);

        // Debug logging: Log conflict result
        error_log('DEBUG JURISDICTION VALIDATOR: Conflict result: ' . print_r($conflict, true));

        $result = ['valid' => true];
        if ($conflict) {
            $result = [
                'valid' => false,
                'message' => sprintf(
                    'Wilayah %s sudah ditetapkan untuk cabang %s (%s). Silakan pilih wilayah lain.',
                    $conflict->regency_code,
                    $conflict->division_name,
                    $conflict->division_code
                )
            ];
        }

        // Debug logging: Log final result
        error_log('DEBUG JURISDICTION VALIDATOR: Final result: ' . print_r($result, true));

        // Cache the result (short expiry since jurisdiction assignments can change)
        $this->cache->set($cache_key, $result, 2 * MINUTE_IN_SECONDS);

        return $result;
    }
}
