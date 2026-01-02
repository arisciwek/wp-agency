<?php
/**
 * New Company Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Company
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Company/NewCompanyController.php
 *
 * Description: Controller untuk mengelola data company (branch) yang belum memiliki inspector.
 *              Menangani operasi DataTable dan all-in-one assignment (agency + division + inspector).
 *              Includes cascade dropdown handlers, validasi input, permission checks,
 *              dan response formatting untuk DataTables.
 *
 * Changelog:
 * 2.1.0 - 2026-01-02 (Auto-Wire Compatibility)
 * - ADDED: Support for auto-wire modal system parameters
 * - ADDED: Auto-detect agency_id from user's employee record
 * - ADDED: Fallback agency_id detection from regency jurisdiction
 * - ADDED: Get company_name from branch record (no need to pass manually)
 * - CHANGED: getAssignmentForm() now accepts 'id' (auto-wire) or 'branch_id' (legacy)
 * - CHANGED: assignInspector() now accepts 'id' (auto-wire) or 'branch_id' (legacy)
 * - CHANGED: Nonce support for both wpdt_nonce (auto-wire) and wp_agency_nonce (legacy)
 * - FIXED: Form data populate issue - agency and company name now auto-detected
 * - BENEFIT: Backward compatible with existing code while supporting auto-wire
 * 2.0.0 - 2025-01-13
 * - BREAKING: All-in-One Assignment (agency + division + inspector)
 * - Added getAllAgencies() handler
 * - Added getDivisionsByAgency() handler
 * - Added getInspectorsByDivision() handler
 * - Updated assignInspector() to handle all 3 fields
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 * - Added DataTables integration
 * - Added permission checks
 * - Added cache support
 */

namespace WPAgency\Controllers\Company;

use WPAgency\Models\Company\NewCompanyModel;
use WPAgency\Models\Company\NewCompanyDataTableModel;
use WPAgency\Validators\Company\NewCompanyValidator;
use WPAgency\Cache\AgencyCacheManager;

class NewCompanyController {
    private NewCompanyModel $model;
    private NewCompanyDataTableModel $datatableModel;
    private NewCompanyValidator $validator;
    private AgencyCacheManager $cache;

    public function __construct() {
        $this->model = new NewCompanyModel();
        $this->datatableModel = new NewCompanyDataTableModel();
        $this->validator = new NewCompanyValidator();
        $this->cache = new AgencyCacheManager();

        // Register AJAX handlers
        add_action('wp_ajax_handle_new_company_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_new_company_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_assign_inspector', [$this, 'assignInspector']);
        add_action('wp_ajax_get_available_inspectors', [$this, 'getAvailableInspectors']);

        // Cascade dropdown handlers
        add_action('wp_ajax_get_all_agencies', [$this, 'getAllAgencies']);
        add_action('wp_ajax_get_divisions_by_agency', [$this, 'getDivisionsByAgency']);
        add_action('wp_ajax_get_inspectors_by_division', [$this, 'getInspectorsByDivision']);

        // Form template loader
        add_action('wp_ajax_get_assignment_form', [$this, 'getAssignmentForm']);
    }

    /**
     * Handle DataTable AJAX request
     *
     * Uses NewCompanyDataTableModel for server-side processing.
     * The model handles data retrieval, formatting, and action buttons.
     */
    public function handleDataTableRequest() {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
        if (!$agency_id) {
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permissions
        if (!$this->validator->canViewNewCompanies($agency_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            // Use DataTableModel - it handles everything (format_row, action buttons, etc.)
            $response = $this->datatableModel->get_datatable_data($_POST);
            wp_send_json($response);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading new companies', 'wp-agency')]);
        }
    }

    /**
     * Get available inspectors for assignment
     *
     * Retrieves agency employees who can be assigned as inspectors for a specific branch.
     * Inspectors must be in the same division as the branch and have either 'agency' (Disnaker)
     * or 'pengawas' (Pengawas) role.
     *
     * @return void JSON response with list of available inspectors
     */
    public function getAvailableInspectors() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

            if (!$agency_id || !$branch_id) {
                throw new \Exception('Invalid agency ID or branch ID');
            }

            // Get branch to determine division
            $branch = $this->model->getBranchById($branch_id);
            if (!$branch || !$branch->division_id) {
                throw new \Exception('Branch not found or has no division assigned');
            }

            // Get division to determine which agency owns it
            global $wpdb;
            $divisions_table = $wpdb->prefix . 'app_agency_divisions';

            $division = $wpdb->get_row($wpdb->prepare(
                "SELECT agency_id FROM {$divisions_table} WHERE id = %d",
                $branch->division_id
            ));

            if (!$division || !$division->agency_id) {
                throw new \Exception('Division not found or has no agency assigned');
            }

            $actual_agency_id = $division->agency_id;
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Branch agency_id: " . ($agency_id ?? 'NULL') . ", Division agency_id: {$actual_agency_id}");

            // Get inspectors from agency employees filtered by division and role
            $employees_table = $wpdb->prefix . 'app_agency_employees';

            // Get inspectors who are in the same division as the branch AND have agency or pengawas role
            $query = $wpdb->prepare(
                "SELECT DISTINCT e.user_id, e.name
                 FROM {$employees_table} e
                 INNER JOIN {$divisions_table} d ON d.id = e.division_id
                 INNER JOIN {$wpdb->users} u ON e.user_id = u.ID
                 INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
                 WHERE e.agency_id = %d
                 AND d.id = %d
                 AND e.status = 'active'
                 AND d.status = 'active'
                 AND um.meta_key = '{$wpdb->prefix}capabilities'
                 AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
                 ORDER BY e.name ASC",
                $actual_agency_id,
                $branch->division_id,
                '%agency%',
                '%pengawas%'
            );

            // Debug: Log the raw query
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Raw Query: " . $query);
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Actual Agency ID: {$actual_agency_id}, Branch Division ID: {$branch->division_id}");

            $inspectors = $wpdb->get_results($query);

            // Debug: Log the results
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Found " . count($inspectors) . " inspectors with agency or pengawas role");
            foreach ($inspectors as $inspector) {
                error_log("DEBUG NewCompanyController::getAvailableInspectors - Inspector: ID={$inspector->user_id}, Name={$inspector->name}");
            }

            // Get assignment count for each inspector
            $filtered_inspectors = [];
            foreach ($inspectors as $inspector) {
                $assignment_count = $this->model->getInspectorAssignments($inspector->user_id);
                $count = count($assignment_count);

                $filtered_inspectors[] = [
                    'value' => $inspector->user_id,
                    'label' => esc_html($inspector->name),
                    'assignment_count' => $count
                ];
            }

            error_log("DEBUG NewCompanyController::getAvailableInspectors - Final filtered count: " . count($filtered_inspectors));

            wp_send_json_success([
                'inspectors' => $filtered_inspectors
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user has inspector role or capability
     *
     * Inspector roles include 'agency' (Disnaker) and 'pengawas' (Pengawas).
     * Users with these roles can be assigned as inspectors for branches.
     */
    private function hasInspectorRole($user): bool {
        // Check capabilities first
        if (user_can($user->ID, 'inspect_companies') ||
            user_can($user->ID, 'manage_options')) {
            return true;
        }

        // Check roles - 'agency' (Disnaker) and 'pengawas' roles are considered inspectors
        $inspector_roles = ['agency', 'pengawas'];
        $user_roles = $user->roles;

        foreach ($inspector_roles as $role) {
            if (in_array($role, $user_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all active agencies
     *
     * @return void JSON response with list of agencies
     */
    public function getAllAgencies() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            global $wpdb;
            $agencies_table = $wpdb->prefix . 'app_agencies';

            $agencies = $wpdb->get_results(
                "SELECT id, name
                 FROM {$agencies_table}
                 WHERE status = 'active'
                 ORDER BY name ASC"
            );

            wp_send_json_success([
                'agencies' => $agencies ?: []
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get divisions by agency
     *
     * @return void JSON response with list of divisions
     */
    public function getDivisionsByAgency() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;

            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            global $wpdb;
            $divisions_table = $wpdb->prefix . 'app_agency_divisions';

            $divisions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name
                 FROM {$divisions_table}
                 WHERE agency_id = %d AND status = 'active'
                 ORDER BY name ASC",
                $agency_id
            ));

            wp_send_json_success([
                'divisions' => $divisions ?: []
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get inspectors by division
     *
     * Retrieves agency employees filtered by division and role (agency/pengawas).
     * Includes assignment count for each inspector.
     *
     * @return void JSON response with list of inspectors
     */
    public function getInspectorsByDivision() {
        try {
            // Accept both wp_agency_nonce and wpdt_nonce (for wp-customer integration)
            $nonce_valid = check_ajax_referer('wp_agency_nonce', 'nonce', false) ||
                          check_ajax_referer('wpdt_nonce', 'nonce', false);

            if (!$nonce_valid) {
                throw new \Exception('Security check failed');
            }

            $division_id = isset($_POST['division_id']) ? intval($_POST['division_id']) : 0;

            if (!$division_id) {
                throw new \Exception('Invalid division ID');
            }

            global $wpdb;
            $employees_table = $wpdb->prefix . 'app_agency_employees';
            $divisions_table = $wpdb->prefix . 'app_agency_divisions';

            // Get inspectors with agency or pengawas role in the specified division
            $query = $wpdb->prepare(
                "SELECT DISTINCT e.user_id, e.name
                 FROM {$employees_table} e
                 INNER JOIN {$divisions_table} d ON d.id = e.division_id
                 INNER JOIN {$wpdb->users} u ON e.user_id = u.ID
                 INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
                 WHERE e.division_id = %d
                 AND e.status = 'active'
                 AND d.status = 'active'
                 AND um.meta_key = '{$wpdb->prefix}capabilities'
                 AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
                 ORDER BY e.name ASC",
                $division_id,
                '%agency%',
                '%pengawas%'
            );

            error_log("DEBUG NewCompanyController::getInspectorsByDivision - Query: " . $query);

            $inspectors = $wpdb->get_results($query);

            error_log("DEBUG NewCompanyController::getInspectorsByDivision - Found " . count($inspectors) . " inspectors");

            // Add assignment counts
            $result = [];
            foreach ($inspectors as $inspector) {
                $assignment_count = $this->model->getInspectorAssignments($inspector->user_id);
                $count = count($assignment_count);

                $result[] = [
                    'value' => $inspector->user_id,
                    'label' => esc_html($inspector->name),
                    'assignment_count' => $count
                ];
            }

            error_log("DEBUG NewCompanyController::getInspectorsByDivision - Final result: " . count($result) . " inspectors");

            wp_send_json_success([
                'inspectors' => $result
            ]);

        } catch (\Exception $e) {
            error_log("DEBUG NewCompanyController::getInspectorsByDivision - Exception: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get assignment form template
     */
    public function getAssignmentForm() {
        try {
            // Check nonce - support both wpdt_nonce (auto-wire) and wp_agency_nonce (legacy)
            // Try wpdt_nonce first (auto-wire system), fallback to wp_agency_nonce
            $nonce_verified = false;

            if (isset($_REQUEST['nonce'])) {
                // Try wpdt_nonce first
                if (wp_verify_nonce($_REQUEST['nonce'], 'wpdt_nonce')) {
                    $nonce_verified = true;
                } elseif (wp_verify_nonce($_REQUEST['nonce'], 'wp_agency_nonce')) {
                    $nonce_verified = true;
                }
            }

            if (!$nonce_verified) {
                throw new \Exception('Security check failed');
            }

            // Support both 'id' (auto-wire) and 'branch_id' (legacy)
            $branch_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : (isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0);

            if (!$branch_id) {
                throw new \Exception('Branch ID required');
            }

            global $wpdb;

            // Get branch data to retrieve company name and regency info
            $branch = $wpdb->get_row($wpdb->prepare(
                "SELECT cb.id, cb.regency_id, c.name as company_name
                 FROM {$wpdb->prefix}app_customer_branches cb
                 LEFT JOIN {$wpdb->prefix}app_customers c ON cb.customer_id = c.id
                 WHERE cb.id = %d",
                $branch_id
            ));

            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            $company_name = $branch->company_name ?? '';

            // Auto-detect agency based on user's agency assignment or regency jurisdiction
            $current_agency_id = 0;
            $current_user_id = get_current_user_id();

            // Try to get agency from user's employee record
            $user_agency = $wpdb->get_var($wpdb->prepare(
                "SELECT agency_id FROM {$wpdb->prefix}app_agency_employees
                 WHERE user_id = %d AND status = 'active'
                 LIMIT 1",
                $current_user_id
            ));

            if ($user_agency) {
                $current_agency_id = intval($user_agency);
            }

            // If no agency from user, try to find agency by regency jurisdiction
            if (!$current_agency_id && $branch->regency_id) {
                $jurisdiction_agency = $wpdb->get_var($wpdb->prepare(
                    "SELECT DISTINCT d.agency_id
                     FROM {$wpdb->prefix}app_agency_jurisdictions j
                     INNER JOIN {$wpdb->prefix}app_agency_divisions d ON j.division_id = d.id
                     WHERE j.jurisdiction_regency_id = %d AND d.status = 'active'
                     LIMIT 1",
                    $branch->regency_id
                ));

                if ($jurisdiction_agency) {
                    $current_agency_id = intval($jurisdiction_agency);
                }
            }

            // Get all agencies
            $agencies = $wpdb->get_results(
                "SELECT id, name FROM {$wpdb->prefix}app_agencies
                 WHERE status = 'active'
                 ORDER BY name ASC"
            );

            // Get divisions for current agency (if any)
            $divisions = [];
            if ($current_agency_id) {
                $divisions = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name FROM {$wpdb->prefix}app_agency_divisions
                     WHERE agency_id = %d AND status = 'active'
                     ORDER BY name ASC",
                    $current_agency_id
                ));
            }

            // Check if template exists
            $template_path = WP_AGENCY_PATH . 'src/Views/admin/company/forms/assignment-form.php';
            if (!file_exists($template_path)) {
                throw new \Exception('Template not found');
            }

            // Render form template
            ob_start();
            include $template_path;
            $form_html = ob_get_clean();

            wp_send_json_success([
                'html' => $form_html
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Assign agency, division, and inspector to branch (All-in-One Assignment)
     */
    public function assignInspector() {
        try {
            // Check nonce - support both wpdt_nonce (auto-wire) and wp_agency_nonce (legacy)
            // Try wpdt_nonce first (auto-wire system), fallback to wp_agency_nonce
            $nonce_verified = false;

            if (isset($_POST['nonce'])) {
                // Try wpdt_nonce first
                if (wp_verify_nonce($_POST['nonce'], 'wpdt_nonce')) {
                    $nonce_verified = true;
                } elseif (wp_verify_nonce($_POST['nonce'], 'wp_agency_nonce')) {
                    $nonce_verified = true;
                }
            }

            if (!$nonce_verified) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Support both 'id' (auto-wire) and 'branch_id' (legacy)
            $branch_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0);
            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            $division_id = isset($_POST['division_id']) ? intval($_POST['division_id']) : 0;
            $inspector_id = isset($_POST['inspector_id']) ? intval($_POST['inspector_id']) : 0;

            error_log("DEBUG NewCompanyController::assignInspector - Received: branch_id={$branch_id}, agency_id={$agency_id}, division_id={$division_id}, inspector_id={$inspector_id}");

            if (!$branch_id || !$agency_id || !$division_id || !$inspector_id) {
                throw new \Exception('Invalid parameters - all fields required');
            }

            // Get branch to verify it exists
            $branch = $this->model->getBranchById($branch_id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            error_log("DEBUG NewCompanyController::assignInspector - Branch found: ID={$branch_id}");

            // Check permission
            if (!$this->validator->canAssignInspector($agency_id)) {
                throw new \Exception('Permission denied');
            }

            // Validate inspector assignment with agency_id and division_id
            $validation = $this->validator->validateInspectorAssignment($branch_id, $inspector_id, $agency_id, $division_id);
            if (!$validation['valid']) {
                throw new \Exception($validation['message']);
            }

            error_log("DEBUG NewCompanyController::assignInspector - Validation passed, proceeding with all-in-one assignment");

            global $wpdb;

            // Get employee.id from user_id (inspector_id is actually user_id from form)
            $employees_table = $wpdb->prefix . 'app_agency_employees';
            $employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$employees_table}
                 WHERE user_id = %d AND agency_id = %d AND division_id = %d AND status = 'active'",
                $inspector_id, $agency_id, $division_id
            ));

            if (!$employee) {
                error_log("DEBUG NewCompanyController::assignInspector - Employee record not found for user_id={$inspector_id}, agency_id={$agency_id}, division_id={$division_id}");
                throw new \Exception('Employee record not found');
            }

            $employee_id = $employee->id;
            error_log("DEBUG NewCompanyController::assignInspector - Found employee.id={$employee_id} for user_id={$inspector_id}");

            $branches_table = $wpdb->prefix . 'app_customer_branches';

            // Update branch with agency_id, division_id, and inspector_id (employee.id) in one query
            $result = $wpdb->update(
                $branches_table,
                [
                    'agency_id' => $agency_id,
                    'division_id' => $division_id,
                    'inspector_id' => $employee_id,  // Use employee.id, not user_id
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $branch_id],
                ['%d', '%d', '%d', '%s'],
                ['%d']
            );

            if ($result === false) {
                error_log("DEBUG NewCompanyController::assignInspector - Database update failed");
                throw new \Exception('Failed to assign agency, division, and inspector');
            }

            error_log("DEBUG NewCompanyController::assignInspector - Assignment successful (rows affected: {$result}), clearing cache");

            // Clear cache
            $this->cache->invalidateDataTableCache('new_company_list', [
                'agency_id' => $agency_id
            ]);
            $this->cache->delete('branch_without_inspector', $agency_id);

            // Clear wp-customer datatable cache if plugin is active
            if (class_exists('\WPCustomer\Cache\CustomerCacheManager')) {
                try {
                    $customerCache = new \WPCustomer\Cache\CustomerCacheManager();
                    $customerCache->invalidateDataTableCache('company_list');
                    error_log("DEBUG - Cleared wp-customer company datatable cache");
                } catch (\Exception $e) {
                    error_log("DEBUG - Failed to clear wp-customer cache: " . $e->getMessage());
                }
            }

            error_log("DEBUG NewCompanyController::assignInspector - Cache cleared, sending success response");

            wp_send_json_success([
                'message' => __('Agency, division, and inspector assigned successfully', 'wp-agency')
            ]);

        } catch (\Exception $e) {
            error_log("DEBUG NewCompanyController::assignInspector - Exception: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
