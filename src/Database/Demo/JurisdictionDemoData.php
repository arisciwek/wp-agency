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
 * Description: Generate jurisdiction demo data dari CSV wp_app_juridictions.csv.
 *              Membuat relasi antara divisions dan regencies untuk wilayah kerja.
 *              is_primary = true untuk regency yang sama dengan division.regency_id.
 *              Data diambil dari CSV dan divalidasi terhadap tabel divisions dan regencies.
 *
 * Dependencies:
 * - AbstractDemoData                : Base class untuk demo data generation
 * - AgencyDemoDataHelperTrait     : Shared helper methods
 * - WordPress database ($wpdb)
 * - CSV file: docs/wp_app_juridictions.csv
 *
 * Database Design:
 * - app_jurisdictions
 *   * id             : Primary key
 *   * division_id    : Foreign key ke app_divisions
 *   * regency_id     : Foreign key ke wi_regencies
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
 * 1. Validate CSV file exists
 * 2. Parse CSV and validate divisions/regencies exist
 * 3. Clear existing jurisdiction data if needed
 * 4. Insert jurisdiction relations with is_primary flag
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - CSV parsing and validation
 * - is_primary logic implementation
 */

namespace WPAgency\Database\Demo;

defined('ABSPATH') || exit;

class JurisdictionDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private $csv_file_path;
    private $jurisdiction_data = [];

    public function __construct() {
        parent::__construct();
        $this->csv_file_path = WP_PLUGIN_DIR . '/wp-agency/docs/wp_app_juridictions.csv';
    }

    /**
     * Validasi data sebelum generate
     */
    protected function validate(): bool {
        try {
            // 1. Validasi file CSV ada
            if (!file_exists($this->csv_file_path)) {
                throw new \Exception("CSV file not found: {$this->csv_file_path}");
            }

            // 2. Validasi tabel divisions ada
            $divisions_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_divisions'"
            );
            if (!$divisions_exist) {
                throw new \Exception('Divisions table not found');
            }

            // 3. Validasi tabel agency jurisdictions ada
            $jurisdictions_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_agency_jurisdictions'"
            );
            if (!$jurisdictions_exist) {
                throw new \Exception('Agency Jurisdictions table not found');
            }

            // 4. Validasi tabel regencies ada
            $regencies_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
            );
            if (!$regencies_exist) {
                throw new \Exception('Regencies table not found');
            }

            // 5. Parse CSV dan validasi data
            $this->parseCSV();
            if (empty($this->jurisdiction_data)) {
                throw new \Exception('No valid jurisdiction data found in CSV');
            }

            // 6. Validasi divisions dan regencies exist di database
            foreach ($this->jurisdiction_data as $division_id => $data) {
                // Cek division exists
                $division = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_divisions WHERE id = %d",
                    $division_id
                ));
                if (!$division) {
                    throw new \Exception("Division not found: {$division_id}");
                }

                // Cek semua regencies exist
                foreach ($data['regencies'] as $regency_id) {
                    $regency = $this->wpdb->get_row($this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->prefix}wi_regencies WHERE id = %d",
                        $regency_id
                    ));
                    if (!$regency) {
                        throw new \Exception("Regency not found: {$regency_id}");
                    }
                }

                // Validasi primary regency matches division's regency_id
                if ($division->regency_id != $data['primary_regency']) {
                    throw new \Exception("Primary regency mismatch for division {$division_id}: expected {$division->regency_id}, got {$data['primary_regency']}");
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
                $primary_regency = $data['primary_regency'];
                $regencies = $data['regencies'];
                $created_by = $data['created_by'];

                foreach ($regencies as $regency_id) {
                    $is_primary = ($regency_id == $primary_regency) ? 1 : 0;

                    // Skip if already exists (avoid duplicates)
                    $exists = $this->wpdb->get_var($this->wpdb->prepare(
                        "SELECT id FROM {$this->wpdb->prefix}app_agency_jurisdictions
                         WHERE division_id = %d AND regency_id = %d",
                        $division_id, $regency_id
                    ));

                    if ($exists) {
                        $this->debug("Jurisdiction already exists: division {$division_id}, regency {$regency_id}");
                        continue;
                    }

                    $result = $this->wpdb->insert(
                        $this->wpdb->prefix . 'app_agency_jurisdictions',
                        [
                            'division_id' => $division_id,
                            'regency_id' => $regency_id,
                            'is_primary' => $is_primary,
                            'created_by' => $created_by
                        ],
                        ['%d', '%d', '%d', '%d']
                    );

                    if ($result === false) {
                        throw new \Exception("Failed to insert jurisdiction: division {$division_id}, regency {$regency_id}");
                    }

                    $generated_count++;
                    $this->debug("Created jurisdiction: division {$division_id}, regency {$regency_id}, primary: {$is_primary}");
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
     * Parse CSV file dan ekstrak jurisdiction data
     */
    private function parseCSV(): void {
        $handle = fopen($this->csv_file_path, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open CSV file: {$this->csv_file_path}");
        }

        // Skip header
        fgetcsv($handle, 1000, ",");

        $current_division = null;
        $regencies = [];

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // CSV columns: id,name,agency_id,code,provinsi_id,regency_id,id,code,name
            $division_id = !empty($data[0]) ? (int)$data[0] : null;
            $division_name = $data[1];
            $agency_id = !empty($data[2]) ? (int)$data[2] : null;
            $division_code = $data[3];
            $provinsi_id = !empty($data[4]) ? (int)$data[4] : null;
            $division_regency_id = !empty($data[5]) ? (int)$data[5] : null;
            $regency_id = !empty($data[6]) ? (int)$data[6] : null;
            $regency_code = $data[7];
            $regency_name = $data[8];

            if ($division_id !== null) {
                // New division
                if ($current_division !== null) {
                    // Save previous division data
                    $this->jurisdiction_data[$current_division]['regencies'] = $regencies;
                }

                $current_division = $division_id;
                $regencies = [];

                // Set primary regency and created_by (use agency owner or default)
                $this->jurisdiction_data[$current_division] = [
                    'primary_regency' => $division_regency_id,
                    'created_by' => 1, // Default admin user, could be improved
                    'regencies' => []
                ];

                // Add the primary regency
                if ($division_regency_id) {
                    $regencies[] = $division_regency_id;
                }
            }

            // Add additional regency if exists
            if ($regency_id && !in_array($regency_id, $regencies)) {
                $regencies[] = $regency_id;
            }
        }

        // Save last division
        if ($current_division !== null) {
            $this->jurisdiction_data[$current_division]['regencies'] = $regencies;
        }

        fclose($handle);

        $this->debug("Parsed " . count($this->jurisdiction_data) . " divisions from CSV");
    }
}
