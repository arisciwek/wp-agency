<?php
/**
 * Agency Employee Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Employee
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Employee/AgencyEmployeeController.php
 *
 * Description: CRUD controller untuk Employee entity.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles employee creation with role assignment integration.
 *
 * Changelog:
 * 2.0.0 - 2025-12-28 (AbstractCRUD Refactoring)
 * - BREAKING: Complete refactor to extend AbstractCrudController
 * - Implements 9 abstract methods dari AbstractCrudController
 * - Custom: Role assignment, employee status management
 * - Preserved: All custom endpoints and validation
 * - Adapted from DivisionController pattern
 */

namespace WPAgency\Controllers\Employee;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Models\Employee\EmployeeDataTableModel;
use WPAgency\Validators\Employee\AgencyEmployeeValidator;
use WPAgency\Cache\AgencyCacheManager;

defined('ABSPATH') || exit;

class AgencyEmployeeController extends AbstractCrudController {

    private AgencyModel $agencyModel;
    private DivisionModel $divisionModel;
    private AgencyEmployeeModel $model;
    private AgencyEmployeeValidator $validator;
    private AgencyCacheManager $cache;

    /**
     * Cache expiry time in seconds (2 hours)
     */
    private const CACHE_EXPIRY = 7200;

    public function __construct() {
        $this->agencyModel = new AgencyModel();
        $this->divisionModel = new DivisionModel();
        $this->model = new AgencyEmployeeModel();
        $this->validator = new AgencyEmployeeValidator();
        $this->cache = AgencyCacheManager::getInstance();

        // Register AJAX hooks
        $this->registerAjaxHooks();
    }

    /**
     * Register AJAX hooks
     */
    private function registerAjaxHooks(): void {
        // CRUD hooks
        add_action('wp_ajax_get_agency_employee', [$this, 'show']);
        add_action('wp_ajax_create_agency_employee', [$this, 'store']);
        add_action('wp_ajax_update_agency_employee', [$this, 'update']);
        add_action('wp_ajax_delete_agency_employee', [$this, 'delete']);

        // Modal form handlers (auto-wire system)
        add_action('wp_ajax_get_agency_employee_form', [$this, 'handleGetEmployeeForm']);
        add_action('wp_ajax_save_agency_employee', [$this, 'handleSaveEmployee']);
        add_action('wp_ajax_delete_agency_employee', [$this, 'handleDeleteEmployee']);

        // DataTable
        add_action('wp_ajax_handle_agency_employee_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_agency_employee_datatable', [$this, 'handleDataTableRequest']);

        // Custom endpoints
        add_action('wp_ajax_create_employee_button', [$this, 'createEmployeeButton']);
        add_action('wp_ajax_change_employee_status', [$this, 'changeStatus']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    protected function getEntityName(): string {
        return 'employee';
    }

    protected function getEntityNamePlural(): string {
        return 'employees';
    }

    protected function getNonceAction(): string {
        return 'wpdt_nonce';
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
        $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : null;

        if (!$agency_id) {
            throw new \Exception('Agency ID tidak valid');
        }

        $data = [
            'agency_id' => $agency_id,
            'division_id' => $division_id,
            'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
            'nip' => sanitize_text_field($_POST['nip'] ?? ''),
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'join_date' => sanitize_text_field($_POST['join_date'] ?? current_time('Y-m-d')),
            'status' => 'active',
            'created_by' => get_current_user_id()
        ];

        return $data;
    }

    protected function prepareUpdateData(int $id): array {
        $data = array_filter([
            'division_id' => isset($_POST['division_id']) ? (int)$_POST['division_id'] : null,
            'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
            'nip' => isset($_POST['nip']) ? sanitize_text_field($_POST['nip']) : null,
            'full_name' => isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : null,
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : null,
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
            'position' => isset($_POST['position']) ? sanitize_text_field($_POST['position']) : null,
            'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : null,
            'join_date' => isset($_POST['join_date']) ? sanitize_text_field($_POST['join_date']) : null,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
        ], function($value) { return $value !== null; });

        return $data;
    }

    // ========================================
    // OVERRIDE CRUD METHODS (Custom Logic)
    // ========================================

    public function store(): void {
        try {
            check_ajax_referer($this->getNonceAction(), 'nonce');

            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : null;

            // Cek permission
            if (!$this->validator->canCreateEmployee($agency_id, $division_id)) {
                throw new \Exception('Anda tidak memiliki izin untuk menambah karyawan');
            }

            // Prepare data
            $data = $this->prepareCreateData();

            // Validate data
            $errors = $this->validator->validateCreate($data);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Create employee
            $employee_id = $this->model->create($data);
            if (!$employee_id) {
                throw new \Exception('Gagal menambah karyawan');
            }

            $new_employee = $this->model->find($employee_id);

            // Cache
            $this->cache->set('employee', $new_employee, self::CACHE_EXPIRY, $employee_id);
            $this->cache->invalidateDataTableCache('employee_list', ['agency_id' => $agency_id]);

            wp_send_json_success([
                'message' => 'Karyawan berhasil ditambahkan',
                'employee' => $new_employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function update(): void {
        try {
            check_ajax_referer($this->getNonceAction(), 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Get existing employee data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $relation = $this->model->getUserRelation($employee->id);
            if (!$this->validator->checkUpdatePermission($relation)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit karyawan ini.');
            }

            // Prepare data
            $data = $this->prepareUpdateData($id);

            // Validate data
            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Update employee
            $updated = $this->model->update($id, $data);

            if ($updated) {
                // Update user roles if provided
                if (isset($_POST['roles']) && is_array($_POST['roles']) && $employee->user_id) {
                    $user = get_userdata($employee->user_id);
                    if ($user) {
                        // Remove all current roles
                        foreach ($user->roles as $role) {
                            $user->remove_role($role);
                        }

                        // Add new roles
                        foreach ($_POST['roles'] as $role) {
                            $user->add_role(sanitize_text_field($role));
                        }
                    }
                }

                // Invalidate cache
                $this->cache->delete('employee', $id);
                $this->cache->invalidateDataTableCache('employee_list', ['agency_id' => $employee->agency_id]);

                $updated_employee = $this->model->find($id);
                $this->cache->set('employee', $updated_employee, self::CACHE_EXPIRY, $id);

                wp_send_json_success([
                    'message' => __('Employee updated successfully', 'wp-agency'),
                    'employee' => $updated_employee
                ]);
                return;
            } else {
                throw new \Exception('Failed to update employee');
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
                throw new \Exception('Invalid employee ID');
            }

            // Get employee data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $agency_id = $employee->agency_id;

            $relation = $this->model->getUserRelation($id);
            if (!$this->validator->checkDeletePermission($relation)) {
                throw new \Exception('Permission denied');
            }

            // Delete employee
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete employee');
            }

            // Invalidate cache
            $this->cache->delete('employee', $id);
            $this->cache->invalidateDataTableCache('employee_list', ['agency_id' => $agency_id]);

            wp_send_json_success([
                'message' => __('Employee deleted successfully', 'wp-agency')
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

            $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

            // Prepare request data for model
            $request_data = $_POST;
            $request_data['status_filter'] = $status_filter;
            $request_data['agency_id'] = $agency_id;

            // Use EmployeeDataTableModel - it handles format_row() which includes actions
            $datatable_model = new EmployeeDataTableModel();
            $result = $datatable_model->get_datatable_data($request_data);

            error_log('[AgencyEmployeeController] DataTable result keys: ' . implode(', ', array_keys($result)));
            if (!empty($result['data'])) {
                error_log('[AgencyEmployeeController] First row sample: ' . json_encode($result['data'][0]));
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
                wp_send_json_error(['message' => 'Invalid employee ID']);
                return;
            }

            $cached_employee = $this->cache->get('employee', $id);

            if ($cached_employee !== false) {
                $employee = $cached_employee;
            } else {
                $employee = $this->model->find($id);

                if ($employee) {
                    $this->cache->set('employee', $employee, self::CACHE_EXPIRY, $id);
                }
            }

            if (!$employee) {
                wp_send_json_error(['message' => 'Employee not found']);
                return;
            }

            $agency = $this->agencyModel->find($employee->agency_id);

            // Get access validation
            $access = $this->validator->validateAccess($id);

            if (!$access['has_access']) {
                wp_send_json_error(['message' => 'Access denied']);
                return;
            }

            wp_send_json_success([
                'employee' => $employee,
                'agency' => $agency,
                'access' => $access
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function createEmployeeButton(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : null;

            if (!$agency_id) {
                wp_send_json_error(['message' => 'Invalid agency ID']);
                return;
            }

            // Check permission
            $canCreate = $this->validator->canCreateEmployee($agency_id, $division_id);

            wp_send_json_success([
                'canCreate' => $canCreate,
                'button_html' => $canCreate ? $this->generateCreateButton() : ''
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function changeStatus(): void {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

            if (!$id || !in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid parameters');
            }

            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $relation = $this->model->getUserRelation($id);
            if (!$this->validator->checkUpdatePermission($relation)) {
                throw new \Exception('Permission denied');
            }

            if ($this->model->update($id, ['status' => $status])) {
                $this->cache->delete('employee', $id);
                $this->cache->invalidateDataTableCache('employee_list', ['agency_id' => $employee->agency_id]);

                wp_send_json_success([
                    'message' => __('Status updated successfully', 'wp-agency')
                ]);
            } else {
                throw new \Exception('Failed to update status');
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function generateActionButtons($employee) {
        $relation = $this->model->getUserRelation($employee->id);

        $can_edit = $this->validator->checkUpdatePermission($relation);
        $can_delete = $this->validator->checkDeletePermission($relation);

        $buttons = '<div class="btn-group" role="group">';

        // View button
        if ($this->validator->checkViewPermission($relation)) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-info view-employee" 
                        data-id="%d" 
                        data-name="%s" 
                        title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </button>',
                esc_attr($employee->id),
                esc_attr($employee->full_name)
            );
        }

        // Edit button
        if ($can_edit) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-primary edit-employee" 
                        data-id="%d" 
                        data-name="%s" 
                        title="Edit">
                    <i class="fas fa-edit"></i>
                </button>',
                esc_attr($employee->id),
                esc_attr($employee->full_name)
            );
        }

        // Delete button
        if ($can_delete) {
            $buttons .= sprintf(
                '<button type="button" class="btn btn-sm btn-danger delete-employee" 
                        data-id="%d" 
                        data-name="%s" 
                        title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>',
                esc_attr($employee->id),
                esc_attr($employee->full_name)
            );
        }

        $buttons .= '</div>';

        return $buttons;
    }

    private function generateCreateButton() {
        return '<button type="button" class="btn btn-primary" id="create-employee-btn">
                    <i class="fas fa-plus"></i> Tambah Karyawan
                </button>';
    }

    /**
     * Handle get employee form (create/edit)
     * For auto-wire modal system
     */
    public function handleGetEmployeeForm(): void {
        try {
            // Auto-wire system sends wpdt_nonce
            $nonce = $_GET['nonce'] ?? $_POST['nonce'] ?? '';

            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
                return;
            }

            $employee_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

            if ($employee_id) {
                // Edit mode
                $employee = $this->model->find($employee_id);

                if (!$employee) {
                    wp_send_json_error(['message' => __('Employee not found', 'wp-agency')]);
                    return;
                }

                // Get additional data for form
                global $wpdb;

                // Get divisions for this agency
                $divisions = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name FROM {$wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND status = 'active'
                     ORDER BY name ASC",
                    $employee->agency_id
                ));

                // Get current user roles
                $user = get_userdata($employee->user_id);
                $current_roles = $user ? $user->roles : [];

                // Load edit form and capture output for auto-wire system
                ob_start();
                include WP_AGENCY_PATH . 'src/Views/admin/employee/forms/edit-employee-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            } else {
                // Create mode
                ob_start();
                include WP_AGENCY_PATH . 'src/Views/admin/employee/forms/create-employee-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle save employee (create/update)
     * For auto-wire modal system
     */
    public function handleSaveEmployee(): void {
        try {
            // Auto-wire system sends wpdt_nonce
            $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';

            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                throw new \Exception(__('Security check failed', 'wp-agency'));
            }

            $mode = $_POST['mode'] ?? 'create';
            $employee_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            if ($mode === 'edit' && $employee_id) {
                // Update mode - delegate to update() method
                $this->update();
            } else {
                // Create mode - delegate to store() method
                $this->store();
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delete employee
     * For auto-wire modal system
     */
    public function handleDeleteEmployee(): void {
        try {
            check_ajax_referer('wpdt_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception(__('Invalid employee ID', 'wp-agency'));
            }

            // Get employee for validation
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception(__('Employee not found', 'wp-agency'));
            }

            // Check permission
            if (!$this->validator->canDeleteEmployee($id)) {
                throw new \Exception(__('Permission denied', 'wp-agency'));
            }

            // Delete via model
            $result = $this->model->delete($id);

            if (!$result) {
                throw new \Exception(__('Failed to delete employee', 'wp-agency'));
            }

            // Clear cache
            $this->cache->delete('employee', $id);
            $this->cache->invalidateDataTableCache('employee_list', ['agency_id' => $employee->agency_id]);

            wp_send_json_success([
                'message' => __('Employee deleted successfully', 'wp-agency')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
