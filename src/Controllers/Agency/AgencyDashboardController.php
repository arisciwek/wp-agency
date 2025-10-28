<?php
/**
 * Agency Dashboard Controller
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Agency
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Agency/AgencyDashboardController.php
 *
 * Description: Controller untuk Agency dashboard dengan base panel system.
 *              Registers hooks untuk DataTable, stats, dan tabs.
 *              Handles AJAX requests untuk panel content.
 *              Implements TODO-2179 base panel pattern.
 *
 * Dependencies:
 * - wp-app-core base panel system (DashboardTemplate)
 * - AgencyDataTableModel untuk DataTable processing
 * - AgencyModel untuk CRUD operations
 *
 * Changelog:
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 2, Task 2.2)
 * - Register hooks for dashboard components
 * - Implement AJAX handlers for DataTable and panel
 * - Support lazy loading tabs (divisions, employees)
 * - Integrate with base panel system
 */

namespace WPAgency\Controllers\Agency;

use WPAgency\Models\Agency\AgencyDataTableModel;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionDataTableModel;
use WPAgency\Models\Employee\EmployeeDataTableModel;
use WPAppCore\Controllers\DataTable\DataTableController;

class AgencyDashboardController {

    /**
     * @var AgencyDataTableModel DataTable model instance
     */
    private $datatable_model;

    /**
     * @var AgencyModel CRUD model instance
     */
    private $model;

    /**
     * @var DataTableController DataTable controller instance
     */
    private $datatable_controller;

    /**
     * Constructor
     * Register all hooks for dashboard components
     */
    public function __construct() {
// error_log('=== AGENCY DASHBOARD CONTROLLER CONSTRUCTOR ===');

        $this->datatable_model = new AgencyDataTableModel();
// error_log('AgencyDataTableModel initialized');

        $this->model = new AgencyModel();
// error_log('AgencyModel initialized');

        $this->datatable_controller = new DataTableController();
// error_log('DataTableController initialized');

        // Register hooks for dashboard components
// error_log('About to register hooks...');
        $this->register_hooks();
// error_log('Hooks registered successfully');
    }

    /**
     * Register all WordPress hooks
     *
     * Hooks registered:
     * - wpapp_left_panel_content: Render DataTable
     * - wpapp_datatable_stats: Register statistics boxes
     * - wpapp_datatable_tabs: Register tabs
     * - AJAX actions: DataTable, panel details, stats, lazy tabs
     */
    private function register_hooks(): void {
// error_log('=== REGISTERING HOOKS ===');

        // Panel content hook
// error_log('Registering wpapp_left_panel_content hook');
        add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Page header hooks (scope local - echo HTML sendiri)
        add_action('wpapp_page_header_left', [$this, 'render_header_title'], 10, 2);
        add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);

        // Stats cards hook - render inside wpapp-statistics-container
        add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);

        // Filter hooks (scope local - echo HTML sendiri)
        add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

        // Statistics hook
        add_filter('wpapp_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Tabs hook
        add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Tab content injection hook (allows cross-plugin extensibility)
        add_action('wpapp_tab_view_content', [$this, 'render_tab_view_content'], 10, 3);

        // AJAX handlers
        add_action('wp_ajax_get_agencies_datatable', [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_get_agency_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_agency_stats', [$this, 'handle_get_stats']);

        // Lazy loading tab handlers
        add_action('wp_ajax_load_divisions_tab', [$this, 'handle_load_divisions_tab']);
        add_action('wp_ajax_load_employees_tab', [$this, 'handle_load_employees_tab']);

        // DataTable AJAX handlers for lazy-loaded tabs
        add_action('wp_ajax_get_divisions_datatable', [$this, 'handle_divisions_datatable']);
        add_action('wp_ajax_get_employees_datatable', [$this, 'handle_employees_datatable']);
    }

    /**
     * Render DataTable HTML
     *
     * Hooked to: wpapp_left_panel_content
     *
     * Only renders for 'agency' entity
     *
     * @param array $config Configuration array from PanelLayoutTemplate
     */
    public function render_datatable($config): void {
// error_log('=== RENDER DATATABLE DEBUG ===');
// error_log('Config received: ' . print_r($config, true));

        // Extract entity from config
        if (!is_array($config)) {
// error_log('ERROR: Config is not an array, received: ' . gettype($config));
            return;
        }

        $entity = $config['entity'] ?? '';
// error_log('Entity extracted: ' . $entity);

        if ($entity !== 'agency') {
// error_log('Entity is not agency, skipping render');
            return;
        }

        // Include DataTable view file
        $datatable_file = WP_AGENCY_PATH . 'src/Views/DataTable/Templates/datatable.php';
// error_log('DataTable file path: ' . $datatable_file);
// error_log('DataTable file exists: ' . (file_exists($datatable_file) ? 'YES' : 'NO'));

        if (file_exists($datatable_file)) {
// error_log('Including datatable.php');
            include $datatable_file;
// error_log('datatable.php included successfully');
        } else {
// error_log('ERROR: datatable.php not found!');
        }
    }

    /**
     * Render page header title
     *
     * Hooked to: wpapp_page_header_left (action)
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_title($config, $entity): void {
        if ($entity !== 'agency') {
            return;
        }

        $this->render_partial('header-title', [], 'agency');
    }

    /**
     * Render page header buttons
     *
     * Hooked to: wpapp_page_header_right (action)
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_buttons($config, $entity): void {
        if ($entity !== 'agency') {
            return;
        }

        $this->render_partial('header-buttons', [], 'agency');
    }

    /**
     * Render statistics header cards
     *
     * Hooked to: wpapp_statistics_cards_content (action)
     *
     * Renders HTML cards inside wpapp-statistics-container (global scope wrapper)
     * Uses statistics-cards wrapper (global scope) with agency-card items (local scope)
     *
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_cards($entity): void {
        if ($entity !== 'agency') {
            return;
        }

        // Get stats directly from database
        global $wpdb;
        $table = $wpdb->prefix . 'app_agencies';

        /**
         * Filter: Allow filtering statistics WHERE clause
         *
         * Enables access control for statistics (e.g., customer employees only see accessible agencies)
         *
         * @param array $where WHERE conditions
         * @param string $context Statistics context (total, active, inactive)
         * @return array Modified WHERE conditions
         *
         * @since 1.0.0
         */
        $where_total = apply_filters('wpapp_agency_statistics_where', [], 'total');
        $where_active = apply_filters('wpapp_agency_statistics_where', ['status' => 'active'], 'active');
        $where_inactive = apply_filters('wpapp_agency_statistics_where', ['status' => 'inactive'], 'inactive');

        // Build WHERE clause
        $where_total_sql = $this->build_where_clause($where_total);
        $where_active_sql = $this->build_where_clause($where_active);
        $where_inactive_sql = $this->build_where_clause($where_inactive);

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_total_sql}");
        $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_active_sql}");
        $inactive = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where_inactive_sql}");

        // Render using partial template (context: 'agency' not 'tab')
        $this->render_partial('stat-cards', compact('total', 'active', 'inactive'), 'agency');
    }

    /**
     * Render filter controls
     *
     * Hooked to: wpapp_dashboard_filters (action)
     *
     * Renders status filter select dengan class agency-* (scope local)
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_filters($config, $entity): void {
        if ($entity !== 'agency') {
            return;
        }

        // Include status filter partial
        $filter_file = \WP_AGENCY_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';

        if (file_exists($filter_file)) {
            include $filter_file;
        }
    }

    /**
     * Register statistics boxes
     *
     * Hooked to: wpapp_datatable_stats
     *
     * Registers 3 stats: Total, Active, Inactive
     *
     * @param array $stats Existing stats
     * @param string $entity Entity type
     * @return array Modified stats array
     */
    public function register_stats($stats, $entity) {
// error_log('=== REGISTER STATS DEBUG ===');
// error_log('Stats received: ' . print_r($stats, true));
// error_log('Entity: ' . $entity);

        if ($entity !== 'agency') {
// error_log('Entity is not agency, returning original stats');
            return $stats;
        }

        $agency_stats = [
            'total' => [
                'label' => __('Total Disnaker', 'wp-agency'),
                'value' => 0,  // Will be filled by AJAX
                'icon' => 'dashicons-building',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-agency'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-agency'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ]
        ];

// error_log('Returning agency stats: ' . print_r($agency_stats, true));
        return $agency_stats;
    }

    /**
     * Register tabs for right panel
     *
     * Hooked to: wpapp_datatable_tabs
     *
     * Registers 3 tabs:
     * - agency-details: Immediate load
     * - divisions: Lazy load on click
     * - employees: Lazy load on click
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity type
     * @return array Modified tabs array
     */
    public function register_tabs($tabs, $entity) {
// error_log('=== REGISTER TABS DEBUG ===');
// error_log('Tabs received: ' . print_r($tabs, true));
// error_log('Entity: ' . $entity);

        if ($entity !== 'agency') {
// error_log('Entity is not agency, returning original tabs');
            return $tabs;
        }

        $agency_tabs = [
            'info' => [
                'title' => __('Data Disnaker', 'wp-agency'),
                'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/details.php',
                'priority' => 10
            ],
            'divisions' => [
                'title' => __('Unit Kerja', 'wp-agency'),
                'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/divisions.php',
                'priority' => 20
            ],
            'employees' => [
                'title' => __('Staff', 'wp-agency'),
                'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/employees.php',
                'priority' => 30
            ]
        ];

// error_log('Returning agency tabs: ' . print_r($agency_tabs, true));
        return $agency_tabs;
    }

    /**
     * Render tab content via hook (Hook-Based Content Injection Pattern)
     *
     * Hooked to: wpapp_tab_view_content
     * Priority: 10 (renders first, before other plugins)
     *
     * This hook-based approach enables cross-plugin extensibility:
     * - wp-agency responds at priority 10 → renders core content
     * - wp-customer can respond at priority 20 → injects customer stats
     * - Other plugins can inject content at priority 30+
     *
     * Pattern Benefits:
     * - ✅ Pure view templates (no controller logic in view files)
     * - ✅ Multiple plugins can inject content into same tab
     * - ✅ Priority-based content ordering
     * - ✅ WordPress standard hook pattern
     * - ✅ Clean separation of concerns
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (e.g., 'info', 'divisions', 'employees')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_tab_view_content($entity, $tab_id, $data): void {
        // Only respond to agency entity
        if ($entity !== 'agency') {
            return;
        }

        // Extract $agency from $data for view files
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Route to appropriate tab view (pure HTML templates)
        switch ($tab_id) {
            case 'info':
                // Main info tab - comprehensive agency information
                include WP_AGENCY_PATH . 'src/Views/agency/tabs/details.php';
                break;

            case 'divisions':
                // Divisions tab - lazy-loaded DataTable
                include WP_AGENCY_PATH . 'src/Views/agency/tabs/divisions.php';
                break;

            case 'employees':
                // Employees tab - lazy-loaded DataTable
                include WP_AGENCY_PATH . 'src/Views/agency/tabs/employees.php';
                break;

            default:
                // Unknown tab - do nothing (other plugins might handle it)
                break;
        }
    }

    /**
     * Handle DataTable AJAX request
     *
     * AJAX action: get_agencies_datatable
     *
     * Uses AgencyDataTableModel for server-side processing
     * Applies filter hooks for cross-plugin integration
     */
    public function handle_datatable_ajax(): void {
        error_log('=== DATATABLE AJAX REQUEST DEBUG ===');
        error_log('Action: get_agencies_datatable');
        error_log('User ID: ' . get_current_user_id());
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            error_log('ERROR: Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }
        error_log('Nonce verified successfully');

        // Check permission (filtered by wp-customer via hooks)
        $can_access = current_user_can('view_agency_list');
// error_log('User has view_agency_list capability: ' . ($can_access ? 'YES' : 'NO'));

        $can_access = apply_filters('wp_agency_can_access_agencies_page', $can_access, get_current_user_id());
// error_log('After filter, can_access: ' . ($can_access ? 'YES' : 'NO'));

        if (!$can_access) {
// error_log('ERROR: Permission denied');
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
// error_log('Calling datatable_model->get_datatable_data()');

            // Get DataTable data using new model
            $response = $this->datatable_model->get_datatable_data($_POST);

// error_log('DataTable response: ' . print_r($response, true));
// error_log('Total records: ' . ($response['recordsTotal'] ?? 'N/A'));
// error_log('Filtered records: ' . ($response['recordsFiltered'] ?? 'N/A'));
// error_log('Data rows: ' . count($response['data'] ?? []));

            wp_send_json($response);

        } catch (\Exception $e) {
// error_log('ERROR: Exception in handle_datatable_ajax: ' . $e->getMessage());
// error_log('Stack trace: ' . $e->getTraceAsString());

            wp_send_json_error([
                'message' => __('Error loading data', 'wp-agency')
            ]);
        }
    }

    /**
     * Handle get agency details AJAX request
     *
     * AJAX action: get_agency_details
     *
     * Returns agency data for right panel display
     */
    public function handle_get_details(): void {
        error_log('=== handle_get_details called ===');
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            error_log('Nonce verification FAILED');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }
        error_log('Nonce verification OK');

        $agency_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        error_log('Agency ID: ' . $agency_id);

        if (!$agency_id) {
            error_log('Invalid agency ID');
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permission - use same capability as menu access
        // If user can view the list page, they can view details
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);
        error_log('Permission check (view_agency_list): ' . ($can_view ? 'OK' : 'DENIED'));

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            error_log('Calling model->find(' . $agency_id . ')');
            $agency = $this->model->find($agency_id);
            error_log('Model result: ' . print_r($agency, true));

            if (!$agency) {
                error_log('Agency not found in database');
                wp_send_json_error(['message' => __('Agency not found', 'wp-agency')]);
                return;
            }

            // Prepare response with proper format for panel manager
            $tabs_content = $this->render_tab_contents($agency);

            $response = [
                'title' => $agency->name ?? __('Agency Details', 'wp-agency'),
                'tabs' => $tabs_content,
                'agency' => $agency // Keep for backward compatibility
            ];

            error_log('=== FINAL RESPONSE DEBUG ===');
            error_log('Response title: ' . $response['title']);
            error_log('Response tabs count: ' . count($response['tabs']));
            error_log('Response tabs keys: ' . print_r(array_keys($response['tabs']), true));
            foreach ($response['tabs'] as $tab_id => $content) {
                error_log("Tab {$tab_id} final length: " . strlen($content));
            }
            error_log('Response agency: ' . print_r($agency, true));
            error_log('Full response structure: ' . print_r([
                'success' => true,
                'data' => [
                    'title' => $response['title'],
                    'tabs_count' => count($response['tabs']),
                    'agency_id' => $agency->id ?? 'N/A'
                ]
            ], true));
            error_log('=== END FINAL RESPONSE DEBUG ===');

            wp_send_json_success($response);

        } catch (\Exception $e) {
            error_log('EXCEPTION in get_details: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());

            wp_send_json_error([
                'message' => __('Error loading agency', 'wp-agency')
            ]);
        }
    }

    /**
     * Handle get statistics AJAX request
     *
     * AJAX action: get_agency_stats
     *
     * Returns counts for statistics boxes
     */
    public function handle_get_stats(): void {
        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Check permission
        $can_access = current_user_can('view_agency_list');
        $can_access = apply_filters('wp_agency_can_access_agencies_page', $can_access, get_current_user_id());

        if (!$can_access) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            global $wpdb;
            $table = $wpdb->prefix . 'app_agencies';

            // Base query - will be filtered by wp-customer hooks
            $where_conditions = ['1=1'];

            /**
             * Filter WHERE conditions for stats
             *
             * Allows wp-customer to filter agencies by user's branches
             *
             * @param array $where_conditions WHERE clauses
             * @param int $user_id Current user ID
             */
            $where_conditions = apply_filters(
                'wp_agency_stats_where',
                $where_conditions,
                get_current_user_id()
            );

            $where_clause = implode(' AND ', $where_conditions);

            // Get counts
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} a WHERE {$where_clause}");
            $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} a WHERE {$where_clause} AND a.status = 'active'");
            $inactive = $wpdb->get_var("SELECT COUNT(*) FROM {$table} a WHERE {$where_clause} AND a.status = 'inactive'");

            wp_send_json_success([
                'stats' => [
                    'total' => (int) $total,
                    'active' => (int) $active,
                    'inactive' => (int) $inactive
                ]
            ]);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
// error_log('Get Agency Stats Error: ' . $e->getMessage());
            }

            wp_send_json_error([
                'message' => __('Error loading statistics', 'wp-agency')
            ]);
        }
    }

    /**
     * Handle load divisions tab AJAX request
     *
     * AJAX action: load_divisions_tab
     *
     * Lazy loads divisions DataTable when tab is clicked
     * Implements Perfex CRM lazy loading pattern
     */
    public function handle_load_divisions_tab(): void {
        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;

        if (!$agency_id) {
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permission - use same capability as menu access
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            // Generate divisions DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-divisions-datatable', compact('agency_id'), 'agency');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
// error_log('Load Divisions Tab Error: ' . $e->getMessage());
            }

            wp_send_json_error([
                'message' => __('Error loading divisions', 'wp-agency')
            ]);
        }
    }

    /**
     * Handle load employees tab AJAX request
     *
     * AJAX action: load_employees_tab
     *
     * Lazy loads employees DataTable when tab is clicked
     * Implements Perfex CRM lazy loading pattern
     */
    public function handle_load_employees_tab(): void {
        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;

        if (!$agency_id) {
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permission - use same capability as menu access
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            // Generate employees DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-employees-datatable', compact('agency_id'), 'agency');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
// error_log('Load Employees Tab Error: ' . $e->getMessage());
            }

            wp_send_json_error([
                'message' => __('Error loading employees', 'wp-agency')
            ]);
        }
    }

    /**
     * Handle divisions DataTable AJAX request
     *
     * AJAX action: get_divisions_datatable
     *
     * Called by DataTable initialization in divisions tab
     * for server-side processing
     */
    public function handle_divisions_datatable(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Check permission
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, 0);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            $model = new DivisionDataTableModel();
            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading divisions', 'wp-agency')]);
        }
    }

    /**
     * Handle employees DataTable AJAX request
     *
     * AJAX action: get_employees_datatable
     *
     * Called by DataTable initialization in employees tab
     * for server-side processing
     */
    public function handle_employees_datatable(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Check permission
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, 0);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            $model = new EmployeeDataTableModel();
            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-agency')]);
        }
    }

    /**
     * Render tab contents using Hook-Based Content Injection Pattern
     *
     * This method triggers the wpapp_tab_view_content hook, allowing:
     * - wp-agency to render core content (priority 10)
     * - Other plugins to inject additional content (priority 20+)
     *
     * Pattern Flow:
     * 1. Prepare data array with $agency object
     * 2. Start output buffering
     * 3. Trigger hook → Multiple plugins can respond
     * 4. Capture combined output
     *
     * @param object $agency Agency object to render
     * @return array Array of tab_id => content_html
     */
    private function render_tab_contents($agency): array {
        error_log('=== RENDER TAB CONTENTS START (HOOK-BASED PATTERN) ===');
        $tabs = [];

        // Get registered tabs
        $registered_tabs = apply_filters('wpapp_datatable_tabs', [], 'agency');
        error_log('Registered tabs: ' . print_r(array_keys($registered_tabs), true));

        foreach ($registered_tabs as $tab_id => $tab_config) {
            error_log("Processing tab: {$tab_id}");

            // Start output buffering
            ob_start();

            // Prepare data for hook
            $data = [
                'agency' => $agency,
                'tab_config' => $tab_config
            ];

            // Trigger hook - allows multiple plugins to inject content
            // Priority 10: wp-agency core content
            // Priority 20+: Other plugins (wp-customer, etc.)
            do_action('wpapp_tab_view_content', 'agency', $tab_id, $data);

            // Capture combined output from all hooked functions
            $content = ob_get_clean();
            $content_length = strlen($content);

            error_log("Tab {$tab_id} content length: {$content_length} bytes");
            error_log("Tab {$tab_id} content preview: " . substr($content, 0, 100));

            $tabs[$tab_id] = $content;
        }

        error_log('Total tabs rendered: ' . count($tabs));
        error_log('=== RENDER TAB CONTENTS END ===');

        return $tabs;
    }

    /**
     * Render partial template file
     *
     * Helper method to include partial template files with extracted variables.
     * Used for non-tab partials like headers, stats, ajax-datatables.
     *
     * NOTE: Tab partials are NO LONGER used (merged into tab files).
     * This method is only for:
     * - Header partials (header-title, header-buttons)
     * - Stats partials (stat-cards)
     * - AJAX partials (ajax-divisions-datatable, ajax-employees-datatable)
     *
     * Template locations:
     * - General partials: src/Views/agency/partials/{$partial}.php
     *
     * @param string $partial Partial template name (without .php extension)
     * @param array  $data    Variables to extract and pass to template
     * @param string $context Template context (default 'agency')
     * @return void
     * @since 2.0.0
     */
    private function render_partial($partial, $data = [], $context = 'agency'): void {
        // Extract variables for template
        if (!empty($data)) {
            extract($data);
        }

        // Determine template path - only 'agency' context now
        $template = WP_AGENCY_PATH . "src/Views/agency/partials/{$partial}.php";

        // Include template if exists
        if (file_exists($template)) {
            include $template;
        } else {
            error_log("Template not found: {$template}");
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<p>' . sprintf(__('Template "%s" not found', 'wp-agency'), esc_html($partial)) . '</p>';
            }
        }
    }

    /**
     * Build WHERE clause from conditions array
     *
     * Converts array of WHERE conditions to SQL WHERE clause string.
     * Supports both associative array (column => value) and indexed array (raw SQL conditions).
     *
     * @param array $conditions WHERE conditions
     * @return string WHERE clause SQL (with WHERE keyword, or empty string)
     *
     * @since 1.0.0
     */
    private function build_where_clause(array $conditions): string {
        if (empty($conditions)) {
            return '';
        }

        global $wpdb;
        $where_parts = [];

        foreach ($conditions as $key => $value) {
            if (is_numeric($key)) {
                // Indexed array - raw SQL condition (already escaped by filter)
                $where_parts[] = $value;
            } else {
                // Associative array - column => value
                $where_parts[] = $wpdb->prepare("{$key} = %s", $value);
            }
        }

        return 'WHERE ' . implode(' AND ', $where_parts);
    }
}
