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
 * - app_agency_divisions
 *   * id             : Primary key
 *   * agency_id    : Foreign key ke agency
 *   * code           : Format Format kode: TTTT-RRXxRRXx-RR (13 karakter)
 *   *                  TTTT-RRXxRRXx adalah kode agency (12 karakter) 
 *   *                  Tanda hubung '-' (1 karakter)
 *   *                  RR adalah 2 digit random number
 *   * name           : Nama division
 *   * type           : enum('cabang','pusat')
 *   * nitku          : Nomor Identitas Tempat Kegiatan Usaha
 *   * provinsi_code  : Code ke wi_provinces
 *   * regency_code   : Code ke wi_regencies
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
        ['id' => 1, 'name' => 'UPT %s'],       // Kantor Pusat
        ['id' => 2, 'name' => 'UPT %s'],         // Division Regional
        ['id' => 3, 'name' => 'UPT %s']          // Division Area
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
                    if ($agency->provinsi_code && $agency->regency_code) {
                        // Cek provinsi ada
                        $province = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT * FROM {$this->wpdb->prefix}wi_provinces
                            WHERE code = %s",
                            $agency->provinsi_code
                        ));
                        if (!$province) {
                            throw new \Exception("Invalid province code for agency {$agency_id}: {$agency->provinsi_code}");
                        }

                        // Cek regency ada dan berelasi dengan provinsi
                        $regency = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT r.*, p.name as province_name
                            FROM {$this->wpdb->prefix}wi_regencies r
                            JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
                            WHERE r.code = %s AND p.code = %s",
                            $agency->regency_code,
                            $agency->provinsi_code
                        ));
                        if (!$regency) {
                            throw new \Exception("Invalid regency code {$agency->regency_code} for province {$agency->provinsi_code}");
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
        // Increase max execution time for batch operations
        ini_set('max_execution_time', '300'); // 300 seconds = 5 minutes

        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
        }

        // Initialize WPUserGenerator for cleanup
        $userGenerator = new WPUserGenerator();

        // Clean up existing demo divisions if shouldClearData is enabled
        if ($this->shouldClearData()) {
            $this->debug("[DivisionDemoData] === Cleanup mode enabled - Deleting existing demo divisions ===");

            // STEP 1: Delete orphaned inactive employees (from deleted divisions)
            $orphaned_employees = $this->wpdb->get_results(
                "SELECT e.id FROM {$this->wpdb->prefix}app_agency_employees e
                 LEFT JOIN {$this->wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
                 WHERE d.id IS NULL AND e.status = 'inactive'"
            );
            if (!empty($orphaned_employees)) {
                $orphan_ids = array_column($orphaned_employees, 'id');
                $placeholders = implode(',', array_fill(0, count($orphan_ids), '%d'));
                $deleted_orphans = $this->wpdb->query(
                    $this->wpdb->prepare(
                        "DELETE FROM {$this->wpdb->prefix}app_agency_employees WHERE id IN ($placeholders)",
                        ...$orphan_ids
                    )
                );
                $this->debug("[DivisionDemoData] Deleted {$deleted_orphans} orphaned inactive employees");
            }

            // STEP 2: Enable hard delete temporarily untuk demo cleanup
            $original_settings = get_option('wp_agency_general_options', []);
            $cleanup_settings = array_merge($original_settings, ['enable_hard_delete_branch' => true]);
            update_option('wp_agency_general_options', $cleanup_settings);
            $this->debug("[DivisionDemoData] Enabled hard delete mode for cleanup");

            // Delete cabang divisions via Model (triggers HOOK for cascade cleanup)
            $cabang_divisions = $this->wpdb->get_results(
                "SELECT id FROM {$this->wpdb->prefix}app_agency_divisions WHERE type = 'cabang'",
                ARRAY_A
            );

            $deleted_divisions = 0;
            $divisionModel = new \WPAgency\Models\Division\DivisionModel();
            foreach ($cabang_divisions as $division) {
                if ($divisionModel->delete($division['id'])) {
                    $deleted_divisions++;
                }
            }
            $this->debug("[DivisionDemoData] Deleted {$deleted_divisions} cabang divisions (HOOK handles employees)");

            // Restore original settings
            update_option('wp_agency_general_options', $original_settings);
            $this->debug("[DivisionDemoData] Restored original delete mode");

            // Collect all division admin user IDs from DivisionUsersData
            $user_ids_to_delete = [];

            // Division users (pusat + cabang for each agency)
            foreach ($this->division_users as $agency_id => $divisions) {
                // Skip pusat deletion - only delete cabang users
                if (isset($divisions['cabang1'])) {
                    $user_ids_to_delete[] = $divisions['cabang1']['id'];
                }
                if (isset($divisions['cabang2'])) {
                    $user_ids_to_delete[] = $divisions['cabang2']['id'];
                }
            }

            $this->debug("[DivisionDemoData] User IDs to clean: " . json_encode($user_ids_to_delete));

            $deleted_users = $userGenerator->deleteUsers($user_ids_to_delete);
            $this->debug("[DivisionDemoData] Cleaned up {$deleted_users} existing demo users");
            $this->debug("Cleaned up {$deleted_users} users and {$deleted_divisions} divisions before generation");
        }

        // TAMBAHKAN DI SINI
        if (!$this->validate()) {
            throw new \Exception('Pre-generation validation failed');
        }

        $generated_count = 0;

        try {
            // Get all active agencies
            $agency_index = 1; // Start from 1 to match DivisionUsersData index (1-10)
            foreach ($this->agency_ids as $agency_id) {
                $agency = $this->agencyModel->find($agency_id);
                if (!$agency) {
                    $this->debug("Agency not found: {$agency_id}");
                    $agency_index++;
                    continue;
                }

                if (!isset($this->division_users[$agency_index])) {
                    $this->debug("No division admin users found for agency index {$agency_index} (ID: {$agency_id}), skipping...");
                    $agency_index++;
                    continue;
                }

                // Skip pusat division generation - now auto-created via wp_agency_agency_created HOOK
                // Pusat division is created by AutoEntityCreator when agency is created
                $this->debug("Pusat division for agency {$agency_id} should be auto-created via HOOK");

                // IMPORTANT: Verify pusat division exists before creating cabang
                $pusat_exists = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'pusat'",
                    $agency_id
                ));

                if (!$pusat_exists) {
                    $this->debug("WARNING: Pusat division NOT found for agency {$agency_id}! Hook may have failed. Skipping cabang generation.");
                    continue;
                }

                // Check for existing cabang divisions
                $existing_cabang_count = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND type = 'cabang'",
                    $agency_id
                ));

                if ($existing_cabang_count > 0) {
                    $this->debug("Cabang divisions exist for agency {$agency_id}, skipping...");
                } else {
                    $this->generateCabangDivisions($agency, $agency_index);
                    $generated_count++;
                }

                $agency_index++; // Increment for next agency
            }

            if ($generated_count === 0) {
                $this->debug('No new divisions were generated - all divisions already exist');
            } else {
                // Reset auto increment only if we added new data
                $this->wpdb->query(
                    "ALTER TABLE {$this->wpdb->prefix}app_agency_divisions AUTO_INCREMENT = " . 
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
     * Generate cabang divisions
     * Uses runtime flow simulation for full validation
     *
     * @param object $agency Agency object
     * @param int $agency_index Agency index (1-10) for DivisionUsersData lookup
     */
    private function generateCabangDivisions($agency, int $agency_index): void {
        // Generate 1-2 cabang per agency
        //$cabang_count = rand(1, 2);

        $cabang_count = 2; // Selalu buat 2 cabang karena sudah ada 2 user cabang

        $userGenerator = new WPUserGenerator();

        // Track used regencies for this agency to avoid duplicates
        $excluded_regencies = [$this->getRegencyIdByCode($agency->regency_code)];

        for ($i = 0; $i < $cabang_count; $i++) {
            // Get cabang admin user ID
            $cabang_key = 'cabang' . ($i + 1);
            if (!isset($this->division_users[$agency_index][$cabang_key])) {
                $this->debug("No admin user found for {$cabang_key} of agency index {$agency_index} (ID: {$agency->id}), skipping...");
                continue;
            }

            // Generate WordPress user untuk cabang with roles from DivisionUsersData
            $user_data = $this->division_users[$agency_index][$cabang_key];
            $wp_user_id = $userGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'roles' => $user_data['role']  // Use roles array from data
            ]);

            if (!$wp_user_id) {
                throw new \Exception("Failed to create WordPress user for division admin: {$user_data['display_name']}");
            }

            // Use the same province as the agency
            $provinsi_id = $this->getProvinceIdByCode($agency->provinsi_code);

            // Get random regency from the agency's province, excluding used ones
            $placeholders = implode(',', array_fill(0, count($excluded_regencies), '%d'));
            $query = "SELECT id FROM {$this->wpdb->prefix}wi_regencies
                     WHERE province_id = %d AND id NOT IN (" . $placeholders . ")
                     ORDER BY RAND() LIMIT 1";
            $params = array_merge([$provinsi_id], $excluded_regencies);
            $regency = $this->wpdb->get_row($this->wpdb->prepare($query, $params));

            if (!$regency) {
                $this->debug("No more available regencies in province {$provinsi_id} for agency {$agency->id}, skipping cabang " . ($i + 1));
                continue;
            }

            $regency_id = (int) $regency->id;
            $excluded_regencies[] = $regency_id;
            $regency_code = $this->getRegencyCodeById($regency_id);

            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            $division_data = [
                'name' => sprintf('UPT %s', $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($agency->name, $cabang_key),
                'provinsi_code' => $agency->provinsi_code,
                'regency_code' => $regency_code,
            ];

            // Create division via runtime flow (validates + uses production code + triggers hooks)
            $division_id = $this->createDivisionViaRuntimeFlow(
                $agency->id,
                $division_data,
                ['user_id' => $wp_user_id],  // Pass existing user_id
                $agency->user_id  // created_by
            );

            if (!$division_id) {
                throw new \Exception("Failed to create cabang division for agency: {$agency->id}");
            }

            $this->division_ids[] = $division_id;
            $this->debug("Created cabang division for agency {$agency->name} in {$regency_name}");
        }
    }

    /**
     * Create division via runtime flow simulation
     * Replicates EXACT logic from DivisionController::store() without AJAX/nonce
     *
     * @param int $agency_id Agency ID
     * @param array $division_data Division fields (name, type, nitku, etc)
     * @param array $admin_data Admin user fields (user_id if already created)
     * @param int $created_by User ID who creates the division
     * @return int Division ID
     * @throws \Exception If validation fails or creation fails
     */
    private function createDivisionViaRuntimeFlow(
        int $agency_id,
        array $division_data,
        array $admin_data,
        int $created_by
    ): int {
        // Initialize validator and model (same as Controller)
        $validator = new \WPAgency\Validators\Division\DivisionValidator();
        $model = new \WPAgency\Models\Division\DivisionModel();

        // Step 1: Check agency_id (line 809-812 from store())
        if (!$agency_id) {
            throw new \Exception('ID Agency tidak valid');
        }

        // Step 2: Check permission (line 817-820 from store())
        if (!$validator->canCreateDivision($agency_id)) {
            throw new \Exception('Anda tidak memiliki izin untuk menambah cabang');
        }

        // Step 3: Sanitize input (line 823-836 from store())
        $data = [
            'agency_id' => $agency_id,
            'name' => sanitize_text_field($division_data['name'] ?? ''),
            'type' => sanitize_text_field($division_data['type'] ?? ''),
            'nitku' => sanitize_text_field($division_data['nitku'] ?? ''),
            'postal_code' => sanitize_text_field($division_data['postal_code'] ?? ''),
            'latitude' => (float)($division_data['latitude'] ?? 0),
            'longitude' => (float)($division_data['longitude'] ?? 0),
            'address' => sanitize_text_field($division_data['address'] ?? ''),
            'phone' => sanitize_text_field($division_data['phone'] ?? ''),
            'email' => sanitize_email($division_data['email'] ?? ''),
            'provinsi_code' => $division_data['provinsi_code'] ?? '',
            'regency_code' => $division_data['regency_code'] ?? '',
            'created_by' => $created_by,
            'status' => 'active'
        ];

        // Step 4: Validate division type (line 869-874 from store())
        $type_validation = $validator->validateDivisionTypeCreate($data['type'], $agency_id);
        if (!$type_validation['valid']) {
            throw new \Exception($type_validation['message']);
        }

        // Step 5: Handle user (line 876-889 from store())
        if (!empty($admin_data['user_id'])) {
            // User already created (demo data dengan WPUserGenerator)
            $data['user_id'] = $admin_data['user_id'];
            $this->debug("Using existing user ID {$data['user_id']} for division");
        } elseif (!empty($admin_data['email'])) {
            // Create new user (runtime flow untuk production simulation)
            // This will be handled by HOOK (wp_agency_division_created)
            $data['admin_username'] = sanitize_user($admin_data['username']);
            $data['admin_email'] = sanitize_email($admin_data['email']);
            $data['admin_firstname'] = sanitize_text_field($admin_data['firstname'] ?? '');
            $data['admin_lastname'] = sanitize_text_field($admin_data['lastname'] ?? '');

            // Use agency user temporarily (hook will update to new user)
            $agency = $this->agencyModel->find($agency_id);
            $data['user_id'] = $agency->user_id;
        }

        // Step 6: Save division (line 892-895 from store())
        $this->debug("Attempting to create division with data: " . print_r($data, true));

        $division_id = $model->create($data);

        $this->debug("Model->create() returned: " . ($division_id ? $division_id : 'FALSE/0'));

        if (!$division_id) {
            // Get last database error
            global $wpdb;
            $last_error = $wpdb->last_error ? $wpdb->last_error : 'No database error';
            $this->debug("Division creation FAILED. DB Error: " . $last_error);
            throw new \Exception('Gagal menambah cabang. DB Error: ' . $last_error);
        }

        // Cache invalidation handled by Model

        $this->debug("Created division via runtime flow (ID: {$division_id}) for agency {$agency_id}");

        return $division_id;
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
