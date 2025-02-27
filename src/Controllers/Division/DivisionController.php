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
use WPAgency\Validators\Division\DivisionValidator;
use WPAgency\Cache\AgencyCacheManager;

class DivisionController {
    private AgencyModel $agencyModel;
    private DivisionModel $model;
    private DivisionValidator $validator;
    private AgencyCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/division.log';

    public function __construct() {
        $this->agencyModel = new AgencyModel();
        $this->model = new DivisionModel();
        $this->validator = new DivisionValidator();
        $this->cache = new AgencyCacheManager();

        // Initialize log file inside plugin directory
        $this->log_file = WP_AGENCY_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_division_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_division_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_agency_divisiones', [$this, 'getAgencyDivisions']);

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

            // Get current division data
            $division = $this->model->find($division_id);
            if (!$division) {
                throw new \Exception('Division not found');
            }

            // Get all divisiones for this agency
            $existing_pusat = $this->model->findPusatByAgency($division->agency_id);
            
            // If changing to 'pusat' and there's already a pusat division
            if ($new_type === 'pusat' && $existing_pusat && $existing_pusat->id !== $division_id) {
                wp_send_json_error([
                    'message' => sprintf(
                        'Agency sudah memiliki kantor pusat: %s. Tidak dapat mengubah cabang ini menjadi kantor pusat.',
                        $existing_pusat->name
                    ),
                    'original_type' => $division->type
                ]);
            }
            
            // If changing from 'pusat' to 'cabang', check if this is the only pusat
            if ($division->type === 'pusat' && $new_type === 'cabang') {
                $pusat_count = $this->model->countPusatByAgency($division->agency_id);
                if ($pusat_count <= 1) {
                    wp_send_json_error([
                        'message' => 'Tidak dapat mengubah tipe menjadi cabang karena ini adalah satu-satunya kantor pusat.',
                        'original_type' => $division->type
                    ]);
                }
            }

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

        if ($this->validator->canViewDivision($division, $agency)) {
            $actions .= sprintf(
                '<button type="button" class="button view-division" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $division->id,
                __('Lihat', 'wp-agency')
            );
        }

        if ($this->validator->canEditDivision($division, $agency)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-division" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $division->id,
                __('Edit', 'wp-agency')
            );
        }

        if ($this->validator->canDeleteDivision($division, $agency)) {
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

            $columns = ['name', 'type', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';
            if ($orderBy === 'actions') {
                $orderBy = 'name';
            }

            // Check cache first
            $cached_result = $this->cache->getDataTableCache(
                'division_list',
                get_current_user_id(),
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
                $agency = $this->agencyModel->find($division->agency_id);
                if (!$this->validator->canViewDivision($division, $agency)) {
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
                    'actions' => $this->generateActionButtons($division)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data,
            ];

            // Cache the result
            $this->cache->setDataTableCache(
                'division_list',
                get_current_user_id(),
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

                // Get agency data untuk permission check
                $agency = $this->agencyModel->find($division->agency_id);
                if (!$agency) {
                    throw new \Exception('Agency not found');
                }

                // Permission check di awal
                if (!$this->validator->canEditDivision($division, $agency)) {
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
                   'provinsi_id' => isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : null,
                   'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
                   'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
                   'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
               ], function($value) { return $value !== null; });

                // Business logic validation
                $errors = $this->validator->validateUpdate($data, $id);
                if (!empty($errors)) {
                    throw new \Exception(reset($errors));
                }

                // Update data
                $updated = $this->model->update($id, $data);
                if (!$updated) {
                    throw new \Exception('Failed to update division');
                }

                wp_send_json_success([
                    'message' => __('Division updated successfully', 'wp-agency'),
                    'division' => $this->model->find($id)
                ]);

            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

        public function show() {
            try {
                check_ajax_referer('wp_agency_nonce', 'nonce');

                $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                if (!$id) {
                    throw new \Exception('Invalid division ID');
                }

                // Get division data
                $division = $this->model->find($id);
                if (!$division) {
                    throw new \Exception('Division not found');
                }

                // Get agency data
                $agency = $this->agencyModel->find($division->agency_id);
                if (!$agency) {
                    throw new \Exception('Agency not found');
                }

                // Permission check di awal
                if (!$this->validator->canViewDivision($division, $agency)) {
                    throw new \Exception('Anda tidak memiliki izin untuk melihat detail cabang ini.');
                }

                // Business logic validation dengan data yang sudah ada
                $errors = $this->validator->validateView($division, $agency);
                if (!empty($errors)) {
                    throw new \Exception(reset($errors));
                }

                wp_send_json_success([
                    'division' => $division
                ]);

            } catch (\Exception $e) {
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
                    'provinsi_id' => isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : null,
                    'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ];

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

                // Invalidate cache
                $this->cache->invalidateDataTableCache('division_list', [
                    'agency_id' => $agency_id
                ]);

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
            
            // Get division and agency for permission check
            $division = $this->model->find($id);
            $agency = $this->agencyModel->find($division->agency_id);
            
            // Check edit permission first (since delete requires edit)
            if (!$this->validator->canEditDivision($division, $agency)) {
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

            $divisiones = $this->model->getByAgency($agency_id);

            wp_send_json_success($divisiones);

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

            // Clear semua cache yang terkait
            $this->cache->delete('division', $division_id);
            $this->cache->delete('division_total_count', get_current_user_id());
            $this->cache->delete('agency_division', $data['agency_id']);
            $this->cache->delete('agency_division_list', $data['agency_id']);
            
            // Invalidate DataTable cache
            $this->cache->invalidateDataTableCache('division_list', [
                'agency_id' => $data['agency_id']
            ]);

            // Cache terkait agency juga perlu diperbarui
            $this->cache->invalidateAgencyCache($data['agency_id']);

            return $division_id;

        } catch (\Exception $e) {
            $this->debug_log('Error creating demo division: ' . $e->getMessage());
            throw $e;
        }
    } 
}

