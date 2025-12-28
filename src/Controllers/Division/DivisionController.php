<?php
/**
 * Division Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Division
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Division/DivisionController.php
 *
 * Description: CRUD controller untuk Division entity.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles division creation with jurisdiction integration.
 *
 * Changelog:
 * 3.0.0 - 2025-12-28 (AbstractCRUD Refactoring)
 * - BREAKING: Complete refactor to extend AbstractCrudController
 * - Implements 9 abstract methods dari AbstractCrudController
 * - Custom: Jurisdiction handling, province/regency conversion
 * - Custom: Division admin user creation via hook
 * - Preserved: All custom endpoints and validation
 * - Adapted from AgencyController pattern
 *
 * 2.1.0 - 2025-11-01 (TODO-3095)
 * - Refactored handleDataTableRequest() to use DivisionDataTableModel
 *
 * 2.0.0 - 2025-01-22 (Task-2068 Division User Auto-Creation)
 * - User creation handled by HOOK (AutoEntityCreator)
 */

namespace WPAgency\Controllers\Division;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Division\DivisionDataTableModel;
use WPAgency\Models\Division\JurisdictionModel;
use WPAgency\Validators\Division\DivisionValidator;
use WPAgency\Validators\Division\JurisdictionValidator;
use WPAgency\Cache\AgencyCacheManager;

defined('ABSPATH') || exit;

class DivisionController extends AbstractCrudController {

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

    /**
     * Cache expiry time in seconds (2 hours)
     */
    private const CACHE_EXPIRY = 7200;

    public function __construct() {
        $this->agencyModel = new AgencyModel();
        $this->model = new DivisionModel();
        $this->jurisdictionModel = new JurisdictionModel();
        $this->validator = new DivisionValidator();
        $this->jurisdictionValidator = new JurisdictionValidator();
        $this->cache = AgencyCacheManager::getInstance();

        // Initialize log file inside plugin directory
        $this->log_file = WP_AGENCY_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX hooks
        $this->registerAjaxHooks();
    }

    /**
     * Register AJAX hooks
     */
    private function registerAjaxHooks(): void {
        // CRUD hooks
        add_action('wp_ajax_get_division', [$this, 'show']);
        add_action('wp_ajax_create_division', [$this, 'store']);
        add_action('wp_ajax_update_division', [$this, 'update']);
        add_action('wp_ajax_delete_division', [$this, 'delete']);

        // DataTable
        add_action('wp_ajax_handle_division_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_division_datatable', [$this, 'handleDataTableRequest']);
        
        // Custom endpoints
        add_action('wp_ajax_get_agency_divisions', [$this, 'getAgencyDivisions']);
        add_action('wp_ajax_validate_division_type_change', [$this, 'validateDivisionTypeChange']);
        add_action('wp_ajax_create_division_button', [$this, 'createDivisionButton']);
        add_action('wp_ajax_validate_division_access', [$this, 'validateDivisionAccess']);
        add_action('wp_ajax_get_available_divisions_for_create_division', [$this, 'getAvailableDivisionsForCreateDivision']);
        add_action('wp_ajax_get_available_provinces_for_division_creation', [$this, 'getAvailableProvincesForDivisionCreation']);
        add_action('wp_ajax_get_available_regencies_for_division_creation', [$this, 'getAvailableRegenciesForDivisionCreation']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    protected function getEntityName(): string {
        return 'division';
    }

    protected function getEntityNamePlural(): string {
        return 'divisions';
    }

    protected function getNonceAction(): string {
        return 'wp_agency_nonce';
    }

    protected function getTextDomain(): string {
        return 'wp-agency';
    }

    protected function getValidator() {
        return $this->validator;
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCacheGroup(): string {
        return 'wp_agency';
    }

    protected function prepareCreateData(): array {
        $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
        if (!$agency_id) {
            throw new \Exception('ID Agency tidak valid');
        }

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

        // Convert province and regency IDs to codes
        if (isset($_POST['province_id'])) {
            $data['province_id'] = (int)$_POST['province_id'];
        }

        if (isset($_POST['regency_id'])) {
            $data['regency_id'] = (int)$_POST['regency_id'];
        }

        // Pass admin data to hook (user creation handled by AutoEntityCreator)
        if (!empty($_POST['admin_email'])) {
            $data['admin_username'] = sanitize_user($_POST['admin_username']);
            $data['admin_email'] = sanitize_email($_POST['admin_email']);
            $data['admin_firstname'] = sanitize_text_field($_POST['admin_firstname'] ?? '');
            $data['admin_lastname'] = sanitize_text_field($_POST['admin_lastname'] ?? '');

            // Use agency user temporarily (hook will update to new user)
            $agency = $this->agencyModel->find($agency_id);
            $data['user_id'] = $agency->user_id;
        }

        return $data;
    }

    protected function prepareUpdateData(int $id): array {
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

        // Convert province and regency IDs
        if (isset($_POST['province_id'])) {
            $data['province_id'] = (int)$_POST['province_id'];
        }

        if (isset($_POST['regency_id'])) {
            $data['regency_id'] = (int)$_POST['regency_id'];
        }

        return $data;
    }

    // ========================================
    // OVERRIDE CRUD METHODS (Custom Logic)
    // ========================================

    public function store(): void {
        try {
            check_ajax_referer($this->getNonceAction(), 'nonce');

            $this->debug_log('DEBUG STORE: All POST data: ' . print_r($_POST, true));

            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;

            // Cek permission
            if (!$this->validator->canCreateDivision($agency_id)) {
                throw new \Exception('Anda tidak memiliki izin untuk menambah cabang');
            }

            // Prepare data
            $data = $this->prepareCreateData();

            $this->debug_log('DEBUG STORE: Sanitized data: ' . print_r($data, true));

            // Validasi type division saat create
            $type_validation = $this->validator->validateDivisionTypeCreate($data['type'], $agency_id);
            if (!$type_validation['valid']) {
                throw new \Exception($type_validation['message']);
            }

            // Simpan division (hook will create user + employee)
            $division_id = $this->model->create($data);
            if (!$division_id) {
                throw new \Exception('Gagal menambah cabang');
            }

            // Save jurisdictions if provided
            if (isset($_POST['jurisdictions']) && is_array($_POST['jurisdictions'])) {
                $jurisdiction_codes = array_map('sanitize_text_field', $_POST['jurisdictions']);

                $this->debug_log('DEBUG STORE: Jurisdiction codes: ' . print_r($jurisdiction_codes, true));

                // Validate jurisdiction assignment
                $jurisdiction_validation = $this->jurisdictionValidator->validateJurisdictionAssignment($agency_id, $jurisdiction_codes, null, true);
                if (!$jurisdiction_validation['valid']) {
                    $this->debug_log('DEBUG STORE: Jurisdiction validation failed: ' . $jurisdiction_validation['message']);
                    $this->model->delete($division_id);
                    throw new \Exception($jurisdiction_validation['message']);
                }

                $primary_jurisdictions = isset($_POST['primary_jurisdictions']) && is_array($_POST['primary_jurisdictions'])
                    ? array_map('sanitize_text_field', $_POST['primary_jurisdictions'])
                    : [];

                $this->debug_log('DEBUG STORE: Primary jurisdictions: ' . print_r($primary_jurisdictions, true));

                if (!$this->jurisdictionModel->saveJurisdictions($division_id, $jurisdiction_codes, $primary_jurisdictions)) {
                    $this->debug_log('DEBUG STORE: Failed to save jurisdictions');
                    $this->model->delete($division_id);
                    throw new \Exception('Gagal menyimpan wilayah kerja cabang');
                }
            }

            $new_division = $this->model->find($division_id);
            $this->debug_log('DEBUG STORE: Division created successfully with ID: ' . $division_id);

            // Cache and invalidate
            $this->cache->set('division', $new_division, self::CACHE_EXPIRY, $division_id);
            $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
            $this->cache->delete('agency_division_list', $agency_id);
            $this->cache->delete('division_total_count', $agency_id);

            wp_send_json_success([
                'message' => 'Cabang berhasil ditambahkan',
                'division' => $new_division
            ]);

        } catch (\Exception $e) {
            $this->debug_log('DEBUG STORE: Exception occurred: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function update(): void {
        try {
            check_ajax_referer($this->getNonceAction(), 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            error_log('[DivisionController] Update called for ID: ' . $id);

            if (!$id) {
                throw new \Exception('Invalid division ID');
            }

            // Get existing division data
            $division = $this->model->find($id);
            if (!$division) {
                throw new \Exception('Division not found');
            }

            $relation = $this->model->getUserRelation($division->id);
            $can_update = $this->validator->checkUpdatePermission($relation);
            error_log('[DivisionController] Permission check: ' . ($can_update ? 'PASS' : 'FAIL') . ' | Relation: ' . json_encode($relation));

            if (!$can_update) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini.');
            }

            // Prepare data
            $data = $this->prepareUpdateData($id);
            error_log('[DivisionController] Update data prepared: ' . json_encode($data));

            // Business logic validation
            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                error_log('[DivisionController] Validation errors: ' . json_encode($errors));
                throw new \Exception(reset($errors));
            }

            // Update data
            error_log('[DivisionController] Calling model->update()...');
            $updated = $this->model->update($id, $data);
            error_log('[DivisionController] Update result: ' . ($updated ? 'SUCCESS' : 'FAILED'));

            // Get updated division data to determine primary jurisdiction
            $updated_division = $this->model->find($id);

            // Handle jurisdictions
            $jurisdiction_codes = isset($_POST['jurisdictions']) && is_array($_POST['jurisdictions'])
                ? array_map('sanitize_text_field', $_POST['jurisdictions'])
                : [];

            // Automatically determine primary jurisdiction based on division's regency_id
            $primary_jurisdictions = [];
            if ($updated_division && $updated_division->regency_id && in_array($updated_division->regency_id, $jurisdiction_codes)) {
                $primary_jurisdictions = [$updated_division->regency_id];
            }

            // Get current jurisdictions to check for primary jurisdiction removal
            $current_jurisdictions = $this->jurisdictionModel->getJurisdictionsByDivision($id);
            $current_primary = array_column(array_filter($current_jurisdictions, fn($j) => $j->is_primary), 'regency_id');

            // Validate that no primary jurisdictions are being removed
            $removed_primaries = array_diff($current_primary, $jurisdiction_codes);
            if (!empty($removed_primaries)) {
                $invalid_removals = array_diff($removed_primaries, [$updated_division->regency_id ?? 0]);
                if (!empty($invalid_removals)) {
                    throw new \Exception('Wilayah kerja utama tidak dapat dihapus. Silakan hapus tanda utama terlebih dahulu.');
                }
            }

            if (!$this->jurisdictionModel->saveJurisdictions($id, $jurisdiction_codes, $primary_jurisdictions)) {
                throw new \Exception('Gagal menyimpan wilayah kerja cabang');
            }

            if ($updated) {
                // Invalidate cache
                $this->cache->delete('division', $id);
                $agency_id = $division->agency_id;
                $this->cache->delete('agency_division_list', $agency_id);
                $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
                $this->model->invalidateEmployeeStatusCache($id);

                $updated_division = $this->model->find($id);
                $this->cache->set('division', $updated_division, self::CACHE_EXPIRY, $id);

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

    public function delete(): void {
        try {
            check_ajax_referer($this->getNonceAction(), 'nonce');

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

            $relation = $this->model->getUserRelation($id);
            if (!$this->validator->checkDeletePermission($relation)) {
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

            // Invalidate cache
            $this->cache->delete('division', $id);
            $this->cache->delete('agency_division_list', $agency_id);
            $this->cache->invalidateDataTableCache('division_list', ['agency_id' => $agency_id]);
            $this->model->invalidateEmployeeStatusCache($id);
            $this->cache->invalidateAgencyCache($agency_id);

            wp_send_json_success([
                'message' => __('Division deleted successfully', 'wp-agency')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // DATATABLE & CUSTOM ENDPOINTS
    // ========================================

    public function handleDataTableRequest(): void {
        check_ajax_referer('wpdt_nonce', 'nonce');

        try {
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
            $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            $orderColumnIndex = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $columns = ['code', 'name', 'admin_name', 'type', 'status', 'jurisdictions', 'actions'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'code';

            $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

            // Prepare request data for model
            $request_data = $_POST;
            $request_data['status_filter'] = $status_filter;
            $request_data['agency_id'] = $agency_id;

            // Use DivisionDataTableModel - it handles format_row() which includes actions
            $datatable_model = new DivisionDataTableModel();
            $result = $datatable_model->get_datatable_data($request_data);

            error_log('[DivisionController] DataTable result keys: ' . implode(', ', array_keys($result)));
            if (!empty($result['data'])) {
                error_log('[DivisionController] First row sample: ' . json_encode($result['data'][0]));
            }

            // Return formatted result from model (already includes actions from format_row)
            wp_send_json($result);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function show(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if (!$id) {
                wp_send_json_error(['message' => 'Invalid division ID']);
                return;
            }

            $cached_division = $this->cache->get('division', $id);

            if ($cached_division !== false) {
                $division = $cached_division;
            } else {
                $division = $this->model->find($id);

                if ($division) {
                    $this->cache->set('division', $division, self::CACHE_EXPIRY, $id);
                }
            }

            if (!$division) {
                wp_send_json_error(['message' => 'Division not found']);
                return;
            }

            $agency = $this->agencyModel->find($division->agency_id);

            // Get access validation
            $access = $this->validator->validateAccess($id);

            if (!$access['has_access']) {
                wp_send_json_error(['message' => 'Access denied']);
                return;
            }

            // Get jurisdictions
            $jurisdictions = $this->jurisdictionModel->getJurisdictionsByDivision($id);

            wp_send_json_success([
                'division' => $division,
                'agency' => $agency,
                'access' => $access,
                'jurisdictions' => $jurisdictions
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function validateDivisionAccess(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0;

            if (!$division_id) {
                wp_send_json_error(['message' => 'Invalid division ID']);
                return;
            }

            $access = $this->validator->validateAccess($division_id);

            wp_send_json_success([
                'has_access' => $access['has_access'],
                'access_type' => $access['access_type'],
                'relation' => $access['relation']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function createDivisionButton(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;

            if (!$agency_id) {
                wp_send_json_error(['message' => 'Invalid agency ID']);
                return;
            }

            // Check permission
            $canCreate = $this->validator->canCreateDivision($agency_id);

            wp_send_json_success([
                'canCreate' => $canCreate,
                'button_html' => $canCreate ? $this->generateCreateButton() : ''
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function validateDivisionTypeChange(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0;
            $new_type = isset($_POST['new_type']) ? sanitize_text_field($_POST['new_type']) : '';
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;

            if (!$division_id || !$new_type || !$agency_id) {
                wp_send_json_error(['message' => 'Invalid parameters']);
                return;
            }

            $validation = $this->validator->validateDivisionTypeChange($division_id, $new_type, $agency_id);

            if ($validation['valid']) {
                wp_send_json_success(['message' => 'Type change valid']);
            } else {
                wp_send_json_error(['message' => $validation['message']]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getAgencyDivisions(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;

            if (!$agency_id) {
                wp_send_json_error(['message' => 'Invalid agency ID']);
                return;
            }

            $divisions = $this->model->getByAgency($agency_id);

            wp_send_json_success(['divisions' => $divisions]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getAvailableDivisionsForCreateDivision(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;

            if (!$agency_id) {
                wp_send_json_error(['message' => 'Invalid agency ID']);
                return;
            }

            $divisions = $this->model->getByAgency($agency_id);

            wp_send_json_success(['divisions' => $divisions]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getAvailableRegenciesForDivisionCreation(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            global $wpdb;

            $province_id = isset($_POST['province_id']) ? (int)$_POST['province_id'] : 0;

            if (!$province_id) {
                wp_send_json_error(['message' => 'Invalid province ID']);
                return;
            }

            $regencies = $wpdb->get_results($wpdb->prepare(
                "SELECT id, code, name FROM {$wpdb->prefix}wi_regencies WHERE province_id = %d ORDER BY name ASC",
                $province_id
            ));

            wp_send_json_success(['regencies' => $regencies]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getAvailableProvincesForDivisionCreation(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            global $wpdb;

            $provinces = $wpdb->get_results(
                "SELECT id, code, name FROM {$wpdb->prefix}wi_provinces ORDER BY name ASC"
            );

            wp_send_json_success(['provinces' => $provinces]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // HELPER & UTILITY METHODS
    // ========================================

    private function initLogDirectory(): void {
        $log_dir = dirname($this->log_file);

        if (!file_exists($log_dir)) {
            if (!wp_mkdir_p($log_dir)) {
                error_log("[DivisionController] CRITICAL: Failed to create log directory: {$log_dir}");
                error_log("[DivisionController] Check directory permissions for: " . WP_AGENCY_PATH);

                $parent_writable = is_writable(WP_AGENCY_PATH);
                error_log("[DivisionController] Parent directory writable: " . ($parent_writable ? 'YES' : 'NO'));

                return;
            }

            error_log("[DivisionController] Log directory created successfully: {$log_dir}");
        }

        if (!is_writable($log_dir)) {
            error_log("[DivisionController] WARNING: Log directory exists but not writable: {$log_dir}");
            return;
        }

        if (!file_exists($this->log_file)) {
            if (@file_put_contents($this->log_file, '') === false) {
                error_log("[DivisionController] CRITICAL: Failed to create log file: {$this->log_file}");
                return;
            }

            error_log("[DivisionController] Log file created successfully: {$this->log_file}");
        }

        if (!is_writable($this->log_file)) {
            error_log("[DivisionController] WARNING: Log file exists but not writable: {$this->log_file}");
        }
    }

    private function debug_log($message): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}\n";

        if (!file_exists($this->log_file)) {
            $this->initLogDirectory();
        }

        if (is_writable($this->log_file)) {
            error_log($log_message, 3, $this->log_file);
        } else {
            error_log("[DivisionController] Cannot write to log file: {$this->log_file}");
            error_log($message);
        }
    }

    private function generateActionButtons($division) {
        // Handle both object property and array access
        $division_id = is_object($division) ? ($division->id ?? null) : ($division['id'] ?? null);

        if (!$division_id) {
            error_log('[DivisionController] generateActionButtons - Division ID is null! Division data: ' . print_r($division, true));
            return '-';
        }

        $relation = $this->model->getUserRelation($division_id);

        $can_edit = $this->validator->checkUpdatePermission($relation);
        $can_delete = $this->validator->checkDeletePermission($relation);

        $buttons = '<div class="btn-group" role="group">';

        $division_name = is_object($division) ? ($division->name ?? '') : ($division['name'] ?? '');

        // View button (always available if user has access)
        if ($this->validator->checkViewPermission($relation)) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-info view-division"
                        data-id="%d"
                        data-name="%s"
                        title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </button>',
                esc_attr($division_id),
                esc_attr($division_name)
            );
        }

        // Edit button
        if ($can_edit) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-primary edit-division"
                        data-id="%d"
                        data-name="%s"
                        title="Edit">
                    <i class="fas fa-edit"></i>
                </button>',
                esc_attr($division_id),
                esc_attr($division_name)
            );
        }

        // Delete button
        if ($can_delete) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-danger delete-division"
                        data-id="%d"
                        data-name="%s"
                        title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>',
                esc_attr($division_id),
                esc_attr($division_name)
            );
        }

        $buttons .= '</div>';

        return $buttons;
    }

    private function generateCreateButton() {
        return '<button type="button" class="btn btn-primary" id="create-division-btn">
                    <i class="fas fa-plus"></i> Tambah Cabang
                </button>';
    }
}
