<?php
/**
 * Division Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Division
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Division/DivisionController.php
 *
 * Description: Controller untuk mengelola data cabang.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk DataTables.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial implementation
 * - Added CRUD endpoints
 * - Added DataTables integration
 * - Added permission checks
 * - Added cache support
 */

namespace WPAgency\Controllers\Division;

use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Division\JurisdictionModel;
use WPAgency\Validators\Division\DivisionValidator;
use WPAgency\Validators\Division\JurisdictionValidator;
use WPAgency\Cache\AgencyCacheManager;

class DivisionController {
    private AgencyModel $agencyModel;
    private DivisionModel $model;
    private JurisdictionModel $jurisdictionModel;
    private DivisionValidator $validator;
    private JurisdictionValidator $jurisdictionValidator;
    private AgencyCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/division.log';

    public function __construct() {
        $this->agencyModel = new AgencyModel();
        $this->model = new DivisionModel();
        $this->jurisdictionModel = new JurisdictionModel();
        $this->validator = new DivisionValidator();
        $this->jurisdictionValidator = new JurisdictionValidator();
        $this->cache = new AgencyCacheManager();

        // Initialize log file inside plugin directory
        $this->log_file = WP_AGENCY_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_division_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_division_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_agency_divisions', [$this, 'getAgencyDivisions']);

        // Register other endpoints
        add_action('wp_ajax_get_division', [$this, 'show']);
        add_action('wp_ajax_create_division', [$this, 'store']);
        add_action('wp_ajax_update_division', [$this, 'update']);
        add_action('wp_ajax_delete_division', [$this, 'delete']);
        add_action('wp_ajax_validate_division_type_change', [$this, 'validateDivisionTypeChange']);
        add_action('wp_ajax_create_division_button', [$this, 'createDivisionButton']);

        add_action('wp_ajax_validate_division_access', [$this, 'validateDivisionAccess']);

    }

    /**
     * Validate agency access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateDivisionAccess() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $division_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$division_id) {
                throw new \Exception('Invalid division ID');
            }

            // Gunakan validator langsung
            $access = $this->validator->validateAccess($division_id);
            
            if (!$access['has_access']) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki akses ke agency ini', 'wp-agency'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            wp_send_json_success([
                'message' => 'Akses diberikan',
                'agency_id' => $agency_id,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'error'
            ]);
        }
    }

    public function createDivisionButton() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            
            if (!$agency_id) {
                throw new \Exception('ID Agency tidak valid');
            }

            $agency = $this->agencyModel->find($agency_id);
            if (!$agency) {
                throw new \Exception('Agency tidak ditemukan');
            }

            if (!$this->validator->canCreateDivision($agency_id)) {
                wp_send_json_success(['button' => '']);
                return;
            }

            $button = '<button type="button" class="button button-primary" id="add-division-btn">';
            $button .= '<span class="dashicons dashicons-plus-alt"></span>';
            $button .= __('Tambah Division', 'wp-agency');
            $button .= '</button>';

            wp_send_json_success([
                'button' => $button
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function validateDivisionTypeChange() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $division_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $new_type = isset($_POST['new_type']) ? sanitize_text_field($_POST['new_type']) : '';
            
            if (!$division_id || !$new_type) {
                throw new \Exception('Missing required parameters');
            }

            // Get current division data - cek cache terlebih dahulu
            $cached_division = $this->cache->get('division', $division_id);
            
            if ($cached_division !== null) {
                $division = $cached_division;
            } else {
                // Jika tidak ada dalam cache, ambil dari database
                $division = $this->model->find($division_id);
                
                // Simpan ke cache jika ada
                if ($division) {
                    $this->cache->set('division', $division, $this->cache::getCacheExpiry(), $division_id);
                }
            }

            if (!$division) {
                throw new \Exception('Division not found');
            }

            // Jika tidak mengubah tipe (tipe baru sama dengan tipe lama), langsung kembalikan valid
            if ($division->type === $new_type) {
                wp_send_json_success(['message' => 'No change in division type']);
                return;
            }

            // Untuk perubahan ke tipe 'pusat'
            if ($new_type === 'pusat') {
                // Cek cache untuk pusat division yang ada
                $existing_pusat = $this->cache->get('agency_pusat_division', $division->agency_id);
                
                if ($existing_pusat === null) {
                    // Jika tidak ada dalam cache, ambil dari database
                    $existing_pusat = $this->model->findPusatByAgency($division->agency_id);
                    
                    // Simpan ke cache jika ada
                    if ($existing_pusat) {
                        $this->cache->set('agency_pusat_division', $existing_pusat, $this->cache::getCacheExpiry(), $division->agency_id);
                    }
                }
                
                // Jika sudah ada pusat division dan bukan division yang sedang diedit
                if ($existing_pusat && $existing_pusat->id !== $division_id) {
                    wp_send_json_error([
                        'message' => sprintf(
                            'Agency sudah memiliki kantor pusat: %s. Tidak dapat mengubah cabang ini menjadi kantor pusat.',
                            $existing_pusat->name
                        ),
                        'original_type' => $division->type
                    ]);
                    return;
                }
            }
            
            // Untuk perubahan dari 'pusat' ke 'cabang'
            if ($division->type === 'pusat' && $new_type === 'cabang') {
                // Cek cache untuk jumlah kantor pusat
                $pusat_count = $this->cache->get('agency_pusat_count', $division->agency_id);
                
                if ($pusat_count === null) {
                    // Jika tidak ada dalam cache, hitung dari database
                    $pusat_count = $this->model->countPusatByAgency($division->agency_id);
                    
                    // Simpan ke cache
                    $this->cache->set('agency_pusat_count', $pusat_count, $this->cache::getCacheExpiry(), $division->agency_id);
                }
                
                // Jika ini adalah satu-satunya kantor pusat
                if ($pusat_count <= 1) {
                    wp_send_json_error([
                        'message' => 'Tidak dapat mengubah tipe menjadi cabang karena ini adalah satu-satunya kantor pusat.',
                        'original_type' => $division->type
                    ]);
                    return;
                }
            }

            // Jika semua validasi lulus
            wp_send_json_success(['message' => 'Valid']);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Gunakan wp_upload_dir() untuk mendapatkan writable directory
        $upload_dir = wp_upload_dir();
        $plugin_log_dir = $upload_dir['basedir'] . '/wp-agency/logs';
        
        // Update log file path dengan format yang lebih informatif
        $this->log_file = $plugin_log_dir . '/agency-' . date('Y-m') . '.log';

        // Buat direktori jika belum ada
        if (!file_exists($plugin_log_dir)) {
            if (!wp_mkdir_p($plugin_log_dir)) {
                // Jika gagal, gunakan sys_get_temp_dir sebagai fallback
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-agency.log';
                error_log('Failed to create log directory in uploads: ' . $plugin_log_dir);
                return;
            }

            // Protect directory dengan .htaccess
            file_put_contents($plugin_log_dir . '/.htaccess', 'deny from all');
            chmod($plugin_log_dir, 0755);
        }

        // Buat file log jika belum ada
        if (!file_exists($this->log_file)) {
            if (@touch($this->log_file)) {
                chmod($this->log_file, 0644);
            } else {
                error_log('Failed to create log file: ' . $this->log_file);
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-agency.log';
                return;
            }
        }

        // Double check writability
        if (!is_writable($this->log_file)) {
            error_log('Log file not writable: ' . $this->log_file);
            $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-agency.log';
        }
    }

    /**
     * Log debug messages to file
     */
    private function debug_log($message): void {
        // Hanya jalankan jika WP_DEBUG aktif
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";

        // Coba tulis ke file
        $written = @error_log($log_message, 3, $this->log_file);
        
        // Jika gagal, log ke default WordPress debug log
        if (!$written) {
            error_log('WP Agency Plugin: ' . $log_message);
        }
    }

    private function generateActionButtons($division) {
        $current_user_id = get_current_user_id();
        $agency = $this->agencyModel->find($division->agency_id);
        $actions = '';
        $relation = $this->validator->getUserRelation($division->id);
        if (!$this->validator->canViewDivision($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button view-division" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $division->id,
                __('Lihat', 'wp-agency')
            );
        }

        if ($this->validator->canEditDivision($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-division" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $division->id,
                __('Edit', 'wp-agency')
            );
        }

        if ($this->validator->canDeleteDivision($relation)) {
            $type_validation = $this->validator->validateDivisionTypeDelete($division->id);
            if ($type_validation['valid']) {
                $actions .= sprintf(
                    '<button type="button" class="button delete-division" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $division->id,
                    __('Hapus', 'wp-agency')
                );
            }
        }

        return $actions;
    }
    
    public function handleDataTableRequest() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $columns = ['code', 'name', 'admin_name', 'type', 'jurisdictions', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'code';
            if ($orderBy === 'actions') {
                $orderBy = 'code';
            }

            // Dapatkan role/capability user saat ini
            $access = $this->validator->validateAccess(0); 

            // Check cache first
            $cached_result = $this->cache->getDataTableCache(
                'division_list',
                $access['access_type'],
                $start, 
                $length,
                $search,
                $orderBy,
                $orderDir,
                ['agency_id' => $agency_id]
            );

            if ($cached_result) {
                wp_send_json($cached_result);
                return;
            }

            // Get fresh data
            $result = $this->model->getDataTableData(
                $agency_id,
                $start,
                $length, 
                $search,
                $orderBy,
                $orderDir
            );

            if (!$result) {
                throw new \Exception('No data returned from model');
            }

            $data = [];
            foreach ($result['data'] as $division) {
                // Validate access
                $relation = $this->validator->getUserRelation($division->id);
                if (!$this->validator->canViewDivision($relation)) {
                    continue;
                }

                // Get admin name
                $admin_name = '-';
                if (!empty($division->user_id)) {
                    $user = get_userdata($division->user_id);
                    if ($user) {
                        $first_name = $user->first_name;
                        $last_name = $user->last_name;
                        if ($first_name || $last_name) {
                            $admin_name = trim($first_name . ' ' . $last_name);
                        } else {
                            $admin_name = $user->display_name;
                        }
                    }
                }

                $data[] = [
                    'id' => $division->id,
                    'code' => esc_html($division->code),
                    'name' => esc_html($division->name),
                    'admin_name' => esc_html($admin_name),
                    'type' => esc_html($division->type),
                    'jurisdictions' => esc_html($division->jurisdictions ?? '-'),
                    'actions' => $this->generateActionButtons($division)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data,
            ];

            // Dapatkan role/capability user saat ini
            $access = $this->validator->validateAccess(0); 

            // Cache the result
            $this->cache->setDataTableCache(
                'division_list',
                $access['access_type'],
                $start,
                $length, 
                $search,
                $orderBy,
                $orderDir,
                $response,
                ['agency_id' => $agency_id]
            );

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 400);
        }

    }

    /**
     * Implementation in update() method
     */
    public function update() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid division ID');
            }

            // Get existing division data
            $division = $this->model->find($id);
            if (!$division) {
                throw new \Exception('Division not found');
            }

            $relation = $this->validator->getUserRelation($division->id);
            if (!$this->validator->canEditDivision($relation)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini.');
            }

           // Validate input
           $data = array_filter([
               'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : null,
               'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : null,
               'nitku' => isset($_POST['nitku']) ? sanitize_text_field($_POST['nitku']) : null,
               'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : null,
               'latitude' => isset($_POST['latitude']) ? (float)$_POST['latitude'] : null,
               'longitude' => isset($_POST['longitude']) ? (float)$_POST['longitude'] : null,
               'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : null,
               'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
               'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : null,
               'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
               'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
           ], function($value) { return $value !== null; });

           // Convert province and regency IDs to codes for storage
           if (isset($_POST['provinsi_code'])) {
               global $wpdb;
               $province_code = $wpdb->get_var($wpdb->prepare(
                   "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
                   (int)$_POST['provinsi_code']
               ));
               if ($province_code) {
                   $data['provinsi_code'] = $province_code;
               }
           }

           if (isset($_POST['regency_code'])) {
               global $wpdb;
               $regency_code = $wpdb->get_var($wpdb->prepare(
                   "SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = %d",
                   (int)$_POST['regency_code']
               ));
               if ($regency_code) {
                   $data['regency_code'] = $regency_code;
               }
           }

            // Business logic validation
            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Update data
            $updated = $this->model->update($id, $data);

            // Save jurisdictions if provided
            if (isset($_POST['jurisdictions']) && is_array($_POST['jurisdictions'])) {
                $jurisdiction_codes = array_map('sanitize_text_field', $_POST['jurisdictions']);

                // Get current jurisdictions to preserve is_primary flags for existing ones
                $current_jurisdictions = $this->jurisdictionModel->getJurisdictionsByDivision($id);
                $current_primary = array_column(array_filter($current_jurisdictions, fn($j) => $j->is_primary), 'regency_code');

                // For edit, we need to handle is_primary carefully - don't allow removal of primary jurisdictions
                $primary_jurisdictions = isset($_POST['primary_jurisdictions']) && is_array($_POST['primary_jurisdictions'])
                    ? array_map('sanitize_text_field', $_POST['primary_jurisdictions'])
                    : $current_primary; // Preserve existing primary if not specified

                // Validate that no primary jurisdictions are being removed
                $removed_primaries = array_diff($current_primary, $jurisdiction_codes);
                if (!empty($removed_primaries)) {
                    throw new \Exception('Wilayah kerja utama tidak dapat dihapus. Silakan hapus tanda utama terlebih dahulu.');
                }

                if (!$this->jurisdictionModel->saveJurisdictions($id, $jurisdiction_codes, $primary_jurisdictions)) {
                    throw new \Exception('Gagal menyimpan wilayah kerja cabang');
                }
            }

            // Tambahkan di dalam method update() setelah update berhasil
            if ($updated) {
                // Hapus cache untuk division yang diupdate
                $this->cache->delete('division', $id);

                // Hapus juga cache lain yang terkait
                $agency_id = $division->agency_id;
                $this->cache->delete('agency_division_list', $agency_id);
                $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
                $this->model->invalidateEmployeeStatusCache($id);
                $this->validator->invalidateAccessCache($id);

                $updated_division = $this->model->find($id);
                $this->cache->set('division', $updated_division, $this->cache::getCacheExpiry(), $id);

                wp_send_json_success([
                    'message' => __('Division updated successfully', 'wp-agency'),
                    'division' => $updated_division
                ]);
                return;
            } else {
                throw new \Exception('Failed to update division');
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Perubahan pada DivisionController.php
     * Memindahkan logic cache ke method show()
     */
    public function show() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid division ID');
            }

            // Cek cache terlebih dahulu
            $cached_division = $this->cache->get('division', $id);
            
            // Jika ada dalam cache, gunakan data dari cache
            if ($cached_division !== null) {
                error_log('DEBUG STATUS FIELD CONTROLLER - Data diambil dari cache');
                $division = $cached_division;
            } else {
                // Jika tidak ada dalam cache, ambil dari database via model
                error_log('DEBUG STATUS FIELD CONTROLLER - Data diambil dari database');
                $division = $this->model->find($id);
                
                // Jika data berhasil diambil, simpan ke cache untuk penggunaan mendatang
                if ($division) {
                    $this->cache->set('division', $division, 3600, $id);
                }
            }

            // Periksa apakah division ditemukan
            if (!$division) {
                throw new \Exception('Division not found');
            }

            $relation = $this->validator->getUserRelation($division->id);
            if (!$this->validator->canViewDivision($relation)) {
                throw new \Exception('Anda tidak memiliki izin untuk melihat detail cabang ini.');
            }

            // Get jurisdictions for the division
            $jurisdictions = $this->jurisdictionModel->getJurisdictionsByDivision($id);

            // Initialize jurisdiction arrays
            $primary_jurisdictions = [];
            $non_primary_jurisdictions = [];
            $available_jurisdictions = [];

            // Get all available regencies for the province
            if ($division->provinsi_code) {
                global $wpdb;
                $province_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wi_provinces WHERE code = %s",
                    $division->provinsi_code
                ));
                if ($province_id) {
                    // Get agency_id for the division
                    $agency_id = $division->agency_id ?? $wpdb->get_var($wpdb->prepare(
                        "SELECT agency_id FROM {$wpdb->prefix}app_divisions WHERE id = %d",
                        $id
                    ));

                    // Get jurisdiction data separated by type
                    $assigned_jurisdictions = $wpdb->get_results($wpdb->prepare(
                        "SELECT r.id, r.code, r.name, aj.is_primary
                         FROM {$wpdb->prefix}wi_regencies r
                         INNER JOIN {$wpdb->prefix}app_agency_jurisdictions aj ON aj.jurisdiction_code = r.code AND aj.division_id = %d
                         WHERE r.province_id = %d
                         ORDER BY r.name ASC",
                        $id, $province_id
                    ));

                    $available_jurisdictions = $wpdb->get_results($wpdb->prepare(
                        "SELECT r.id, r.code, r.name
                         FROM {$wpdb->prefix}wi_regencies r
                         LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions aj ON aj.jurisdiction_code = r.code
                         WHERE r.province_id = %d AND aj.id IS NULL
                         ORDER BY r.name ASC",
                        $province_id
                    ));

                    // FIX: Ensure the division's own regency_code is marked as primary
                    // This corrects data inconsistency in the database
                    if ($division->regency_code) {
                        foreach ($assigned_jurisdictions as $jur) {
                            if ($jur->code === $division->regency_code) {
                                $jur->is_primary = 1;
                                // Also update the database permanently
                                $wpdb->update(
                                    $wpdb->prefix . 'app_agency_jurisdictions',
                                    ['is_primary' => 1],
                                    ['division_id' => $id, 'jurisdiction_code' => $jur->code],
                                    ['%d'],
                                    ['%d', '%s']
                                );
                                break; // Only one primary per division
                            }
                        }
                    }

                    // Debug logging
                    error_log("DEBUG ASSIGNED JURISDICTIONS for division $id:");
                    foreach ($assigned_jurisdictions as $jur) {
                        error_log("  - {$jur->code}: {$jur->name}, primary: '{$jur->is_primary}' (type: " . gettype($jur->is_primary) . ")");
                    }

                    // Separate primary and non-primary assigned jurisdictions
                    $primary_jurisdictions = array_filter($assigned_jurisdictions, fn($j) => $j->is_primary);
                    $non_primary_jurisdictions = array_filter($assigned_jurisdictions, fn($j) => !$j->is_primary);

                    error_log("DEBUG PRIMARY JURISDICTIONS (" . count($primary_jurisdictions) . "):");
                    foreach ($primary_jurisdictions as $jur) {
                        error_log("  - {$jur->code}: {$jur->name}");
                    }

                    error_log("DEBUG NON-PRIMARY JURISDICTIONS (" . count($non_primary_jurisdictions) . "):");
                    foreach ($non_primary_jurisdictions as $jur) {
                        error_log("  - {$jur->code}: {$jur->name}");
                    }
                }
            }

            // Convert codes back to IDs for form compatibility
            if ($division->provinsi_code) {
                global $wpdb;
                $province_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wi_provinces WHERE code = %s",
                    $division->provinsi_code
                ));
                $division->provinsi_id = $province_id ?: $division->provinsi_code;
            }

            if ($division->regency_code) {
                global $wpdb;
                $regency_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wi_regencies WHERE code = %s",
                    $division->regency_code
                ));
                $division->regency_id = $regency_id ?: $division->regency_code;
            }

            // Prepare jurisdiction data for JavaScript with flags
            $all_jurisdictions = [];

            // Add primary jurisdictions with flags
            foreach (($primary_jurisdictions ?? []) as $jur) {
                $all_jurisdictions[] = [
                    'id' => $jur->id,
                    'code' => $jur->code,
                    'name' => $jur->name,
                    'is_primary' => true,
                    'is_assign' => true
                ];
            }

            // Add non-primary jurisdictions with flags
            foreach (($non_primary_jurisdictions ?? []) as $jur) {
                $all_jurisdictions[] = [
                    'id' => $jur->id,
                    'code' => $jur->code,
                    'name' => $jur->name,
                    'is_primary' => false,
                    'is_assign' => true
                ];
            }

            // Add available jurisdictions with flags
            foreach (($available_jurisdictions ?? []) as $jur) {
                $all_jurisdictions[] = [
                    'id' => $jur->id,
                    'code' => $jur->code,
                    'name' => $jur->name,
                    'is_primary' => false,
                    'is_assign' => false
                ];
            }

            // Kembalikan data division dalam response
            wp_send_json_success([
                'division' => $division,
                'jurisdictions' => $jurisdictions,
                'primary_jurisdictions' => $primary_jurisdictions ?? [],
                'non_primary_jurisdictions' => $non_primary_jurisdictions ?? [],
                'available_jurisdictions' => $available_jurisdictions ?? [],
                'all_jurisdictions' => $all_jurisdictions // For easier debugging
            ]);

        } catch (\Exception $e) {
            error_log('DEBUG STATUS FIELD CONTROLLER - Error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function store() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            if (!$agency_id) {
                throw new \Exception('ID Agency tidak valid');
            }

            // Cek permission
            if (!$this->validator->canCreateDivision($agency_id)) {
                throw new \Exception('Anda tidak memiliki izin untuk menambah cabang');
            }

            // Sanitasi input
            $data = [
                'agency_id' => $agency_id,
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'type' => sanitize_text_field($_POST['type'] ?? ''),
                'nitku' => sanitize_text_field($_POST['nitku'] ?? ''),
                'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
                'latitude' => (float)($_POST['latitude'] ?? 0),
                'longitude' => (float)($_POST['longitude'] ?? 0),
                'address' => sanitize_text_field($_POST['address'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'created_by' => get_current_user_id(),
                'status' => 'active'
            ];

            // Convert province and regency IDs to codes for storage
            if (isset($_POST['provinsi_code'])) {
                global $wpdb;
                $province_code = $wpdb->get_var($wpdb->prepare(
                    "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
                    (int)$_POST['provinsi_code']
                ));
                if ($province_code) {
                    $data['provinsi_code'] = $province_code;
                }
            }

            if (isset($_POST['regency_code'])) {
                global $wpdb;
                $regency_code = $wpdb->get_var($wpdb->prepare(
                    "SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = %d",
                    (int)$_POST['regency_code']
                ));
                if ($regency_code) {
                    $data['regency_code'] = $regency_code;
                }
            }

            // Validasi type division saat create
            $type_validation = $this->validator->validateDivisionTypeCreate($data['type'], $agency_id);
            if (!$type_validation['valid']) {
                throw new \Exception($type_validation['message']);
            }

            // Buat user untuk admin division jika data admin diisi
            if (!empty($_POST['admin_email'])) {
                $user_data = [
                    'user_login' => sanitize_user($_POST['admin_username']),
                    'user_email' => sanitize_email($_POST['admin_email']),
                    'first_name' => sanitize_text_field($_POST['admin_firstname']),
                    'last_name' => sanitize_text_field($_POST['admin_lastname'] ?? ''),
                    'user_pass' => wp_generate_password(),
                    'role' => 'division_admin'
                ];

                $user_id = wp_insert_user($user_data);
                if (is_wp_error($user_id)) {
                    throw new \Exception($user_id->get_error_message());
                }

                $data['user_id'] = $user_id;
                
                // Kirim email aktivasi
                wp_new_user_notification($user_id, null, 'user');
            }

            // Simpan division
            $division_id = $this->model->create($data);
            if (!$division_id) {
                if (!empty($user_id)) {
                    wp_delete_user($user_id); // Rollback user creation jika gagal
                }
                throw new \Exception('Gagal menambah cabang');
            }

            // Save jurisdictions if provided
            if (isset($_POST['jurisdictions']) && is_array($_POST['jurisdictions'])) {
                $jurisdiction_codes = array_map('sanitize_text_field', $_POST['jurisdictions']);

                // Validate jurisdiction assignment
                $jurisdiction_validation = $this->jurisdictionValidator->validateJurisdictionAssignment($agency_id, $jurisdiction_codes, null, false);
                if (!$jurisdiction_validation['valid']) {
                    // If validation fails, delete the division and rollback
                    $this->model->delete($division_id);
                    if (!empty($user_id)) {
                        wp_delete_user($user_id);
                    }
                    throw new \Exception($jurisdiction_validation['message']);
                }

                $primary_jurisdictions = isset($_POST['primary_jurisdictions']) && is_array($_POST['primary_jurisdictions'])
                    ? array_map('sanitize_text_field', $_POST['primary_jurisdictions'])
                    : [];

                if (!$this->jurisdictionModel->saveJurisdictions($division_id, $jurisdiction_codes, $primary_jurisdictions)) {
                    // If jurisdiction save fails, delete the division and rollback
                    $this->model->delete($division_id);
                    if (!empty($user_id)) {
                        wp_delete_user($user_id);
                    }
                    throw new \Exception('Gagal menyimpan wilayah kerja cabang');
                }
            }

            $new_division = $this->model->find($division_id);

            $this->cache->set('division', $new_division, $this->cache::getCacheExpiry(), $division_id);
            // Invalidate cache
            $this->cache->invalidateDataTableCache('division_list', [
                'agency_id' => $agency_id
            ]);
            $this->cache->delete('agency_division_list', $agency_id);
            $this->cache->delete('division_total_count', $agency_id);

            wp_send_json_success([
                'message' => 'Cabang berhasil ditambahkan',
                'division' => $this->model->find($division_id)
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function delete() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid division ID');
            }
            
            // Ambil data division terlebih dahulu
            $division = $this->model->find($id);
            if (!$division) {
                throw new \Exception('Division not found');
            }
            
            $agency_id = $division->agency_id;
            
            $relation = $this->validator->getUserRelation($id);
            if (!$this->validator->canDeleteDivision($relation)) {
                throw new \Exception('Permission denied');
            }
            
            // Validate division type deletion
            $type_validation = $this->validator->validateDivisionTypeDelete($id);
            if (!$type_validation['valid']) {
                throw new \Exception($type_validation['message']);
            }

            // Proceed with deletion
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete division');
            }

            // Hapus cache division
            $this->cache->delete('division', $id);

            // Hapus cache daftar division agency
            $this->cache->delete('agency_division_list', $agency_id);

            // Invalidasi cache DataTable
            $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);

            // Invalidasi cache status karyawan
            $this->model->invalidateEmployeeStatusCache($id);

            // Invalidasi cache terkait agency
            $this->cache->invalidateAgencyCache($agency_id);
            
            // Tambahkan ini: Invalidasi cache akses
            $this->validator->invalidateAccessCache($id);

            wp_send_json_success([
                'message' => __('Division deleted successfully', 'wp-agency')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function getAgencyDivisions() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;
            if (!$agency_id) {
                throw new \Exception('ID Agency tidak valid');
            }

            // Periksa permission
            if (!current_user_can('view_division_list') && !current_user_can('view_own_division')) {
                throw new \Exception('Anda tidak memiliki akses untuk melihat data cabang');
            }

            // Cek cache dulu
            $cached_divisions = $this->cache->get('agency_division_list', $agency_id);
            if ($cached_divisions !== null) {
                wp_send_json_success($cached_divisions);
                return;
            }

            // Jika tidak ada di cache, ambil dari database
            $divisions = $this->model->getByAgency($agency_id);

            // Simpan ke cache untuk penggunaan mendatang
            if ($divisions) {
                $this->cache->set('agency_division_list', $divisions, $this->cache::getCacheExpiry(), $agency_id);
            }

            wp_send_json_success($divisions);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Khusus untuk membuat demo data division
     */
    public function createDemoDivision(array $data): ?int {
        try {
            // Debug log
            $this->debug_log('Creating demo division: ' . print_r($data, true));

            // Buat division via model
            $division_id = $this->model->create($data);
            
            if (!$division_id) {
                throw new \Exception('Gagal membuat demo division');
            }
            // Dapatkan role/capability user saat ini
            $access = $this->validator->validateAccess(0); 

            // Clear semua cache yang terkait
            $this->cache->delete('division', $division_id);
            $this->cache->delete('division_total_count', $access['access_type']);
            $this->cache->delete('agency_division', $data['agency_id']);
            $this->cache->delete('agency_division_list', $data['agency_id']);
            
            // Invalidate DataTable cache
            $this->cache->invalidateDataTableCache('division_list', [
                'agency_id' => $data['agency_id']
            ]);

            // Cache terkait agency juga perlu diperbarui
            $this->cache->invalidateAgencyCache($data['agency_id']);
            $this->model->invalidateEmployeeStatusCache($division_id);
            
            return $division_id;

        } catch (\Exception $e) {
            $this->debug_log('Error creating demo division: ' . $e->getMessage());
            throw $e;
        }
    }


}

