<?php
/**
 * New Company Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Company/NewCompanyController.php
 *
 * Description: Controller untuk mengelola data company (branch) yang belum memiliki inspector.
 *              Menangani operasi DataTable dan assignment inspector.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk DataTables.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 * - Added DataTables integration
 * - Added permission checks
 * - Added cache support
 */

namespace WPAgency\Controllers\Company;

use WPAgency\Models\Company\NewCompanyModel;
use WPAgency\Validators\Company\NewCompanyValidator;
use WPAgency\Cache\AgencyCacheManager;

class NewCompanyController {
    private NewCompanyModel $model;
    private NewCompanyValidator $validator;
    private AgencyCacheManager $cache;

    public function __construct() {
        $this->model = new NewCompanyModel();
        $this->validator = new NewCompanyValidator();
        $this->cache = new AgencyCacheManager();

        // Register AJAX handlers
        add_action('wp_ajax_handle_new_company_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_new_company_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_assign_inspector', [$this, 'assignInspector']);
        add_action('wp_ajax_get_available_inspectors', [$this, 'getAvailableInspectors']);
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handleDataTableRequest() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            // Check permissions
            if (!$this->validator->canViewNewCompanies($agency_id)) {
                throw new \Exception('Permission denied');
            }

            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $columns = ['code', 'company_name', 'division_name', 'regency_name', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'code';
            if ($orderBy === 'actions') {
                $orderBy = 'code';
            }

            // Get data from model
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
            foreach ($result['data'] as $branch) {
                $data[] = [
                    'id' => $branch->id,
                    'code' => esc_html($branch->code),
                    'company_name' => esc_html($branch->company_name),
                    'division_name' => esc_html($branch->division_name ?? '-'),
                    'regency_name' => esc_html($branch->regency_name ?? '-'),
                    'actions' => $this->generateActionButtons($branch)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data,
            ];

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 400);
        }
    }

    /**
     * Generate action buttons for DataTable
     */
    private function generateActionButtons($branch) {
        $actions = '';

        // View button
        $actions .= sprintf(
            '<button type="button" class="button view-company" data-id="%d" title="%s">
                <i class="dashicons dashicons-visibility"></i>
            </button> ',
            $branch->id,
            __('Lihat', 'wp-agency')
        );

        // Assign inspector button
        if ($this->validator->canAssignInspector($branch->agency_id)) {
            $actions .= sprintf(
                '<button type="button" class="button assign-inspector" data-id="%d" data-company="%s" title="%s">
                    <i class="dashicons dashicons-admin-users"></i>
                </button>',
                $branch->id,
                esc_attr($branch->company_name),
                __('Assign Inspector', 'wp-agency')
            );
        }

        return $actions;
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

            // Get inspectors from agency employees filtered by division and role
            global $wpdb;
            $employees_table = $wpdb->prefix . 'app_agency_employees';
            $divisions_table = $wpdb->prefix . 'app_agency_divisions';

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
                $agency_id,
                $branch->division_id,
                '%agency%',
                '%pengawas%'
            );

            // Debug: Log the raw query
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Raw Query: " . $query);
            error_log("DEBUG NewCompanyController::getAvailableInspectors - Agency ID: {$agency_id}, Branch Division ID: {$branch->division_id}");

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
     * Assign inspector to branch
     */
    public function assignInspector() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
            $inspector_id = isset($_POST['inspector_id']) ? intval($_POST['inspector_id']) : 0;

            error_log("DEBUG NewCompanyController::assignInspector - Received: branch_id={$branch_id}, inspector_id={$inspector_id}");

            if (!$branch_id || !$inspector_id) {
                throw new \Exception('Invalid parameters');
            }

            // Get branch to check agency
            $branch = $this->model->getBranchById($branch_id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            error_log("DEBUG NewCompanyController::assignInspector - Branch found, agency_id: {$branch->agency_id}, current inspector_id: " . ($branch->inspector_id ?? 'NULL'));

            // Check permission
            if (!$this->validator->canAssignInspector($branch->agency_id)) {
                throw new \Exception('Permission denied');
            }

            // Validate inspector assignment
            $validation = $this->validator->validateInspectorAssignment($branch_id, $inspector_id);
            if (!$validation['valid']) {
                throw new \Exception($validation['message']);
            }

            error_log("DEBUG NewCompanyController::assignInspector - Validation passed, proceeding with assignment");

            // Assign inspector
            $result = $this->model->assignInspector($branch_id, $inspector_id);

            if (!$result) {
                error_log("DEBUG NewCompanyController::assignInspector - Model assignInspector returned false");
                throw new \Exception('Failed to assign inspector');
            }

            error_log("DEBUG NewCompanyController::assignInspector - Assignment successful, clearing cache");

            // Clear cache
            $this->cache->invalidateDataTableCache('new_company_list', [
                'agency_id' => $branch->agency_id
            ]);
            $this->cache->delete('branch_without_inspector', $branch->agency_id);

            // Clear wp-customer datatable cache if plugin is active
            $cacheFile = WP_PLUGIN_DIR . '/wp-customer/src/Cache/CustomerCacheManager.php';
            if (file_exists($cacheFile)) {
                require_once $cacheFile;
                if (class_exists('\WPCustomer\Cache\CustomerCacheManager')) {
                    try {
                        $customerCache = new \WPCustomer\Cache\CustomerCacheManager();
                        $customerCache->clearAll();
                        error_log("DEBUG NewCompanyController::assignInspector - Cleared all wp-customer caches");
                    } catch (\Exception $e) {
                        error_log("DEBUG NewCompanyController::assignInspector - Failed to clear wp-customer cache: " . $e->getMessage());
                    }
                } else {
                    error_log("DEBUG NewCompanyController::assignInspector - CustomerCacheManager class not found after require");
                }
            } else {
                error_log("DEBUG NewCompanyController::assignInspector - CustomerCacheManager file not found");
            }

            error_log("DEBUG NewCompanyController::assignInspector - Cache cleared, sending success response");

            wp_send_json_success([
                'message' => __('Inspector assigned successfully', 'wp-agency')
            ]);

        } catch (\Exception $e) {
            error_log("DEBUG NewCompanyController::assignInspector - Exception: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
