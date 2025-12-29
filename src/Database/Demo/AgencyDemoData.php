<?php
/**
 * Agency Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.9
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/AgencyDemoData.php
 *
 * Description: Generate agency demo data dengan:
 *              - Data perusahaan dengan format yang valid
 *              - Integrasi dengan WordPress user
 *              - Data wilayah dari Provinces/Regencies (ID-based, not code-based)
 *              - Validasi dan tracking data unik
 *              - Division pusat inherit user dari agency (same user)
 *
 * Changelog:
 * 1.0.9 - 2025-11-04 (FIX: Use province_id/regency_id instead of codes)
 * - CRITICAL FIX: Changed from provinsi_code/regency_code to province_id/regency_id
 * - Matches current AgencysDB schema (uses ID-based FKs, not codes)
 * - Updated location data retrieval to use IDs directly
 * - Removed code-based helper method calls
 * - Fixes "Unknown column 'provinsi_code'" error
 *
 * 1.0.8 - 2025-11-01 (FIX: Static Entity IDs via Hook)
 * - CRITICAL FIX: Added filter hook to inject static agency IDs (1-10)
 * - Now uses wp_agency_before_insert hook for static ID injection
 * - Agencies created with predictable IDs matching AgencyUsersData (1-10)
 * - Passes '_demo_entity_id' to hook for ID injection
 * - Fixes inconsistency: defined IDs 1-10 but created with auto-increment
 *
 * 2.2.0 - 2025-10-22 (FIX: Division Pusat Inherits Agency User)
 * - REVERTED: Division pusat now INHERITS user from agency (same user)
 * - Removed createDivisionPusatForDemo() method (no longer needed)
 * - Division pusat created automatically via HOOK (AutoEntityCreator)
 * - Agency user (130-139) = Division pusat user (same) ✓
 * - Employee auto-created via division hook with same user ✓
 * - Total demo users: 10 (1 user per agency for all entities)
 * - Pattern matches production: 1 user → 1 agency + 1 division + 1 employee
 *
 * 2.1.0 - 2025-01-22 (FIX: Division Pusat with DivisionUsersData) - REVERTED
 * - Division pusat used separate user from DivisionUsersData (ID 140-169)
 * - This was INCORRECT for runtime flow pattern
 *
 * 2.0.0 - 2025-01-22 (Task-2067 Runtime Flow)
 * - BREAKING: Completely rewritten to use runtime flow pattern
 * - Removed dependency on AgencyController (production pollution)
 * - Now uses AgencyValidator + AgencyModel directly
 * - Uses createAgencyViaRuntimeFlow() method
 * - Full validation via AgencyValidator::validateForm()
 * - Hooks properly fired (wp_agency_agency_created)
 * - Division pusat auto-created via hook
 * - Employee auto-created via hook
 * - HOOK-based cleanup with cascade delete
 * - Follows wp-customer pattern exactly
 * - Demo generation now serves as automated testing tool
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\AgencyUsersData;
use WPAgency\Database\Demo\Data\DivisionUsersData;
use WPAgency\Validators\AgencyValidator;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;

defined('ABSPATH') || exit;

class AgencyDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private static $agency_ids = [];
    private static $user_ids = [];
    private static $used_emails = [];
    public $used_names = [];
    protected $agency_users = [];
    protected $division_users = [];
    protected $agencyValidator;
    protected $agencyModel;
    protected $divisionModel;

    // Data statis agency - disesuaikan dengan provinsi yang memiliki data regencies
    private static $agencies = [
        ['id' => 1, 'name' => 'Disnaker Provinsi Aceh'],
        ['id' => 2, 'name' => 'Disnaker Provinsi Sumatera Utara'],
        ['id' => 3, 'name' => 'Disnaker Provinsi Sumatera Barat'],
        ['id' => 4, 'name' => 'Disnaker Provinsi Banten'],
        ['id' => 5, 'name' => 'Disnaker Provinsi Jawa Barat'],
        ['id' => 6, 'name' => 'Disnaker Provinsi Jawa Tengah'],
        ['id' => 7, 'name' => 'Disnaker Provinsi DKI Jakarta'],
        ['id' => 8, 'name' => 'Disnaker Provinsi Maluku'],
        ['id' => 9, 'name' => 'Disnaker Provinsi Papua'],
        ['id' => 10, 'name' => 'Disnaker Provinsi Sulawesi Selatan']
    ];

    /**
     * Constructor to initialize properties
     */
    public function __construct() {
        parent::__construct();
        $this->agency_users = AgencyUsersData::$data;
        $this->division_users = DivisionUsersData::$data;

        // Initialize production dependencies (Task-2067)
        $this->agencyValidator = new AgencyValidator();
        $this->agencyModel = new AgencyModel();
        $this->divisionModel = new DivisionModel();
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

    /**
     * Create agency via runtime flow (simulating real production flow)
     *
     * This method replicates the exact flow that happens in production:
     * 1. Validate data via AgencyValidator::validateForm()
     * 2. Create agency via AgencyModel::create()
     * 3. Fire wp_agency_agency_created HOOK (auto-creates division pusat + employee)
     * 4. Cache invalidation (handled by Model)
     *
     * NO special demo methods, NO validation bypass, NO production pollution
     *
     * @param array $agency_data Agency data
     * @return int|null Agency ID or null on failure
     * @throws \Exception On validation or creation error
     */
    private function createAgencyViaRuntimeFlow(array $agency_data): ?int {
        // 1. Validate data using production validator
        $validation_errors = $this->agencyValidator->validateForm($agency_data);

        if (!empty($validation_errors)) {
            $error_msg = 'Validation failed: ' . implode(', ', $validation_errors);
            $this->debug($error_msg);
            throw new \Exception($error_msg);
        }

        // 2. Create agency using production Model::create()
        // This triggers wp_agency_agency_created HOOK automatically
        $agency_id = $this->agencyModel->create($agency_data);

        if (!$agency_id) {
            throw new \Exception('Failed to create agency via Model');
        }

        $this->debug("✓ Agency created: ID={$agency_id}, Name={$agency_data['name']}");

        // 3. Cache invalidation handled automatically by Model
        // 4. HOOK fired automatically by Model
        //    → AutoEntityCreator::handleAgencyCreated()
        //      → Creates division pusat
        //        → HOOK: wp_agency_division_created
        //          → Creates employee

        return $agency_id;
    }

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            return;
        }

        // PHASE 1: Cleanup existing demo data (if shouldClearData)
        if ($this->shouldClearData()) {
            $this->cleanupDemoData();
        }

        // PHASE 2: Generate agencies via runtime flow
        $this->generateAgenciesViaRuntimeFlow();
    }

    /**
     * Cleanup demo data using HOOK-based cascade delete
     */
    private function cleanupDemoData(): void {
        $this->debug('Starting cleanup of existing demo data...');

        // 1. Enable hard delete temporarily (for complete cleanup)
        $original_settings = get_option('wp_agency_general_options', []);
        $cleanup_settings = array_merge($original_settings, [
            'enable_hard_delete_branch' => true
        ]);
        update_option('wp_agency_general_options', $cleanup_settings);

        // 2. Get all demo agencies (reg_type = 'generate')
        $demo_agencies = $this->wpdb->get_col(
            "SELECT id FROM {$this->wpdb->prefix}app_agencies
             WHERE reg_type = 'generate'
             ORDER BY id ASC"
        );

        if (!empty($demo_agencies)) {
            $this->debug('Found ' . count($demo_agencies) . ' demo agencies to delete');

            // 3. Delete via Model (triggers HOOK cascade)
            foreach ($demo_agencies as $agency_id) {
                try {
                    $this->agencyModel->delete($agency_id);
                    // → Triggers wp_agency_agency_deleted hook
                    //   → Cascade deletes divisions
                    //     → Cascade deletes employees

                    $this->debug("✓ Deleted agency ID={$agency_id} (with cascade)");
                } catch (\Exception $e) {
                    $this->debug("✗ Failed to delete agency ID={$agency_id}: " . $e->getMessage());
                }
            }
        }

        // 4. Clean demo users
        $user_ids = array_column($this->agency_users, 'id');
        $userGenerator = new WPUserGenerator();
        $userGenerator->deleteUsers($user_ids);
        $this->debug('✓ Cleaned ' . count($user_ids) . ' demo users');

        // 5. Restore original settings
        update_option('wp_agency_general_options', $original_settings);

        // 6. CRITICAL: Clear ALL cache to avoid validation errors
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $this->debug('✓ Flushed WP cache');
        }

        // Clear agency cache specifically
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%agency%cache%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%name_exists%'");
        $this->debug('✓ Cleared agency validation cache');

        $this->debug('✓ Cleanup completed');
    }

    /**
     * Generate agencies via runtime flow
     */
    private function generateAgenciesViaRuntimeFlow(): void {
        $this->debug('Starting agency generation via runtime flow...');

        // Add filter hook to inject static entity IDs for demo data
        add_filter('wp_agency_before_insert', function($insert_data, $data) {
            if (isset($data['_demo_entity_id'])) {
                $insert_data['id'] = $data['_demo_entity_id'];
                error_log("AgencyDemoData: Injecting static agency ID: {$insert_data['id']}");
            }
            return $insert_data;
        }, 10, 2);

        // Register AutoEntityCreator hooks if not already registered
        // In production (web context), hooks are registered via wp-agency.php plugins_loaded
        // In WP-CLI context, plugins_loaded may have passed, so we register here
        global $wp_filter;
        $autoEntityCreator = null;

        if (!isset($wp_filter['wp_agency_agency_created']) ||
            !isset($wp_filter['wp_agency_division_created'])) {
            $autoEntityCreator = new \WPAgency\Handlers\AutoEntityCreator();
            add_action('wp_agency_agency_created', [$autoEntityCreator, 'handleAgencyCreated'], 10, 2);
            add_action('wp_agency_division_created', [$autoEntityCreator, 'handleDivisionCreated'], 10, 2);
            $this->debug('Registered AutoEntityCreator hooks (WP-CLI context)');
        } else {
            $this->debug('Using existing AutoEntityCreator hooks (production context)');
        }

        $userGenerator = new WPUserGenerator();
        $generated_count = 0;
        $failed_count = 0;

        foreach (self::$agencies as $index => $agency) {
            try {
                // 1. Get user data for this agency
                $user_data = $this->agency_users[$index] ?? null;
                if (!$user_data) {
                    throw new \Exception("User data not found for agency index {$index}");
                }

                // 2. Create WP User via wp_insert_user() (with static ID)
                $user_id = $userGenerator->generateUser([
                    'id' => $user_data['id'],
                    'username' => $user_data['username'],
                    'display_name' => $user_data['display_name'],
                    'roles' => $user_data['roles']
                ]);

                $this->debug("✓ User created: ID={$user_id}, Username={$user_data['username']}");

                // 3. Get location IDs (not codes)
                $province_name = $this->mapAgencyNameToProvince($agency['name']);
                if ($province_name) {
                    $province_id = $this->getProvinceIdByName($province_name);
                    $regency_id = $this->getRandomRegencyId($province_id);
                } else {
                    // Fallback to random
                    $province_id = $this->getRandomProvinceId();
                    $regency_id = $this->getRandomRegencyId($province_id);
                }

                // Validate location relationship
                if (!$this->validateLocation($province_id, $regency_id)) {
                    throw new \Exception("Invalid province-regency relationship");
                }

                // 4. Prepare agency data with static ID for demo data
                $agency_data = [
                    'name' => $agency['name'],
                    'status' => 'active',
                    'province_id' => $province_id,
                    'regency_id' => $regency_id,
                    'user_id' => $user_id,
                    'reg_type' => 'generate',  // Mark as demo data
                    'created_by' => $user_id,
                    '_demo_entity_id' => $agency['id']  // Pass static ID for hook injection
                ];

                // 5. Create agency via runtime flow
                // NOTE: Hook wp_agency_agency_created will auto-create division pusat
                // Division pusat inherits user from agency (same user for both)
                $agency_id = $this->createAgencyViaRuntimeFlow($agency_data);

                $this->debug("✓ Agency created: ID={$agency_id}, Name={$agency_data['name']}");

                // 6. Division pusat will be created automatically by HOOK
                // wp_agency_agency_created → AutoEntityCreator::handleAgencyCreated()
                // Division pusat inherits user from agency (no separate user)

                self::$agency_ids[] = $agency_id;
                $generated_count++;
                $this->debug("✓ Agency #{$generated_count} completed (division pusat auto-created by hook)");

            } catch (\Exception $e) {
                $failed_count++;
                $this->debug("✗ Failed to create agency: " . $e->getMessage());

                // Continue with next agency instead of stopping
                continue;
            }
        }

        $this->debug("Generation completed: {$generated_count} succeeded, {$failed_count} failed");

        // Remove filter after generation
        remove_all_filters('wp_agency_before_insert', 10);

        // Remove hooks only if we registered them (WP-CLI context)
        if ($autoEntityCreator !== null) {
            remove_action('wp_agency_agency_created', [$autoEntityCreator, 'handleAgencyCreated'], 10);
            remove_action('wp_agency_division_created', [$autoEntityCreator, 'handleDivisionCreated'], 10);
            $this->debug('Removed AutoEntityCreator hooks (WP-CLI cleanup)');
        } else {
            $this->debug('Kept production AutoEntityCreator hooks active');
        }
    }

    /**
     * Map agency name to province name
     */
    private function mapAgencyNameToProvince(string $agency_name): ?string {
        $map = [
            'Disnaker Provinsi Aceh' => 'Aceh',
            'Disnaker Provinsi Sumatera Utara' => 'Sumatera Utara',
            'Disnaker Provinsi Sumatera Barat' => 'Sumatera Barat',
            'Disnaker Provinsi Banten' => 'Banten',
            'Disnaker Provinsi Jawa Barat' => 'Jawa Barat',
            'Disnaker Provinsi Jawa Tengah' => 'Jawa Tengah',
            'Disnaker Provinsi DKI Jakarta' => 'DKI Jakarta',
            'Disnaker Provinsi Maluku' => 'Maluku',
            'Disnaker Provinsi Papua' => 'Papua',
            'Disnaker Provinsi Sulawesi Selatan' => 'Sulawesi Selatan'
        ];

        return $map[$agency_name] ?? null;
    }

    /**
     * Get province ID by name
     */
    private function getProvinceIdByName(string $province_name): ?int {
        $province = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}wi_provinces WHERE name = %s",
            $province_name
        ));

        return $province ? (int) $province->id : null;
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
