<?php
/**
 * Jurisdiction Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/JurisdictionDemoData.php
 *
 * Description: Generate jurisdiction demo data dari static array JurisdictionData.
 *              Membuat relasi antara divisions dan regencies untuk wilayah kerja.
 *              is_primary = true untuk regency yang sama dengan division.regency_code.
 *              Data diambil dari array dan divalidasi terhadap tabel divisions dan regencies.
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
 *   * division_id    : Foreign key ke app_divisions
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
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - CSV parsing and validation
 * - is_primary logic implementation
 * 1.1.0 - 2024-12-XX
 * - Changed to use static array instead of CSV
 * - Added JurisdictionData dependency
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
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_divisions'"
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

            // 5. Validasi divisions dan regencies exist di database
            foreach ($this->jurisdiction_data as $division_id => $data) {
                // Cek division exists
                $division = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_divisions WHERE id = %d",
                    $division_id
                ));
                if (!$division) {
                    throw new \Exception("Division not found: {$division_id}");
                }

                // Get regency codes directly
                $jurisdiction_codes = JurisdictionData::getRegencyCodesForDivision($division_id);

                // Cek semua regencies exist
                foreach ($jurisdiction_codes as $jurisdiction_code) {
                    $regency = $this->wpdb->get_row($this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->prefix}wi_regencies WHERE code = %s",
                        $jurisdiction_code
                    ));
                    if (!$regency) {
                        throw new \Exception("Regency not found: {$jurisdiction_code}");
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
            foreach ($this->jurisdiction_data as $division_id => $data) {
                // Get division to determine primary regency
                $division = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_divisions WHERE id = %d",
                    $division_id
                ));

                if (!$division) {
                    $this->debug("Division not found: {$division_id}, skipping...");
                    continue;
                }

                $primary_jurisdiction_code = $division->regency_code;
                // Get regency codes directly
                $jurisdiction_codes = JurisdictionData::getRegencyCodesForDivision($division_id);
                $created_by = $data['created_by'];

                // Ensure primary regency is included in the regencies list
                if (!in_array($primary_jurisdiction_code, $jurisdiction_codes)) {
                    $jurisdiction_codes[] = $primary_jurisdiction_code;
                    $this->debug("Added primary regency {$primary_jurisdiction_code} to division {$division_id} regencies list");
                }

                foreach ($jurisdiction_codes as $jurisdiction_code) {
                    $is_primary = ($jurisdiction_code == $primary_jurisdiction_code) ? 1 : 0;

                    // Skip if already exists for this division
                    $exists = $this->wpdb->get_var($this->wpdb->prepare(
                        "SELECT id FROM {$this->wpdb->prefix}app_agency_jurisdictions
                         WHERE division_id = %d AND jurisdiction_code = %s",
                        $division_id, $jurisdiction_code
                    ));

                    if ($exists) {
                        $this->debug("Jurisdiction already exists: division {$division_id}, regency {$jurisdiction_code}");
                        continue;
                    }

                    // Check if jurisdiction_code is already assigned to another division
                    $regency_exists = $this->wpdb->get_var($this->wpdb->prepare(
                        "SELECT id FROM {$this->wpdb->prefix}app_agency_jurisdictions
                         WHERE jurisdiction_code = %s",
                        $jurisdiction_code
                    ));

                    if ($regency_exists) {
                        $this->debug("Regency {$jurisdiction_code} already assigned to another division, skipping for division {$division_id}");
                        continue;
                    }

                    $result = $this->wpdb->insert(
                        $this->wpdb->prefix . 'app_agency_jurisdictions',
                        [
                            'division_id' => $division_id,
                            'jurisdiction_code' => $jurisdiction_code,
                            'is_primary' => $is_primary,
                            'created_by' => $created_by
                        ],
                        ['%d', '%s', '%d', '%d']
                    );

                    if ($result === false) {
                        throw new \Exception("Failed to insert jurisdiction: division {$division_id}, regency {$jurisdiction_code}");
                    }

                    $generated_count++;
                    $this->debug("Created jurisdiction: division {$division_id}, regency {$jurisdiction_code}, primary: {$is_primary}");
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


}
