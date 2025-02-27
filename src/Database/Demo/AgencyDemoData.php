<?php
/**
 * Agency Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/AgencyDemoData.php
 * 
 * Description: Generate agency demo data dengan:
 *              - Data perusahaan dengan format yang valid
 *              - Integrasi dengan WordPress user
 *              - Data wilayah dari Provinces/Regencies
 *              - Validasi dan tracking data unik
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\AgencyUsersData;
use WPAgency\Database\Demo\Data\DivisionUsersData;
use WPAgency\Controllers\AgencyController;

defined('ABSPATH') || exit;

class AgencyDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private static $agency_ids = [];
    private static $user_ids = [];
    private static $used_emails = [];
    public $used_names = [];
    public $used_npwp = [];
    public $used_nib = [];
    protected $agency_users = [];
    private $agencyController;

    // Data statis agency
    private static $agencies = [
        ['id' => 1, 'name' => 'PT Maju Bersama', 'provinsi_id' => '16', 'regency_id' => '34'],
        ['id' => 2, 'name' => 'CV Teknologi Nusantara'],
        ['id' => 3, 'name' => 'PT Sinar Abadi'],
        ['id' => 4, 'name' => 'PT Global Teknindo'],
        ['id' => 5, 'name' => 'CV Mitra Solusi'],
        ['id' => 6, 'name' => 'PT Karya Digital'],
        ['id' => 7, 'name' => 'PT Bumi Perkasa'],
        ['id' => 8, 'name' => 'CV Cipta Kreasi'],
        ['id' => 9, 'name' => 'PT Meta Inovasi'],
        ['id' => 10, 'name' => 'PT Delta Sistem']
    ];

    /**
     * Constructor to initialize properties
     */
    public function __construct() {
        parent::__construct();
        $this->agency_users = AgencyUsersData::$data;
        $this->agencyController = new AgencyController();
    }

    /**
     * Validasi sebelum generate data
     */
    protected function validate(): bool {
        try {
            // Validasi tabel provinces & regencies
            $provinces_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_provinces'"
            );
            if (!$provinces_exist) {
                throw new \Exception('Tabel provinces tidak ditemukan');
            }

            // Get agency users mapping
            if (empty($this->agency_users)) {
                throw new \Exception('Agency users not found');
            }

            $regencies_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
            );
            if (!$regencies_exist) {
                throw new \Exception('Tabel regencies tidak ditemukan');
            }

            // Cek data provinces & regencies tersedia
            $province_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}wi_provinces"
            );
            if ($province_count == 0) {
                throw new \Exception('Data provinces kosong');
            }

            $regency_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}wi_regencies"
            );
            if ($regency_count == 0) {
                throw new \Exception('Data regencies kosong');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            return;
        }

        // Inisialisasi WPUserGenerator dan simpan reference ke static data
        $userGenerator = new WPUserGenerator();

        foreach (self::$agencies as $agency) {
            try {
                // 1. Cek existing agency
                $existing_agency = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT c.* FROM {$this->wpdb->prefix}app_agencies c 
                         INNER JOIN {$this->wpdb->users} u ON c.user_id = u.ID 
                         WHERE c.id = %d",
                        $agency['id']
                    )
                );

                if ($existing_agency) {
                    if ($this->shouldClearData()) {
                        // Delete existing agency if shouldClearData is true
                        $this->wpdb->delete(
                            $this->wpdb->prefix . 'app_agencies',
                            ['id' => $agency['id']],
                            ['%d']
                        );
                        $this->debug("Deleted existing agency with ID: {$agency['id']}");
                    } else {
                        $this->debug("Agency exists with ID: {$agency['id']}, skipping...");
                        continue;
                    }
                }

                // 2. Cek dan buat WP User jika belum ada
                $wp_user_id = 1 + $agency['id'];  // Sesuai dengan indeks di AgencyUsersData
                
                // Ambil data user dari static array
                $user_data = $this->agency_users[$agency['id'] - 1];
                $user_id = $userGenerator->generateUser([
                    'id' => $user_data['id'],
                    'username' => $user_data['username'],
                    'display_name' => $user_data['display_name'],
                    'role' => 'agency'
                ]);

                if (!$user_id) {
                    throw new \Exception("Failed to create WordPress user for agency: {$agency['name']}");
                }

                // Store user_id untuk referensi
                self::$user_ids[$agency['id']] = $wp_user_id;

                // 3. Generate agency data baru
                if (isset($agency['provinsi_id'])) {
                    $provinsi_id = (int)$agency['provinsi_id'];
                    // Pastikan regency sesuai dengan provinsi ini
                    $regency_id = isset($agency['regency_id']) ? 
                        (int)$agency['regency_id'] : 
                        $this->getRandomRegencyId($provinsi_id);
                } else {
                    // Get random valid province-regency pair
                    $provinsi_id = $this->getRandomProvinceId();
                    $regency_id = $this->getRandomRegencyId($provinsi_id);
                }

                // Validate location relationship
                if (!$this->validateLocation($provinsi_id, $regency_id)) {
                    throw new \Exception("Invalid province-regency relationship: Province {$provinsi_id}, Regency {$regency_id}");
                }

                if ($this->shouldClearData()) {
                    // Delete existing agency if user WP not  exists
                    $this->wpdb->delete(
                        $this->wpdb->prefix . 'app_agencies',
                        ['id' => $agency['id']],
                        ['%d']
                    );
                    
                    $this->debug("Deleted existing agency with ID: {$agency['id']}");
                }

                // Prepare agency data according to schema
                $agency_data = [
                    'id' => $agency['id'],
                    'code' => $this->agencyModel->generateAgencyCode(),
                    'name' => $agency['name'],
                    'npwp' => $this->generateNPWP(),
                    'nib' => $this->generateNIB(),
                    'status' => 'active',
                    'provinsi_id' => $provinsi_id ?: null,
                    'regency_id' => $regency_id ?: null,
                    'user_id' => $wp_user_id,
                    'created_by' => 1,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ];

                // Use createDemoAgency instead of create
                if (!$this->agencyController->createDemoAgency($agency_data)) {
                    throw new \Exception("Failed to create agency with fixed ID");
                }

                // Track agency ID
                self::$agency_ids[] = $agency['id'];

                $this->debug("Created agency: {$agency['name']} with fixed ID: {$agency['id']} and WP User ID: {$wp_user_id}");

            } catch (\Exception $e) {
                $this->debug("Error processing agency {$agency['name']}: " . $e->getMessage());
                throw $e;
            }
        }

        // Add cache handling after bulk generation
        foreach (self::$agency_ids as $agency_id) {
            $this->cache->invalidateAgencyCache($agency_id);
            $this->cache->delete('agency_total_count', get_current_user_id());
            $this->cache->invalidateDataTableCache('agency_list');
        }

        // Reset auto_increment
        $this->wpdb->query(
            "ALTER TABLE {$this->wpdb->prefix}app_agencies AUTO_INCREMENT = 211"
        );
    }

    /**
     * Get array of generated agency IDs
     */
    public function getAgencyIds(): array {
        return self::$agency_ids;
    }

    /**
     * Get array of generated user IDs
     */
    public function getUserIds(): array {
        return self::$user_ids;
    }
}
