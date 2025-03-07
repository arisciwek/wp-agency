<?php
/**
 * Division Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/DivisionDemoData.php
 *
 * Description: Generate division demo data dengan:
 *              - Kantor pusat (type = pusat) untuk setiap agency
 *              - Division (type = cabang) dengan lokasi yang berbeda
 *              - Format kode sesuai DivisionModel::generateDivisionCode()
 *              - Data lokasi dari wi_provinces dan wi_regencies
 *              - Division memiliki 1 kantor pusat dan 1-2 cabang
 *              - Location data terintegrasi dengan trait
 *              - Tracking unique values (NITKU, email)
 *              - Error handling dan validasi
 *
 * Dependencies:
 * - AbstractDemoData                : Base class untuk demo data generation
 * - AgencyDemoDataHelperTrait     : Shared helper methods
 * - AgencyModel                   : Get agency data
 * - DivisionModel                     : Generate division code & save data
 * - WP Database (wi_provinces, wi_regencies)
 * 
 * Database Design:
 * - app_divisions
 *   * id             : Primary key
 *   * agency_id    : Foreign key ke agency
 *   * code           : Format Format kode: TTTT-RRXxRRXx-RR (13 karakter)
 *   *                  TTTT-RRXxRRXx adalah kode agency (12 karakter) 
 *   *                  Tanda hubung '-' (1 karakter)
 *   *                  RR adalah 2 digit random number
 *   * name           : Nama division
 *   * type           : enum('cabang','pusat')
 *   * nitku          : Nomor Identitas Tempat Kegiatan Usaha
 *   * provinsi_id    : Foreign key ke wi_provinces
 *   * regency_id     : Foreign key ke wi_regencies
 *   * user_id        : Foreign key ke wp_users
 *   * status         : enum('active','inactive')
 *
 * Usage Example:
 * ```php 
 * $divisionDemo = new DivisionDemoData($agency_ids, $user_ids);
 * $divisionDemo->run();
 * $division_ids = $divisionDemo->getDivisionIds();
 * ```
 *
 * Order of operations:
 * 1. Validate agency_ids dan user_ids
 * 2. Validate provinces & regencies tables
 * 3. Generate pusat division setiap agency
 * 4. Generate cabang divisions (1-2 per agency)
 * 5. Track generated division IDs
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added integration with wi_provinces and wi_regencies
 * - Added location validation and tracking
 * - Added documentation and usage examples
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\DivisionUsersData;
use WPAgency\Controllers\Division\DivisionController;

defined('ABSPATH') || exit;

class DivisionDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private $division_ids = [];
    private $used_nitku = [];
    private $used_emails = [];
    private $agency_ids;
    private $user_ids;
    private $divisionController;
    protected $division_users = [];

    // Format nama division
    private static $divisions = [
        ['id' => 1, 'name' => '%s Kantor Pusat'],       // Kantor Pusat
        ['id' => 2, 'name' => '%s Division %s'],         // Division Regional
        ['id' => 3, 'name' => '%s Division %s']          // Division Area
    ];

    public function __construct() {
        parent::__construct();
        $this->agency_ids = [];
        $this->user_ids = [];
        $this->division_users = DivisionUsersData::$data;
        $this->divisionController = new DivisionController();
    }

    /**
     * Validasi data sebelum generate
     */
        protected function validate(): bool {
            try {
                // Get all active agency IDs from model
                $this->agency_ids = $this->agencyModel->getAllAgencyIds();
                if (empty($this->agency_ids)) {
                    throw new \Exception('No active agencies found in database');
                }

                // Get division admin users mapping from WPUserGenerator
                $this->division_users = DivisionUsersData::$data;
                if (empty($this->division_users)) {
                    throw new \Exception('Division admin users not found');
                }

                // 1. Validasi keberadaan tabel
                $provinces_exist = $this->wpdb->get_var(
                    "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_provinces'"
                );
                if (!$provinces_exist) {
                    throw new \Exception('Provinces table not found');
                }

                $regencies_exist = $this->wpdb->get_var(
                    "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
                );
                if (!$regencies_exist) {
                    throw new \Exception('Regencies table not found');
                }

                // 2. Validasi ketersediaan data provinsi & regency
                $province_count = $this->wpdb->get_var("
                    SELECT COUNT(*) 
                    FROM {$this->wpdb->prefix}wi_provinces
                ");
                if ($province_count == 0) {
                    throw new \Exception('No provinces data found');
                }

                $regency_count = $this->wpdb->get_var("
                    SELECT COUNT(*) 
                    FROM {$this->wpdb->prefix}wi_regencies
                ");
                if ($regency_count == 0) {
                    throw new \Exception('No regencies data found');
                }

                // 3. Validasi data wilayah untuk setiap agency
                foreach ($this->agency_ids as $agency_id) {
                    $agency = $this->agencyModel->find($agency_id);
                    if (!$agency) {
                        throw new \Exception("Agency not found: {$agency_id}");
                    }

                    // Jika agency punya data wilayah, validasi relasinya
                    if ($agency->provinsi_id && $agency->regency_id) {
                        // Cek provinsi ada
                        $province = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT * FROM {$this->wpdb->prefix}wi_provinces 
                            WHERE id = %d",
                            $agency->provinsi_id
                        ));
                        if (!$province) {
                            throw new \Exception("Invalid province ID for agency {$agency_id}: {$agency->provinsi_id}");
                        }

                        // Cek regency ada dan berelasi dengan provinsi
                        $regency = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT r.*, p.name as province_name 
                            FROM {$this->wpdb->prefix}wi_regencies r
                            JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
                            WHERE r.id = %d AND r.province_id = %d",
                            $agency->regency_id,
                            $agency->provinsi_id
                        ));
                        if (!$regency) {
                            throw new \Exception("Invalid regency ID {$agency->regency_id} for province {$agency->provinsi_id}");
                        }

                        $this->debug(sprintf(
                            "Validated location for agency %d: %s, %s %s",
                            $agency_id,
                            $province->name,
                            $regency->type,
                            $regency->name
                        ));
                    }
                }

                $this->debug('All location data validated successfully');
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
            // Delete existing divisions
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_divisions WHERE id > 0");
            
            // Reset auto increment
            $this->wpdb->query("ALTER TABLE {$this->wpdb->prefix}app_divisions AUTO_INCREMENT = 1");
            
            $this->debug("Cleared existing division data");
        }

        // TAMBAHKAN DI SINI
        if (!$this->validate()) {
            throw new \Exception('Pre-generation validation failed');
        }

        $generated_count = 0;

        try {
            // Get all active agencies
            foreach ($this->agency_ids as $agency_id) {
                $agency = $this->agencyModel->find($agency_id);
                if (!$agency) {
                    $this->debug("Agency not found: {$agency_id}");
                    continue;
                }

                if (!isset($this->division_users[$agency_id])) {
                    $this->debug("No division admin users found for agency {$agency_id}, skipping...");
                    continue;
                }

                // Check for existing pusat division
                $existing_pusat = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_divisions 
                     WHERE agency_id = %d AND type = 'pusat'",
                    $agency_id
                ));

                if ($existing_pusat) {
                    $this->debug("Pusat division exists for agency {$agency_id}, skipping...");
                } else {
                    // Get pusat admin user ID
                    $pusat_user = $this->division_users[$agency_id]['pusat'];
                    $this->debug("Using pusat admin user ID: {$pusat_user['id']} for agency {$agency_id}");
                    $this->generatePusatDivision($agency, $pusat_user['id']);
                    $generated_count++;
                }

                // Check for existing cabang divisions
                $existing_cabang_count = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_divisions 
                     WHERE agency_id = %d AND type = 'cabang'",
                    $agency_id
                ));

                if ($existing_cabang_count > 0) {
                    $this->debug("Cabang divisions exist for agency {$agency_id}, skipping...");
                } else {
                    $this->generateCabangDivisions($agency);
                    $generated_count++;
                }
            }

            if ($generated_count === 0) {
                $this->debug('No new divisions were generated - all divisions already exist');
            } else {
                // Reset auto increment only if we added new data
                $this->wpdb->query(
                    "ALTER TABLE {$this->wpdb->prefix}app_divisions AUTO_INCREMENT = " . 
                    (count($this->division_ids) + 1)
                );
                $this->debug("Division generation completed. Total new divisions processed: {$generated_count}");
            }

        } catch (\Exception $e) {
            $this->debug("Error in division generation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate kantor pusat
     */

    private function generatePusatDivision($agency, $division_user_id): void {
        // Validate location data
        if (!$this->validateLocation($agency->provinsi_id, $agency->regency_id)) {
            throw new \Exception("Invalid location for agency: {$agency->id}");
        }

        // Generate WordPress user dulu
        $userGenerator = new WPUserGenerator();
        
        // Ambil data user dari division_users
        $user_data = $this->division_users[$agency->id]['pusat'];
        
        // Generate WP User
        $wp_user_id = $userGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'role' => 'agency'  // atau role khusus untuk division admin
        ]);

        if (!$wp_user_id) {
            throw new \Exception("Failed to create WordPress user for division admin: {$user_data['display_name']}");
        }

        $regency_name = $this->getRegencyName($agency->regency_id);
        $location = $this->generateValidLocation();
        
        $division_data = [
            'agency_id' => $agency->id,
            'name' => sprintf('%s Division %s', 
                            $agency->name,
                            $regency_name),
            'type' => 'pusat',
            'nitku' => $this->generateNITKU(),
            'postal_code' => $this->generatePostalCode(),
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'address' => $this->generateAddress($regency_name),
            'phone' => $this->generatePhone(),
            'email' => $this->generateEmail($agency->name, 'pusat'),
            'provinsi_id' => $agency->provinsi_id,
            'regency_id' => $agency->regency_id,
            'user_id' => $division_user_id,                  // Division admin user
            'created_by' => $agency->user_id,            // Agency owner user
            'status' => 'active'
        ];
    
        $division_id = $this->divisionController->createDemoDivision($division_data);

        if (!$division_id) {
            throw new \Exception("Failed to create pusat division for agency: {$agency->id}");
        }

        $this->division_ids[] = $division_id;
        $this->debug("Created pusat division for agency {$agency->name}");
    }

    /**
     * Generate cabang divisions
     */
    private function generateCabangDivisions($agency): void {
        // Generate 1-2 cabang per agency
        //$cabang_count = rand(1, 2);

        $cabang_count = 2; // Selalu buat 2 cabang karena sudah ada 2 user cabang

        $used_provinces = [$agency->provinsi_id];
        $userGenerator = new WPUserGenerator();
        
        for ($i = 0; $i < $cabang_count; $i++) {
            // Get cabang admin user ID
            $cabang_key = 'cabang' . ($i + 1);
            if (!isset($this->division_users[$agency->id][$cabang_key])) {
                $this->debug("No admin user found for {$cabang_key} of agency {$agency->id}, skipping...");
                continue;
            }

            // Generate WordPress user untuk cabang
            $user_data = $this->division_users[$agency->id][$cabang_key];
            $wp_user_id = $userGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'role' => 'agency'  // atau role khusus untuk division admin
            ]);
            
            if (!$wp_user_id) {
                throw new \Exception("Failed to create WordPress user for division admin: {$user_data['display_name']}");
            }

            // Get random province (different from used provinces)
            $provinsi_id = $this->getRandomProvinceExcept($agency->provinsi_id);
            while (in_array($provinsi_id, $used_provinces)) {
                $provinsi_id = $this->getRandomProvinceExcept($agency->provinsi_id);
            }
            $used_provinces[] = $provinsi_id;
            
            // Get random regency from selected province
            $regency_id = $this->getRandomRegencyId($provinsi_id);
            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            $division_data = [
                'agency_id' => $agency->id,
                'name' => sprintf('%s Division %s', 
                                $agency->name, 
                                $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($agency->name, $cabang_key),
                'provinsi_id' => $provinsi_id,
                'regency_id' => $regency_id,
                'user_id' => $wp_user_id,  // Gunakan WP user yang baru dibuat
                'created_by' => $agency->user_id,        // Agency owner user
                'status' => 'active'
            ];

            $division_id = $this->divisionController->createDemoDivision($division_data);
            if (!$division_id) {
                throw new \Exception("Failed to create cabang division for agency: {$agency->id}");
            }

            $this->division_ids[] = $division_id;
            $this->debug("Created cabang division for agency {$agency->name} in {$regency_name}");
        }
    }

    /**
     * Helper method generators
     */
    private function generateNITKU(): string {
        do {
            $nitku = sprintf("%013d", rand(1000000000000, 9999999999999));
        } while (in_array($nitku, $this->used_nitku));
        
        $this->used_nitku[] = $nitku;
        return $nitku;
    }

    private function generatePostalCode(): string {
        return (string) rand(10000, 99999);
    }

    private function generatePhone(): string {
        $isMobile = rand(0, 1) === 1;
        $prefix = rand(0, 1) ? '+62' : '0';
        
        if ($isMobile) {
            // Mobile format: +62/0 8xx xxxxxxxx
            return $prefix . '8' . rand(1, 9) . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        } else {
            // Landline format: +62/0 xxx xxxxxxx
            $areaCodes = ['21', '22', '24', '31', '711', '61', '411', '911']; // Jakarta, Bandung, Semarang, Surabaya, Palembang, etc
            $areaCode = $areaCodes[array_rand($areaCodes)];
            return $prefix . $areaCode . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        }
    }

    private function generateEmail($agency_name, $type): string {
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com'];
        
        do {
            $email = sprintf('%s.%s@%s',
                $type,
                strtolower(str_replace([' ', '.'], '', $agency_name)),
                $domains[array_rand($domains)]
            );
        } while (in_array($email, $this->used_emails));
        
        $this->used_emails[] = $email;
        return $email;
    }

    /**
     * Get array of generated division IDs
     */
    public function getDivisionIds(): array {
        return $this->division_ids;
    }

    // Define location bounds untuk wilayah Indonesia
    private const LOCATION_BOUNDS = [
        'LAT_MIN' => -11.0,    // Batas selatan (Pulau Rote)
        'LAT_MAX' => 6.0,      // Batas utara (Sabang)
        'LONG_MIN' => 95.0,    // Batas barat (Pulau Weh)
        'LONG_MAX' => 141.0    // Batas timur (Pulau Merauke)
    ];

    /**
     * Generate random latitude dalam format decimal
     * dengan 8 digit di belakang koma
     */
    private function generateLatitude(): float {
        $min = self::LOCATION_BOUNDS['LAT_MIN'] * 100000000;
        $max = self::LOCATION_BOUNDS['LAT_MAX'] * 100000000;
        $randomInt = rand($min, $max);
        return $randomInt / 100000000;
    }

    /**
     * Generate random longitude dalam format decimal
     * dengan 8 digit di belakang koma
     */
    private function generateLongitude(): float {
        $min = self::LOCATION_BOUNDS['LONG_MIN'] * 100000000;
        $max = self::LOCATION_BOUNDS['LONG_MAX'] * 100000000;
        $randomInt = rand($min, $max);
        return $randomInt / 100000000;
    }

    /**
     * Helper method untuk format koordinat dengan 8 digit decimal
     */
    private function formatCoordinate(float $coordinate): string {
        return number_format($coordinate, 8, '.', '');
    }

    /**
     * Generate dan validasi koordinat
     */
    private function generateValidLocation(): array {
        $latitude = $this->generateLatitude();
        $longitude = $this->generateLongitude();

        return [
            'latitude' => $this->formatCoordinate($latitude),
            'longitude' => $this->formatCoordinate($longitude)
        ];
    }

    /**
     * Debug method untuk test hasil generate
     */
    private function debugLocation(): void {
        $location = $this->generateValidLocation();
        $this->debug(sprintf(
            "Generated location - Lat: %s, Long: %s",
            $location['latitude'],
            $location['longitude']
        ));
    }


}
