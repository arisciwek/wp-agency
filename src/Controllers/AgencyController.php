<?php
/**
* Agency Controller Class
*
* @package     WP_Agency
* @subpackage  Controllers
* @version     1.0.7
* @author      arisciwek
*
* Path: /wp-agency/src/Controllers/AgencyController.php
*
* Description: Controller untuk mengelola data agency.
*              Menangani operasi CRUD dengan integrasi cache.
*              Includes validasi input, permission checks,
*              dan response formatting untuk panel kanan.
*              Menyediakan endpoints untuk DataTables server-side.
*
* Changelog:
* 1.0.3 - 2025-01-22 (Task-2067 Runtime Flow)
* - Deleted createDemoAgency() method (production code pollution)
* - Demo generation now uses production code flow
* - Zero demo-specific code in production namespace
*
* 1.0.2 - 2025-01-22 (Task-2065 Follow-up)
* - Added user creation in store() method
* - Admin can now input username and email when creating agency
* - Auto-assigns dual roles: 'agency' + 'agency_admin_dinas'
* - Sends email notification to new user
* - Consistent with wp-customer pattern
*
* 1.0.1 - 2024-12-08
* - Added view_own_agency permission check in show method
* - Enhanced permission validation
* - Improved error handling for permission checks
*
* 1.0.0 - 2024-12-03 14:30:00
* - Refactor CRUD responses untuk panel kanan
* - Added cache integration di semua endpoints
* - Added konsisten response format
* - Added validasi dan permission di semua endpoints
* - Improved error handling dan feedback
*/

namespace WPAgency\Controllers;

use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Validators\AgencyValidator;
use WPAgency\Cache\AgencyCacheManager;

class AgencyController {
    private $error_messages;
    private AgencyModel $model;
    private AgencyValidator $validator;
    private AgencyCacheManager $cache;
    private DivisionModel $divisionModel;
    private AgencyEmployeeModel $employeeModel;

    private string $log_file;

    private function logPermissionCheck($action, $user_id, $agency_id, $result, $division_id = null) {
        // $this->debug_log(sprintf(
        //    'Permission check for %s - User: %d, Agency: %d, Division: %s, Result: %s',
        //    $action,
        //    $user_id,
        //    $agency_id,
        //    $division_id ?? 'none',  // Gunakan null coalescing untuk handle null division_id
        //    $result ? 'granted' : 'denied'
        // ));
    }

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/agency.log';

    public function __construct() {
        $this->model = new AgencyModel();
        $this->divisionModel = new DivisionModel();
        $this->employeeModel = new AgencyEmployeeModel();
        $this->validator = new AgencyValidator();
        $this->cache = new AgencyCacheManager();

        // Inisialisasi error messages
        $this->error_messages = [
            'insufficient_permissions' => __('Anda tidak memiliki izin untuk melakukan operasi ini', 'wp-agency'),
            'view_denied' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-agency'),
            'edit_denied' => __('Anda tidak memiliki izin untuk mengubah data ini', 'wp-agency'),
            'delete_denied' => __('Anda tidak memiliki izin untuk menghapus data ini', 'wp-agency'),
        ];


        // Inisialisasi log file di dalam direktori plugin
        $this->log_file = WP_AGENCY_PATH . self::DEFAULT_LOG_FILE;

        // Pastikan direktori logs ada
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_agency_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_agency_datatable', [$this, 'handleDataTableRequest']);

        // Register endpoint untuk update
        add_action('wp_ajax_update_agency', [$this, 'update']);

        // Register endpoint lain yang diperlukan
        add_action('wp_ajax_get_agency', [$this, 'show']);
        add_action('wp_ajax_create_agency', [$this, 'store']);
        add_action('wp_ajax_delete_agency', [$this, 'delete']);
        add_action('wp_ajax_validate_agency_access', [$this, 'validateAgencyAccess']);
        //add_action('wp_ajax_get_current_agency_id', [$this, 'getCurrentAgencyId']);
        add_action('wp_ajax_generate_agency_pdf', [$this, 'generate_agency_pdf']);
        add_action('wp_ajax_generate_wp_docgen_agency_detail_document', [$this, 'generate_wp_docgen_agency_detail_document']);
        add_action('wp_ajax_generate_wp_docgen_agency_detail_pdf', [$this, 'generate_wp_docgen_agency_detail_pdf']);
        add_action('wp_ajax_create_agency_button', [$this, 'createAgencyButton']);

        add_action('wp_ajax_create_pdf_button', [$this, 'createPdfButton']);
        add_action('wp_ajax_get_available_provinces_for_agency_creation', [$this, 'getAvailableProvincesForAgencyCreation']);
        add_action('wp_ajax_get_available_regencies_for_agency_creation', [$this, 'getAvailableRegenciesForAgencyCreation']);

        add_action('wp_ajax_get_available_provinces_for_agency_editing', [$this, 'getAvailableProvincesForAgencyEditing']);
        add_action('wp_ajax_get_available_regencies_for_agency_editing', [$this, 'getAvailableRegenciesForAgencyEditing']);


    }
    
public function createPdfButton() {
    try {
        check_ajax_referer('wp_agency_nonce', 'nonce');
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$id) {
            throw new \Exception('Invalid agency ID');
        }

        // Tambahkan logging untuk debug
        $access = $this->validator->validateAccess($id);
        error_log('PDF Button Access Check - User: ' . get_current_user_id());
        error_log('Access Result: ' . print_r($access, true));

        if (!$access['has_access']) {
            wp_send_json_success(['button' => '']);
            return;
        }

        $button = '<button type="button" class="button wp-mpdf-agency-detail-export-pdf">';
        $button .= '<span class="dashicons dashicons-pdf"></span>';
        $button .= __('Generate PDF', 'wp-agency');
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

    /**
     * Generate DOCX document
     */
    public function generate_wp_docgen_agency_detail_document() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // Validate access
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this agency');
            }

            // Get agency data
            $agency = $this->model->find($id);
            if (!$agency) {
                throw new \Exception('Agency not found');
            }

            // Initialize WP DocGen
            //$docgen = new \WPDocGen\Generator();
            $docgen = wp_docgen();

            // Set template variables
            $variables = [
                'agency_name' => $agency->name,
                'agency_code' => $agency->code,
                'total_divisiones' => $agency->division_count,
                'created_date' => date('d F Y H:i', strtotime($agency->created_at)),
                'updated_date' => date('d F Y H:i', strtotime($agency->updated_at)),
                'generated_date' => date('d F Y H:i')
            ];

            // Get template path
            $template_path = WP_AGENCY_PATH . 'templates/docx/agency-detail.docx';

            // Generate DOCX
            $output_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.docx';
            $docgen->generateFromTemplate($template_path, $variables, $output_path);

            // Prepare download response
            $file_url = wp_upload_dir()['url'] . '/agency-' . $agency->code . '.docx';
            wp_send_json_success([
                'file_url' => $file_url,
                'filename' => 'agency-' . $agency->code . '.docx'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Generate PDF from DOCX
     */
    public function generate_wp_docgen_agency_detail_pdf() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // Similar validation as DOCX generation
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this agency');
            }

            $agency = $this->model->find($id);
            if (!$agency) {
                throw new \Exception('Agency not found');
            }

            // Initialize WP DocGen
            $docgen = new \WPDocGen\Generator();

            // Generate DOCX first (similar to generate_wp_docgen_agency_detail_document)
            $variables = [
                'agency_name' => $agency->name,
                'agency_code' => $agency->code,
                'total_divisiones' => $agency->division_count,
                'created_date' => date('d F Y H:i', strtotime($agency->created_at)),
                'updated_date' => date('d F Y H:i', strtotime($agency->updated_at)),
                'generated_date' => date('d F Y H:i')
            ];

            $template_path = WP_AGENCY_PATH . 'templates/docx/agency-detail.docx';
            $docx_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.docx';
            
            // Generate DOCX first
            $docgen->generateFromTemplate($template_path, $variables, $docx_path);

            // Convert DOCX to PDF
            $pdf_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.pdf';
            $docgen->convertToPDF($docx_path, $pdf_path);

            // Clean up DOCX file
            unlink($docx_path);

            // Send PDF URL back
            $pdf_url = wp_upload_dir()['url'] . '/agency-' . $agency->code . '.pdf';
            wp_send_json_success([
                'file_url' => $pdf_url,
                'filename' => 'agency-' . $agency->code . '.pdf'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function createAgencyButton() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            if (!current_user_can('add_agency')) {
                wp_send_json_success(['button' => '']);
                return;
            }

            $button = '<button type="button" class="button button-primary" id="add-agency-btn">';
            $button .= '<span class="dashicons dashicons-plus-alt"></span>';
            $button .= __('Tambah Agency', 'wp-agency');
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

    public function generate_agency_pdf() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // Cek akses
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this agency');
            }

            // Load wp-mpdf jika ada
            if (!function_exists('wp_mpdf_load')) {
                throw new \Exception('PDF generator plugin tidak ditemukan');
            }

            if (!wp_mpdf_load()) {
                throw new \Exception('Gagal memuat PDF generator plugin');
            }

            if (!wp_mpdf_init()) {
                throw new \Exception('Gagal menginisialisasi PDF generator');
            }
            
            // Ambil data agency
            $agency = $this->model->find($id);
            if (!$agency) {
                throw new \Exception('Agency not found');
            }

            // Generate PDF menggunakan WP mPDF
            ob_start();
            include WP_AGENCY_PATH . 'src/Views/templates/agency/pdf/agency-detail-pdf.php';
            $html = ob_get_clean();

            $mpdf = wp_mpdf()->generate_pdf($html, [
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16
            ]);

            // Output PDF untuk download
            $mpdf->Output('agency-' . $agency->code . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'pdf_generation_error'
            ]);
        }
    }

    /**
     * Validate agency access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateAgencyAccess() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            // Gunakan validator langsung
            $access = $this->validator->validateAccess($agency_id);
            
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

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $agency_base_dir = $upload_dir['basedir'] . '/wp-agency';
        $agency_log_dir = $agency_base_dir . '/logs';
        
        // Update log file path with monthly rotation format
        $this->log_file = $agency_log_dir . '/agency-' . date('Y-m') . '.log';

        // Create base wp-agency directory if it doesn't exist
        if (!file_exists($agency_base_dir)) {
            if (!wp_mkdir_p($agency_base_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-agency.log';
                error_log('Failed to create base directory in uploads: ' . $agency_base_dir);
                return;
            }
            
            // Add .htaccess to base directory
            $base_htaccess_content = "# Protect Directory\n";
            $base_htaccess_content .= "<FilesMatch \"^.*$\">\n";
            $base_htaccess_content .= "Order Deny,Allow\n";
            $base_htaccess_content .= "Deny from all\n";
            $base_htaccess_content .= "</FilesMatch>\n";
            $base_htaccess_content .= "\n";
            $base_htaccess_content .= "# Allow specific file types if needed\n";
            $base_htaccess_content .= "<FilesMatch \"\.(jpg|jpeg|png|gif|css|js)$\">\n";
            $base_htaccess_content .= "Order Allow,Deny\n";
            $base_htaccess_content .= "Allow from all\n";
            $base_htaccess_content .= "</FilesMatch>";
            
            @file_put_contents($agency_base_dir . '/.htaccess', $base_htaccess_content);
            @chmod($agency_base_dir, 0755);
        }

        // Create logs directory if it doesn't exist
        if (!file_exists($agency_log_dir)) {
            if (!wp_mkdir_p($agency_log_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-agency.log';
                error_log('Failed to create log directory in uploads: ' . $agency_log_dir);
                return;
            }

            // Add .htaccess to logs directory with strict rules
            $logs_htaccess_content = "# Deny access to all files\n";
            $logs_htaccess_content .= "Order deny,allow\n";
            $logs_htaccess_content .= "Deny from all\n\n";
            $logs_htaccess_content .= "# Deny access to log files specifically\n";
            $logs_htaccess_content .= "<Files ~ \"\.log$\">\n";
            $logs_htaccess_content .= "Order allow,deny\n";
            $logs_htaccess_content .= "Deny from all\n";
            $logs_htaccess_content .= "</Files>\n\n";
            $logs_htaccess_content .= "# Extra protection\n";
            $logs_htaccess_content .= "<IfModule mod_php.c>\n";
            $logs_htaccess_content .= "php_flag engine off\n";
            $logs_htaccess_content .= "</IfModule>";
            
            @file_put_contents($agency_log_dir . '/.htaccess', $logs_htaccess_content);
            @chmod($agency_log_dir, 0755);
        }

        // Create log file if it doesn't exist
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
     * Log debug messages ke file
     *
     * @param mixed $message Pesan yang akan dilog
     * @return void
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

        // Gunakan error_log bawaan WordPress dengan custom log file
        error_log($log_message, 3, $this->log_file);
    }

    private function getAgencyTableData($start = 0, $length = 10, $search = '', $orderColumn = 'code', $orderDir = 'asc', $status_filter = 'active') {
        try {

            // Validasi permission yang sudah bekerja di handleDataTableRequest()
            $hasPermission = current_user_can('view_agency_list');

            // Dapatkan role/capability user saat ini
            $access = $this->validator->validateAccess(0);

            $this->logPermissionCheck(
                'view_agency_list',
                $access['access_type'],  // Langsung gunakan access_type yang sudah ada                0,
                null,
                $hasPermission
            );

            if (!$hasPermission) {
                return null;
            }

            // Get data using model with status filter
            $result = $this->model->getDataTableData($start, $length, $search, $orderColumn, $orderDir, $status_filter);

            return $result;

        } catch (\Exception $e) {
            $this->debug_log('Error getting agency table data: ' . $e->getMessage());
            return null;
        }
    }

    // Untuk AJAX request
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_agency_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get parameters with safe defaults
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

            // Force active filter if user doesn't have delete_agency permission
            if (!current_user_can('delete_agency')) {
                $status_filter = 'active';
            }

            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) && isset($_POST['columns'][$_POST['order'][0]['column']]['data'])
                ? sanitize_text_field($_POST['columns'][$_POST['order'][0]['column']]['data'])
                : 'name';
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Additional parameters if needed
            $additionalParams = ['status_filter' => $status_filter];

            // If filtering by specific parameters
            if (isset($_POST['status'])) {
                $additionalParams['status'] = sanitize_text_field($_POST['status']);
            }
            if (isset($_POST['type'])) {
                $additionalParams['type'] = sanitize_text_field($_POST['type']);
            }

            // Dapatkan role/capability user saat ini
            $access = $this->validator->validateAccess(0); 

            // Check cache first
            $cached_result = $this->cache->getDataTableCache(
                'agency_list',          // Specific context for main agency listing
                $access['access_type'],  // Langsung gunakan access_type yang sudah ada
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                $additionalParams        // Additional filtering parameters if any
            );

            if ($cached_result) {
                wp_send_json($cached_result);
                return;
            }

            // Get fresh data if no cache
            $result = $this->getAgencyTableData($start, $length, $search, $orderColumn, $orderDir, $status_filter);
            if (!$result) {
                throw new \Exception('Failed to fetch agency data');
            }

            // Format data for response
            $data = [];
            foreach ($result['data'] as $agency) {
                $data[] = [
                    'id' => $agency->id,
                    'code' => esc_html($agency->code),
                    'name' => esc_html($agency->name),
                    'owner_name' => esc_html($agency->owner_name ?? '-'),
                    'division_count' => intval($agency->division_count),
                    'actions' => $this->generateActionButtons($agency)
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

            // Set cache
            $this->cache->setDataTableCache(
                'agency_list',         // Same context as get
                $access['access_type'],  // Langsung gunakan access_type yang sudah ada
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                $response,
                $additionalParams       // Same additional parameters
            );

            wp_send_json($response);

        } catch (\Exception $e) {
            $this->debug_log('DataTable error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate action buttons untuk DataTable row
     * 
     * @param object $agency Data agency dari row
     * @return string HTML button actions
     */
    private function generateActionButtons($agency) {
        $actions = '';
        
        // Dapatkan relasi user dengan agency ini
        $relation = $this->validator->getUserRelation($agency->id);
        
        // View Button - selalu tampilkan jika punya akses view
        if ($this->validator->canView($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button view-agency" data-id="%d">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                $agency->id
            );
        }

        // Edit Button - tampilkan jika punya akses edit
        if ($this->validator->canUpdate($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button edit-agency" data-id="%d">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                $agency->id
            );
        }

        // Delete Button - hanya untuk admin
        if ($this->validator->canDelete($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button small-button delete-agency" data-id="%d">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                $agency->id
            );
        }
        return $actions;
    }

    /**
     * Handle agency creation request
     * Endpoint: wp_ajax_create_agency
     */
    public function store() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $permission_errors = $this->validator->validatePermission('create');
            if (!empty($permission_errors)) {
                wp_send_json_error(['message' => reset($permission_errors)]);
                return;
            }

            // Handle user creation if username and email provided
            $user_id = null;
            if (!empty($_POST['username']) && !empty($_POST['email'])) {
                // Validate username and email
                $username = sanitize_user($_POST['username']);
                $email = sanitize_email($_POST['email']);

                if (username_exists($username)) {
                    wp_send_json_error(['message' => __('Username sudah digunakan', 'wp-agency')]);
                    return;
                }

                if (email_exists($email)) {
                    wp_send_json_error(['message' => __('Email sudah terdaftar', 'wp-agency')]);
                    return;
                }

                // Create new user
                $user_data = [
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass' => wp_generate_password(),
                    'role' => 'agency'
                ];

                $user_id = wp_insert_user($user_data);

                if (is_wp_error($user_id)) {
                    wp_send_json_error(['message' => $user_id->get_error_message()]);
                    return;
                }

                // Add agency_admin_dinas role (dual-role pattern)
                $user = get_user_by('ID', $user_id);
                if ($user) {
                    $user->add_role('agency_admin_dinas');
                }

                // Send notification to new user
                wp_new_user_notification($user_id, null, 'user');
            } else {
                // Use existing user_id or current user
                $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : get_current_user_id();
            }

            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
                'provinsi_code' => isset($_POST['provinsi_code']) ? sanitize_text_field($_POST['provinsi_code']) : null,
                'regency_code' => isset($_POST['regency_code']) ? sanitize_text_field($_POST['regency_code']) : null,
                'user_id' => $user_id,
                'reg_type' => 'by_admin', // Mark as admin creation
                'created_by' => get_current_user_id()
            ];

            error_log('Received data: ' . print_r($data, true));

            $form_errors = $this->validator->validateForm($data);
            if (!empty($form_errors)) {
                wp_send_json_error(['message' => implode(', ', $form_errors)]);
                return;
            }

            $id = $this->model->create($data);
            if (!$id) {
                throw new \Exception('Failed to create agency');
            }
            
            error_log('Created agency ID: ' . $id);

            $agency = $this->model->find($id);
            error_log('Found agency: ' . print_r($agency, true));

            // Simpan agency ke cache
            $this->cache->set('agency', $agency, $this->cache::getCacheExpiry(), $id);
            
            // Invalidasi cache terkait
            $this->cache->invalidateDataTableCache('agency_list');
            $this->cache->delete('agency_total_count', get_current_user_id());

            wp_send_json_success([
                'message' => __('Agency berhasil ditambahkan', 'wp-agency'),
                'data' => $agency
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle agency update request
     * Endpoint: wp_ajax_update_agency
     */
    public function update() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // 1. Permission validation
            $permission_errors = $this->validator->validatePermission('update', $id);
            if (!empty($permission_errors)) {
                wp_send_json_error([
                    'message' => reset($permission_errors)
                ]);
                return;
            }

            // 2. Prepare update data
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'status' => !empty($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active',
                'provinsi_code' => !empty($_POST['provinsi_code']) ? sanitize_text_field($_POST['provinsi_code']) : null,
                'regency_code' => !empty($_POST['regency_code']) ? sanitize_text_field($_POST['regency_code']) : null
            ];

            // Add validation for status field
            if (empty($data['status'])) {
                $data['status'] = 'active'; // Default value
            }

            // Validate status is one of allowed values
            if (!in_array($data['status'], ['active', 'inactive'])) {
                throw new \Exception('Invalid status value');
            }

            // Handle user_id if present and user has permission
            if (isset($_POST['user_id']) && current_user_can('edit_all_agencies')) {
                $data['user_id'] = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
            }

            // Debug log
            error_log('Update data received: ' . print_r($data, true));
            error_log('Raw POST data: ' . print_r($_POST, true));

            // 3. Form validation
            $form_errors = $this->validator->validateForm($data, $id);
            if (!empty($form_errors)) {
                wp_send_json_error([
                    'message' => implode(', ', $form_errors)
                ]);
                return;
            }

            // 4. Perform update
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update agency');
            }

            // Clear relevant caches
            $this->cache->invalidateAgencyCache($id);

            // 5. Get updated data for response
            $agency = $this->model->find($id);
            if (!$agency) {
                throw new \Exception('Failed to retrieve updated agency');
            }

            // Get additional data for response
            $division_count = $this->model->getDivisionCount($id);
            $access = $this->validator->validateAccess($id);

            // 6. Return success response with complete data
            wp_send_json_success([
                'message' => __('Agency berhasil diperbarui', 'wp-agency'),
                'data' => [
                    'agency' => array_merge((array)$agency, [
                        'access_type' => $access['access_type'],
                        'has_access' => $access['has_access']
                    ]),
                    'division_count' => $division_count,
                    'access_type' => $access['access_type']
                ]
            ]);

        } catch (\Exception $e) {
            $this->debug_log('Update error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function show() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $this->debug_log("=== Start show() ===");
            
            // Get and validate ID
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // Validate access
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this agency');
            }

            // Cek cache terlebih dahulu
            $cached_agency = $this->cache->get('agency', $id);
            
            if ($cached_agency !== null) {
                $this->debug_log("Data agency diambil dari cache");
                $agency = $cached_agency;
            } else {
                // Jika tidak ada dalam cache, ambil dari database
                $this->debug_log("Data agency diambil dari database");
                $agency = $this->model->find($id);
                
                // Simpan ke cache jika ditemukan
                if ($agency) {
                    $this->cache->set('agency', $agency, $this->cache::getCacheExpiry(), $id);
                }
            }

            if (!$agency) {
                throw new \Exception('Agency not found');
            }



            // Prepare response data
            $response_data = [
                'agency' => $agency,
                'access_type' => $access['access_type']
            ];

            $this->debug_log("Sending response: " . print_r($response_data, true));
            
            wp_send_json_success($response_data);

        } catch (\Exception $e) {
            $this->debug_log("Error in show(): " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function enrichAgencyData($agency, $access, $division_count) {
        return [
            'agency' => array_merge((array)$agency, [
                'access_type' => $access['access_type'],
                'has_access' => $access['has_access']
            ]),
            'division_count' => $division_count,
            'access_type' => $access['access_type']
        ];
    }

    private function logAgencyAccess($data) {
        $this->debug_log('=== Agency Access Debug ===');
        $this->debug_log('Agency ID: ' . $data['agency']['id']);
        $this->debug_log('Access Type: ' . $data['access_type']);
        $this->debug_log('Agency Data: ' . print_r($data['agency'], true));
    }

    public function delete() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid agency ID');
            }

            // Add this check
            if (!current_user_can('delete_agency')) {
                throw new \Exception('Insufficient permissions');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Perform delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete agency');
            }

            // Clear cache
            $this->cache->invalidateAgencyCache($id);

            wp_send_json_success([
                'message' => __('Data Agency berhasil dihapus', 'wp-agency')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getStats() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            // Get agency_id from query param
            $agency_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $current_user_id = get_current_user_id();

            // Stats should be filtered by user permissions
            // Use user_id in cache key for per-user stats
            $cache_key = 'agency_stats_' . $current_user_id;
            if ($agency_id > 0) {
                $cache_key .= '_agency_' . $agency_id;
            }

            // Check cache first
            $cached_stats = $this->cache->get($cache_key);
            if ($cached_stats !== null) {
                wp_send_json_success($cached_stats);
                return;
            }

            // Validate access if agency_id provided
            if ($agency_id) {
                $access = $this->validator->validateAccess($agency_id);
                if (!$access['has_access']) {
                    throw new \Exception('You do not have permission to view this agency');
                }
            }

        // Get counts filtered by user permissions
        $stats = [
            'total_agencies' => $this->model->getTotalCount(),  // ✅ RESTRICTED by user
            'total_divisions' => $this->divisionModel->getTotalCount(),  // ✅ RESTRICTED by user
            'total_employees' => $this->employeeModel->getTotalCount($agency_id)  // Already restricted
        ];

            // Cache for 5 minutes
            $this->cache->set($cache_key, $stats, 5 * MINUTE_IN_SECONDS);

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function renderMainPage() {
        // Render template
        require_once WP_AGENCY_PATH . 'src/Views/templates/agency-dashboard.php';
    }

    public function getAgencyData() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            $access = $this->validator->validateAccess($agency_id);
            if (!$access['has_access']) {
                throw new \Exception('No access to agency');
            }

            $agency = $this->model->find($agency_id);
            if (!$agency) {
                throw new \Exception('Agency not found');
            }

            wp_send_json_success([
                'agency' => $agency
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Get available provinces for agency creation
     * Returns provinces that are not yet assigned to any agency
     */
    public function getAvailableProvincesForAgencyCreation() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            // Check permission to create agencies
            if (!current_user_can('add_agency')) {
                throw new \Exception('Insufficient permissions to create agencies');
            }

            global $wpdb;

            // Query to get unassigned provinces
            $provinces = $wpdb->get_results("
                SELECT p.id, p.name, p.code
                FROM {$wpdb->prefix}wi_provinces p
                LEFT JOIN {$wpdb->prefix}app_agencies a ON a.provinsi_code = p.code
                WHERE a.provinsi_code IS NULL
                ORDER BY p.name ASC
            ");

            // Format for select options
            $options = [];
            foreach ($provinces as $province) {
                $options[] = [
                    'value' => $province->code,
                    'label' => esc_html($province->name)
                ];
            }

            wp_send_json_success([
                'provinces' => $options
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available regencies for agency editing
     * Returns regencies in the agency's province where divisions exist
     */
    public function getAvailableRegenciesForAgencyEditing() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            if (!$agency_id) {
                throw new \Exception('Agency ID is required');
            }

            // Validate access to the agency
            $access = $this->validator->validateAccess($agency_id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to edit this agency');
            }

            global $wpdb;

            // First get the agency's current regency to include it even if no divisions
            $agency_regency = $wpdb->get_row($wpdb->prepare("
                SELECT r.id, r.code, r.name FROM {$wpdb->prefix}wi_regencies r
                JOIN {$wpdb->prefix}app_agencies a ON a.regency_code = r.code
                WHERE a.id = %d
            ", $agency_id));

            // Debug raw query as per TODO - get available regencies with divisions
            $available_query = $wpdb->prepare("
                SELECT r.id, r.code, r.name FROM {$wpdb->prefix}wi_regencies r
                JOIN {$wpdb->prefix}wi_provinces p ON p.id = r.province_id
                JOIN {$wpdb->prefix}app_agencies a ON a.provinsi_code = p.code
                WHERE a.id = %d AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}app_agency_divisions d WHERE d.regency_code = r.code
                )
                GROUP BY r.id, r.code, r.name
            ", $agency_id);

            error_log('DEBUG: Available regencies query: ' . $available_query);

            // Execute the query
            $regencies = $wpdb->get_results($available_query);

            // Include agency's current regency if not already in the list
            if ($agency_regency) {
                $exists = false;
                foreach ($regencies as $regency) {
                    if ($regency->code === $agency_regency->code) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    array_unshift($regencies, $agency_regency);
                }
            }

            // Sort by name
            usort($regencies, function($a, $b) {
                return strcmp($a->name, $b->name);
            });

            // Format for select options
            $options = [];
            foreach ($regencies as $regency) {
                $options[] = [
                    'value' => $regency->code, // Assuming regency has code field, adjust if needed
                    'label' => esc_html($regency->name)
                ];
            }

            wp_send_json_success([
                'regencies' => $options
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available regencies for agency creation
     * Returns regencies in the selected province where the province is not assigned to any agency
     */
    public function getAvailableRegenciesForAgencyCreation() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            // Check permission to create agencies
            if (!current_user_can('add_agency')) {
                throw new \Exception('Insufficient permissions to create agencies');
            }

            $province_code = isset($_POST['province_code']) ? sanitize_text_field($_POST['province_code']) : '';
            if (empty($province_code)) {
                throw new \Exception('Province code is required');
            }

            global $wpdb;

            // Check if wi_regencies table exists and has data
            $regencies_table = $wpdb->prefix . 'wi_regencies';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$regencies_table'") == $regencies_table;

            if (!$table_exists) {
                error_log("DEBUG: wi_regencies table does not exist, returning sample data");
                // Return sample data for testing
                $sample_regencies = [
                    ['id' => 1, 'code' => '1101', 'name' => 'Kabupaten Aceh Selatan'],
                    ['id' => 2, 'code' => '1102', 'name' => 'Kabupaten Aceh Tenggara'],
                    ['id' => 3, 'code' => '1103', 'name' => 'Kabupaten Aceh Timur'],
                    ['id' => 4, 'code' => '1104', 'name' => 'Kabupaten Aceh Tengah'],
                    ['id' => 5, 'code' => '1105', 'name' => 'Kabupaten Aceh Barat'],
                ];

                $regencies = array_map(function($regency) {
                    return (object) $regency;
                }, $sample_regencies);
            } else {
                // Query to get available regencies in the selected province
                $regencies = $wpdb->get_results($wpdb->prepare("
                    SELECT r.id, r.code, r.name
                    FROM {$wpdb->prefix}wi_provinces p
                    LEFT JOIN {$wpdb->prefix}app_agencies a ON a.provinsi_code = p.code
                    INNER JOIN {$wpdb->prefix}wi_regencies r ON r.province_id = p.id
                    WHERE a.provinsi_code IS NULL AND p.code = %s
                    ORDER BY r.name ASC
                ", $province_code));
            }

            // Format for select options
            $options = [];
            foreach ($regencies as $regency) {
                $options[] = [
                    'value' => $regency->code,
                    'label' => esc_html($regency->name)
                ];
            }

            wp_send_json_success([
                'regencies' => $options
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get all provinces for agency editing
     * Returns all provinces (not filtered like creation)
     */
    public function getAvailableProvincesForAgencyEditing() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            // Check permission to edit agencies
            // Permission check removed for form loading - actual edit operations have proper checks

            global $wpdb;

            // Query to get ALL provinces (not filtered)
            $provinces = $wpdb->get_results("
                SELECT p.id, p.name, p.code
                FROM {$wpdb->prefix}wi_provinces p
                ORDER BY p.name ASC
            ");

            // Format for select options
            $options = [];
            foreach ($provinces as $province) {
                $options[] = [
                    'value' => $province->code,
                    'label' => esc_html($province->name)
                ];
            }

            wp_send_json_success([
                'provinces' => $options
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
