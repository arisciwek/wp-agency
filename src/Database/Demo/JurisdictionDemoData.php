<?php
/**
 * Jurisdiction Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     2.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/JurisdictionDemoData.php
 *
 * Description: Generate jurisdiction demo data dari static array JurisdictionData.
 *              Membuat relasi antara divisions dan regencies untuk wilayah kerja.
 *              is_primary = true untuk regency yang sama dengan division.regency_code.
 *              Uses agency index pattern (1-10) like DivisionUsersData.
 *              Works with runtime generated division IDs from DivisionDemoData.
 *
 * Dependencies:
 * - AbstractDemoData                : Base class untuk demo data generation
 * - AgencyDemoDataHelperTrait     : Shared helper methods
 * - JurisdictionData              : Static jurisdiction data
 * - WordPress database ($wpdb)
 *
 * Database Design:
 * - app_jurisdictions
 *   * id             : Primary key
 *   * division_id    : Foreign key ke app_agency_divisions
 *   * jurisdiction_code   : Code ke wi_regencies
 *   * is_primary     : 1 jika regency utama (tidak dapat dipindah)
 *   * created_by     : User ID pembuat
 *
 * Usage Example:
 * ```php
 * $jurisdictionDemo = new JurisdictionDemoData();
 * $jurisdictionDemo->run();
 * ```
 *
 * Order of operations:
 * 1. Load jurisdiction data from static array
 * 2. Validate divisions/regencies exist in database
 * 3. Clear existing jurisdiction data if needed
 * 4. Insert jurisdiction relations with is_primary flag
 *
 * Changelog:
 * 2.0.1 - 2025-11-01 (BugFix: Agency ID Mismatch + Static ID Implementation)
 * - CRITICAL FIX: Changed agency_id calculation from ($index + 20) to $index
 * - AgencyDemoData now injects static IDs 1-10 via wp_agency_before_insert hook
 * - JurisdictionDemoData correctly references agency IDs 1-10
 * - Fixes "Agency not found: 21" error during jurisdiction generation
 *
 * 2.0.0 - 2025-10-31 (TODO-3093)
 * - RESTRUCTURE: Changed to use agency index pattern (like DivisionDemoData)
 * - FIX: Works with runtime generated division IDs
 * - PATTERN: Loop agencies, query divisions by agency_id and type
 * - DATA: Uses JurisdictionData v2.0.0 with agency index structure
 * - FIX: Properly maps database type 'cabang' to JurisdictionData keys 'cabang1'/'cabang2'
 * - REFACTOR: Extract logic to validateJurisdictionData() and createJurisdictionsForDivision()
 * - TESTED: Successfully generates jurisdictions for both pusat (13) and cabang (24) divisions
 * 1.1.0 - 2024-12-XX
 * - Changed to use static array instead of CSV
 * - Added JurisdictionData dependency
 * 1.0.0 - 2024-01-27
 * - Initial version (now deprecated)
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\JurisdictionData;

defined('ABSPATH') || exit;

class JurisdictionDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private $jurisdiction_data = [];

    public function __construct() {
        parent::__construct();
        $this->jurisdiction_data = JurisdictionData::$data;
    }

    /**
     * Validasi data sebelum generate
     */
    protected function validate(): bool {
        try {
            // 1. Validasi tabel divisions ada
            $divisions_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_agency_divisions'"
            );
            if (!$divisions_exist) {
                throw new \Exception('Divisions table not found');
            }

            // 2. Validasi tabel agency jurisdictions ada
            $jurisdictions_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_agency_jurisdictions'"
            );
            if (!$jurisdictions_exist) {
                throw new \Exception('Agency Jurisdictions table not found');
            }

            // 3. Validasi tabel regencies ada
            $regencies_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
            );
            if (!$regencies_exist) {
                throw new \Exception('Regencies table not found');
            }

            // 4. Validasi jurisdiction data tersedia
            if (empty($this->jurisdiction_data)) {
                throw new \Exception('No jurisdiction data found');
            }

            // 5. Validasi agencies dan divisions exist di database
            // Loop agencies 1-10 (matches AgencyDemoData static IDs 1-10)
            for ($agency_index = 1; $agency_index <= 10; $agency_index++) {
                $agency_id = $agency_index; // Agency IDs 1-10 from static ID injection

                // Cek agency exists
                $agency = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_agencies WHERE id = %d",
                    $agency_id
                ));
                if (!$agency) {
                    throw new \Exception("Agency not found: {$agency_id}");
                }

                // Cek data untuk agency index ini
                if (!isset($this->jurisdiction_data[$agency_index])) {
                    $this->debug("No jurisdiction data for agency index {$agency_index}, skipping validation");
                    continue;
                }

                // VALIDATE PUSAT DIVISION
                $pusat_division = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'pusat'",
                    $agency_id
                ));

                if (!$pusat_division) {
                    throw new \Exception("Pusat division not found for agency {$agency_id}");
                }

                // Validate pusat jurisdiction data
                $pusat_data = JurisdictionData::getForDivision($agency_index, 'pusat');
                if ($pusat_data) {
                    $this->validateJurisdictionData($pusat_division, $pusat_data);
                }

                // VALIDATE CABANG DIVISIONS
                $cabang_divisions = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'cabang'
                     ORDER BY id ASC",
                    $agency_id
                ));

                if ($cabang_divisions) {
                    foreach ($cabang_divisions as $idx => $cabang_division) {
                        $cabang_key = 'cabang' . ($idx + 1);
                        $cabang_data = JurisdictionData::getForDivision($agency_index, $cabang_key);

                        if ($cabang_data) {
                            $this->validateJurisdictionData($cabang_division, $cabang_data);
                        } else {
                            $this->debug("No jurisdiction data for agency {$agency_index}, {$cabang_key}");
                        }
                    }
                }
            }

            $this->debug('All jurisdiction data validated successfully');
            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
        }

        if ($this->shouldClearData()) {
            // Delete existing agency jurisdictions
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_agency_jurisdictions WHERE id > 0");

            // Reset auto increment
            $this->wpdb->query("ALTER TABLE {$this->wpdb->prefix}app_agency_jurisdictions AUTO_INCREMENT = 1");

            $this->debug("Cleared existing agency jurisdiction data");
        }

        $generated_count = 0;

        try {
            // Loop agencies 1-10 (matches AgencyDemoData static IDs 1-10)
            for ($agency_index = 1; $agency_index <= 10; $agency_index++) {
                $agency_id = $agency_index; // Agency IDs 1-10 from static ID injection

                // Cek data untuk agency index ini
                if (!isset($this->jurisdiction_data[$agency_index])) {
                    $this->debug("No jurisdiction data for agency index {$agency_index}, skipping");
                    continue;
                }

                // PROCESS PUSAT DIVISION
                $pusat_division = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'pusat'",
                    $agency_id
                ));

                if ($pusat_division) {
                    $jurisdiction_data = JurisdictionData::getForDivision($agency_index, 'pusat');
                    if ($jurisdiction_data) {
                        $generated_count += $this->createJurisdictionsForDivision(
                            $pusat_division,
                            $jurisdiction_data
                        );
                    }
                }

                // PROCESS CABANG DIVISIONS
                // Get all cabang divisions for this agency (ordered by ID for consistent mapping)
                $cabang_divisions = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'cabang'
                     ORDER BY id ASC",
                    $agency_id
                ));

                if ($cabang_divisions) {
                    foreach ($cabang_divisions as $idx => $cabang_division) {
                        // Map first cabang to cabang1, second to cabang2, etc.
                        $cabang_key = 'cabang' . ($idx + 1);

                        $jurisdiction_data = JurisdictionData::getForDivision($agency_index, $cabang_key);
                        if ($jurisdiction_data) {
                            $generated_count += $this->createJurisdictionsForDivision(
                                $cabang_division,
                                $jurisdiction_data
                            );
                        } else {
                            $this->debug("No jurisdiction data for agency {$agency_index}, {$cabang_key}");
                        }
                    }
                }
            }

            if ($generated_count === 0) {
                $this->debug('No new jurisdictions were generated - all data already exists');
            } else {
                $this->debug("Jurisdiction generation completed. Total new relations: {$generated_count}");
            }

        } catch (\Exception $e) {
            $this->debug("Error in jurisdiction generation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate jurisdiction data for a division
     *
     * @param object $division Division object from database
     * @param array $jurisdiction_data Jurisdiction data (regencies, created_by)
     * @throws \Exception If validation fails
     */
    private function validateJurisdictionData($division, $jurisdiction_data): void {
        $regency_codes = $jurisdiction_data['regencies'];

        // Validasi regencies exist
        foreach ($regency_codes as $regency_code) {
            $regency = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}wi_regencies WHERE code = %s",
                $regency_code
            ));
            if (!$regency) {
                throw new \Exception("Regency not found: {$regency_code}");
            }
        }

        // Validasi primary regency (dari division.regency_code)
        $primary_regency = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}wi_regencies WHERE code = %s",
            $division->regency_code
        ));
        if (!$primary_regency) {
            throw new \Exception("Primary regency not found: {$division->regency_code}");
        }
    }

    /**
     * Create jurisdictions for a specific division
     *
     * @param object $division Division object from database
     * @param array $jurisdiction_data Jurisdiction data (regencies, created_by)
     * @return int Number of jurisdictions created
     */
    private function createJurisdictionsForDivision($division, $jurisdiction_data): int {
        $count = 0;
        $primary_jurisdiction_code = $division->regency_code;
        $jurisdiction_codes = $jurisdiction_data['regencies'];
        $created_by = $jurisdiction_data['created_by'];

        // Ensure primary regency is included in the regencies list
        if (!in_array($primary_jurisdiction_code, $jurisdiction_codes)) {
            $jurisdiction_codes[] = $primary_jurisdiction_code;
            $this->debug("Added primary regency {$primary_jurisdiction_code} to division {$division->id} regencies list");
        }

        foreach ($jurisdiction_codes as $jurisdiction_code) {
            $is_primary = ($jurisdiction_code == $primary_jurisdiction_code) ? 1 : 0;

            // Skip if already exists for this division
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}app_agency_jurisdictions
                 WHERE division_id = %d AND jurisdiction_code = %s",
                $division->id, $jurisdiction_code
            ));

            if ($exists) {
                $this->debug("Jurisdiction already exists: division {$division->id}, regency {$jurisdiction_code}");
                continue;
            }

            // Check if jurisdiction_code is already assigned to another division
            $regency_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}app_agency_jurisdictions
                 WHERE jurisdiction_code = %s",
                $jurisdiction_code
            ));

            if ($regency_exists) {
                $this->debug("Regency {$jurisdiction_code} already assigned to another division, skipping for division {$division->id}");
                continue;
            }

            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'app_agency_jurisdictions',
                [
                    'division_id' => $division->id,
                    'jurisdiction_code' => $jurisdiction_code,
                    'is_primary' => $is_primary,
                    'created_by' => $created_by
                ],
                ['%d', '%s', '%d', '%d']
            );

            if ($result === false) {
                throw new \Exception("Failed to insert jurisdiction: division {$division->id}, regency {$jurisdiction_code}");
            }

            $count++;
            $this->debug("Created jurisdiction: division {$division->id}, regency {$jurisdiction_code}, primary: {$is_primary}");
        }

        return $count;
    }


}
