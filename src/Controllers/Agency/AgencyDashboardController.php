<?php
/**
 * Agency Dashboard Controller
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Agency
 * @version     1.6.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Agency/AgencyDashboardController.php
 *
 * Description: Controller untuk Agency dashboard dengan dual panel system.
 *              Registers hooks untuk DataTable, stats, dan tabs.
 *              Handles AJAX requests untuk panel content.
 *              Migrated to wp-datatable from wp-app-core.
 *
 * Dependencies:
 * - wp-datatable dual panel system (DashboardTemplate)
 * - AgencyDataTableModel untuk DataTable processing
 * - AgencyModel untuk CRUD operations
 *
 * Changelog:
 * 1.6.1 - 2025-12-25 (Security Fix)
 * - FIXED: Nonce mismatch - changed from wpdt_panel_nonce to wpdt_nonce
 * - REASON: BaseAssets uses wpdt_nonce, backend must match
 * - BENEFIT: Security check now passes correctly
 *
 * 1.6.0 - 2025-12-25 (Migration to wp-datatable)
 * - MIGRATED: From wp-app-core to wp-datatable framework
 * - UPDATED: All hooks from wpapp_ to wpdt_ prefix
 * - UPDATED: All nonces from wpapp_panel_nonce to wpdt_nonce
 * - UPDATED: All filter hooks to use wpdt_ prefix
 * - ADDED: signal_dual_panel() method for dual panel detection
 * - PATTERN: Strategy-based asset loading via dual panel signal
 *
 * 1.5.0 - 2025-11-01 (TODO-3097)
 * - ADDED: New tab "Perusahaan Baru" (new-companies)
 * - ADDED: render_new_companies_tab() method
 * - ADDED: handle_load_new_companies_tab() AJAX handler
 * - ADDED: handle_new_companies_datatable() AJAX handler
 * - Shows branches without inspector (inspector_id IS NULL)
 * - Following centralization pattern from TODO-3094/3095/3096
 *
 * 1.4.0 - 2025-10-29 (TODO-3087 Pembahasan-03)
 * - RESTORED: wpdt_tab_view_after_content hook in render_tab_contents()
 * - REASON: Tab files use pure HTML pattern (not TabViewTemplate::render())
 * - DECISION: Entity controller provides extension hook directly
 * - BENEFIT: Extension content works, no dependency on TabViewTemplate
 *
 * 1.3.0 - 2025-10-29 (TODO-3087 Pembahasan-01)
 * - REMOVED: Duplicate wpdt_tab_view_after_content hook from render_tab_contents()
 * - REASON: TabViewTemplate already provides this hook (wp-app-core TODO-1188)
 * - BENEFIT: Eliminate duplication, single source of truth
 * - PATTERN: Consistent with wp-app-core framework design
 *
 * 1.2.0 - 2025-10-29 (TODO-3086 Review-01)
 * - REFACTORED: Per-tab hook registration (removed switch-case pattern)
 * - ADDED: render_info_tab(), render_divisions_tab(), render_employees_tab()
 * - REMOVED: render_tab_view_content() with switch-case
 * - BENEFIT: Better decoupling, each tab independently registered
 * - RENAMED: details.php → info.php (consistency with tab_id)
 *
 * 1.1.0 - 2025-10-29 (TODO-3086)
 * - ADDED: wpdt_tab_view_after_content hook in render_tab_contents()
 * - PATTERN: Separate core content from extension content injection
 * - BENEFIT: Consistent with TabViewTemplate pattern (wp-app-core TODO-1188)
 * - FIX: Prevents duplicate rendering from wp-customer statistics injection
 *
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
     * - wpdt_left_panel_content: Render DataTable
     * - wpdt_datatable_stats: Register statistics boxes
     * - wpdt_datatable_tabs: Register tabs
     * - wpdt_use_dual_panel: Signal dual panel usage
     * - AJAX actions: DataTable, panel details, stats, lazy tabs
     */
    private function register_hooks(): void {
// error_log('=== REGISTERING HOOKS ===');

        // Dual panel signal hook (wp-datatable)
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel'], 10, 1);

        // Panel content hook
// error_log('Registering wpdt_left_panel_content hook');
        add_action('wpdt_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Page header hooks (scope local - echo HTML sendiri)
        add_action('wpdt_page_header_left', [$this, 'render_header_title'], 10, 2);
        add_action('wpdt_page_header_right', [$this, 'render_header_buttons'], 10, 2);

        // Stats cards hook - render inside wpdt-statistics-container
        add_action('wpdt_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);

        // Filter hooks (scope local - echo HTML sendiri)
        add_action('wpdt_dashboard_filters', [$this, 'render_filters'], 10, 2);

        // Statistics hook
        add_filter('wpdt_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Tabs hook
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Tab content injection hooks (per-tab registration for better decoupling)
        add_action('wpdt_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_divisions_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_employees_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_new_companies_tab'], 10, 3);

        // AJAX handlers
        add_action('wp_ajax_get_agencies_datatable', [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_get_agency_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_agency_stats', [$this, 'handle_get_stats']);

        // Lazy loading tab handlers
        add_action('wp_ajax_load_divisions_tab', [$this, 'handle_load_divisions_tab']);
        add_action('wp_ajax_load_employees_tab', [$this, 'handle_load_employees_tab']);
        add_action('wp_ajax_load_new_companies_tab', [$this, 'handle_load_new_companies_tab']);

        // DataTable AJAX handlers for lazy-loaded tabs
        add_action('wp_ajax_get_divisions_datatable', [$this, 'handle_divisions_datatable']);
        add_action('wp_ajax_get_employees_datatable', [$this, 'handle_employees_datatable']);
        add_action('wp_ajax_get_new_companies_datatable', [$this, 'handle_new_companies_datatable']);
    }

    /**
     * Signal dual panel usage for wp-agency-disnaker page
     *
     * Hooked to: wpdt_use_dual_panel (filter)
     *
     * Tells wp-datatable AssetController to load dual panel assets
     * when on the Disnaker page (wp-agency-disnaker)
     *
     * @param bool $use Current signal state
     * @return bool True if on Disnaker page, otherwise unchanged
     */
    public function signal_dual_panel($use): bool {
        if (isset($_GET['page']) && $_GET['page'] === 'wp-agency-disnaker') {
            return true;
        }
        return $use;
    }

    /**
     * Render DataTable HTML
     *
     * Hooked to: wpdt_left_panel_content
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
     * Hooked to: wpdt_page_header_left (action)
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
     * Hooked to: wpdt_page_header_right (action)
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
     * Hooked to: wpdt_statistics_cards_content (action)
     *
     * Renders HTML cards inside wpdt-statistics-container (global scope wrapper)
     * Uses statistics-cards wrapper (global scope) with agency-card items (local scope)
     *
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_cards($entity): void {
        if ($entity !== 'agency') {
            return;
        }

        // Use DataTableModel for consistent filtering (same as DataTable & wp-customer pattern)
        $model = new \WPAgency\Models\Agency\AgencyDataTableModel();

        // Get filtered counts using model method
        // This applies wpapp_datatable_agencies_where filter automatically
        $total = $model->get_total_count('all');
        $active = $model->get_total_count('active');
        $inactive = $model->get_total_count('inactive');

        // Render using partial template (context: 'agency' not 'tab')
        $this->render_partial('stat-cards', compact('total', 'active', 'inactive'), 'agency');
    }

    /**
     * Render filter controls
     *
     * Hooked to: wpdt_dashboard_filters (action)
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
     * Hooked to: wpdt_datatable_stats
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
     * Hooked to: wpdt_datatable_tabs
     *
     * Registers 4 tabs:
     * - info: Agency information (immediate load)
     * - divisions: Divisions DataTable (lazy load on click)
     * - employees: Employees DataTable (lazy load on click)
     * - new-companies: New Companies DataTable (lazy load on click)
     *
     * Pattern: Hook-based content injection (entity-owned)
     * - register_tabs() defines tab metadata (title, priority)
     * - render_tab_contents() triggers hooks (Line 933, 938)
     * - render_info_tab() / render_divisions_tab() / render_employees_tab()
     *   respond to hooks and include template files
     *
     * Hook flow:
     * 1. wpdt_tab_view_content (Priority 10) - Core content rendering
     * 2. wpdt_tab_view_after_content (Priority 20+) - Extension content injection
     *
     * @see render_tab_contents() Line 910-948
     * @see render_info_tab() Line 683-704
     * @see render_divisions_tab() Line 732-752
     * @see render_employees_tab() Line 780-800
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
                'priority' => 10
            ],
            'divisions' => [
                'title' => __('Unit Kerja', 'wp-agency'),
                'priority' => 20
            ],
            'employees' => [
                'title' => __('Staff', 'wp-agency'),
                'priority' => 30
            ]
        ];

        // 'Perusahaan Baru' tab - Only for agency roles
        // Customer roles (customer_admin, customer_branch_admin, customer_employee) cannot see this tab
        if ($this->user_has_agency_role()) {
            $agency_tabs['new-companies'] = [
                'title' => __('Perusahaan Baru', 'wp-agency'),
                'priority' => 40
            ];
        }

// error_log('Returning agency tabs: ' . print_r($agency_tabs, true));
        return $agency_tabs;
    }

    /**
     * Render info tab HTML content
     *
     * Hook handler for wpdt_tab_view_content (info tab).
     * Renders the actual HTML content for the info tab.
     *
     * Entity-owned hook implementation pattern:
     * - render_tab_contents() triggers wpdt_tab_view_content hook
     * - This method responds to that hook for 'agency' entity, 'info' tab
     * - Priority 10: Core content rendering
     * - Priority 20+: Extension plugins use wpdt_tab_view_after_content
     *
     * Hook Flow:
     * 1. render_tab_contents() → do_action('wpdt_tab_view_content')
     * 2. This method → Includes info.php template
     * 3. Extension hooks fire after (wp-customer can inject statistics)
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (should be 'info')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_info_tab($entity, $tab_id, $data): void {
        // Only respond to agency entity and info tab
        if ($entity !== 'agency' || $tab_id !== 'info') {
            return;
        }

        // Extract $agency from $data for view file
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include pure HTML view template
        include WP_AGENCY_PATH . 'src/Views/agency/tabs/info.php';
    }

    /**
     * Render divisions tab content
     *
     * Hook handler for wpdt_tab_view_content (divisions tab).
     * Each tab independently registered for better decoupling.
     *
     * Entity-owned hook implementation pattern:
     * - render_tab_contents() triggers wpdt_tab_view_content hook
     * - This method responds to that hook for 'agency' entity, 'divisions' tab
     * - Priority 10: Core content rendering
     * - Priority 20+: Extension plugins use wpdt_tab_view_after_content
     *
     * Hook Flow:
     * 1. render_tab_contents() → do_action('wpdt_tab_view_content')
     * 2. This method → Includes divisions.php template (DataTable)
     * 3. Extension hooks fire after (other plugins can inject additional content)
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (should be 'divisions')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_divisions_tab($entity, $tab_id, $data): void {
        // Only respond to agency entity and divisions tab
        if ($entity !== 'agency' || $tab_id !== 'divisions') {
            return;
        }

        // Extract $agency from $data for view file
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include lazy-loaded DataTable view
        include WP_AGENCY_PATH . 'src/Views/agency/tabs/divisions.php';
    }

    /**
     * Render employees tab content
     *
     * Hook handler for wpdt_tab_view_content (employees tab).
     * Each tab independently registered for better decoupling.
     *
     * Entity-owned hook implementation pattern:
     * - render_tab_contents() triggers wpdt_tab_view_content hook
     * - This method responds to that hook for 'agency' entity, 'employees' tab
     * - Priority 10: Core content rendering
     * - Priority 20+: Extension plugins use wpdt_tab_view_after_content
     *
     * Hook Flow:
     * 1. render_tab_contents() → do_action('wpdt_tab_view_content')
     * 2. This method → Includes employees.php template (DataTable)
     * 3. Extension hooks fire after (other plugins can inject additional content)
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (should be 'employees')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_employees_tab($entity, $tab_id, $data): void {
        // Only respond to agency entity and employees tab
        if ($entity !== 'agency' || $tab_id !== 'employees') {
            return;
        }

        // Extract $agency from $data for view file
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include lazy-loaded DataTable view
        include WP_AGENCY_PATH . 'src/Views/agency/tabs/employees.php';
    }

    /**
     * Render new companies tab HTML content
     *
     * Hook handler for wpdt_tab_view_content (new-companies tab).
     * Renders the actual HTML content for the new companies tab.
     * Shows branches without inspector (inspector_id IS NULL).
     *
     * Entity-owned hook implementation pattern:
     * - render_tab_contents() triggers wpdt_tab_view_content hook
     * - This method responds to that hook for 'agency' entity, 'new-companies' tab
     * - Priority 10: Core content rendering
     * - Priority 20+: Extension plugins use wpdt_tab_view_after_content
     *
     * Hook Flow:
     * 1. render_tab_contents() → do_action('wpdt_tab_view_content')
     * 2. This method → Includes new-companies.php template
     * 3. Extension hooks fire after
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (should be 'new-companies')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_new_companies_tab($entity, $tab_id, $data): void {
        // Only respond to agency entity and new-companies tab
        if ($entity !== 'agency' || $tab_id !== 'new-companies') {
            return;
        }

        // Extract $agency from $data for view file
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include lazy-loaded DataTable view
        include WP_AGENCY_PATH . 'src/Views/agency/tabs/new-companies.php';
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
        // error_log('=== DATATABLE AJAX REQUEST DEBUG ===');
        // error_log('Action: get_agencies_datatable');
        // error_log('User ID: ' . get_current_user_id());
        // error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('ERROR: Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }
        // error_log('Nonce verified successfully');

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
        // error_log('=== handle_get_details called ===');
        // error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('Nonce verification FAILED');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }
        // error_log('Nonce verification OK');

        $agency_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        // error_log('Agency ID: ' . $agency_id);

        if (!$agency_id) {
            error_log('Invalid agency ID');
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permission - use same capability as menu access
        // If user can view the list page, they can view details
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);
        // error_log('Permission check (view_agency_list): ' . ($can_view ? 'OK' : 'DENIED'));

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            // error_log('Calling model->find(' . $agency_id . ')');
            $agency = $this->model->find($agency_id);
            // error_log('Model result: ' . print_r($agency, true));

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

            // error_log('=== FINAL RESPONSE DEBUG ===');
            // error_log('Response title: ' . $response['title']);
            // error_log('Response tabs count: ' . count($response['tabs']));
            // error_log('Response tabs keys: ' . print_r(array_keys($response['tabs']), true));
            // foreach ($response['tabs'] as $tab_id => $content) {
            //     error_log("Tab {$tab_id} final length: " . strlen($content));
            // }
            // error_log('Response agency: ' . print_r($agency, true));
            // error_log('Full response structure: ' . print_r([
            //     'success' => true,
            //     'data' => [
            //         'title' => $response['title'],
            //         'tabs_count' => count($response['tabs']),
            //         'agency_id' => $agency->id ?? 'N/A'
            //     ]
            // ], true));
            // error_log('=== END FINAL RESPONSE DEBUG ===');

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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
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
     * Handle load new companies tab AJAX request
     *
     * AJAX action: load_new_companies_tab
     *
     * Lazy loads new companies DataTable when tab is clicked
     * Shows branches without inspector (inspector_id IS NULL)
     * Implements Perfex CRM lazy loading pattern
     */
    public function handle_load_new_companies_tab(): void {
        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;

        if (!$agency_id) {
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
            return;
        }

        // Check permission - view_own_agency based on agency_id
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            // Generate new companies DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-new-companies-datatable', compact('agency_id'), 'agency');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Load New Companies Tab Error: ' . $e->getMessage());
            }

            wp_send_json_error([
                'message' => __('Error loading new companies', 'wp-agency')
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
        error_log('=== DIVISIONS DATATABLE AJAX HANDLER CALLED ===');
        error_log('POST agency_id: ' . ($_POST['agency_id'] ?? 'NOT SET'));
        error_log('POST status_filter: ' . ($_POST['status_filter'] ?? 'NOT SET'));
        error_log('POST action: ' . ($_POST['action'] ?? 'NOT SET'));

        // Verify nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('ERROR: Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }
        error_log('Nonce verified OK');

        // Check permission
        $can_view = current_user_can('view_agency_list');
        error_log('User has view_agency_list: ' . ($can_view ? 'YES' : 'NO'));

        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, 0);
        error_log('After filter, can_view: ' . ($can_view ? 'YES' : 'NO'));

        if (!$can_view) {
            error_log('ERROR: Permission denied');
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            error_log('Creating DivisionDataTableModel...');
            $model = new DivisionDataTableModel();

            error_log('Calling get_datatable_data...');
            $response = $model->get_datatable_data($_POST);

            error_log('Total records: ' . ($response['recordsTotal'] ?? 'N/A'));
            error_log('Filtered records: ' . ($response['recordsFiltered'] ?? 'N/A'));
            error_log('Data rows: ' . count($response['data'] ?? []));

            // Log first 3 rows with wilayah_kerja data
            if (isset($response['data']) && is_array($response['data'])) {
                $sample_rows = array_slice($response['data'], 0, 3);
                foreach ($sample_rows as $index => $row) {
                    error_log(sprintf(
                        'Row %d: code=%s, name=%s, wilayah_kerja=%s',
                        $index,
                        $row['code'] ?? 'N/A',
                        $row['name'] ?? 'N/A',
                        $row['wilayah_kerja'] ?? 'N/A'
                    ));
                }
            }

            wp_send_json($response);
        } catch (\Exception $e) {
            error_log('EXCEPTION in handle_divisions_datatable: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
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
     * Handle new companies DataTable AJAX request
     *
     * AJAX action: get_new_companies_datatable
     *
     * Called by DataTable initialization in new-companies tab
     * for server-side processing. Uses NewCompanyDataTableModel.
     */
    public function handle_new_companies_datatable(): void {
        // Verify nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Check permission - view_own_agency
        $can_view = current_user_can('view_agency_list');
        $can_view = apply_filters('wp_agency_can_view_agency', $can_view, 0);

        if (!$can_view) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        try {
            $model = new \WPAgency\Models\Company\NewCompanyDataTableModel();
            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('New Companies DataTable Error: ' . $e->getMessage());
            }
            wp_send_json_error(['message' => __('Error loading new companies', 'wp-agency')]);
        }
    }

    /**
     * Render tab contents using Hook-Based Content Injection Pattern
     *
     * This method triggers the wpdt_tab_view_content hook, allowing:
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
        // error_log('=== RENDER TAB CONTENTS START (HOOK-BASED PATTERN) ===');
        $tabs = [];

        // Get registered tabs
        $registered_tabs = apply_filters('wpdt_datatable_tabs', [], 'agency');
        // error_log('Registered tabs: ' . print_r(array_keys($registered_tabs), true));

        foreach ($registered_tabs as $tab_id => $tab_config) {
            // error_log("Processing tab: {$tab_id}");

            // Start output buffering
            ob_start();

            // Prepare data for hook
            $data = [
                'agency' => $agency,
                'tab_config' => $tab_config
            ];

            // Trigger hooks - allows multiple plugins to inject content
            // Priority 10: wp-agency core content (wpdt_tab_view_content)
            // Priority 20+: wp-customer extension content (wpdt_tab_view_after_content)
            do_action('wpdt_tab_view_content', 'agency', $tab_id, $data);

            // Extension hook for additional content injection
            // This hook is provided here (not from TabViewTemplate) because
            // tab files use pure HTML pattern, not TabViewTemplate::render()
            do_action('wpdt_tab_view_after_content', 'agency', $tab_id, $data);

            // Capture combined output from all hooked functions
            $content = ob_get_clean();
            // $content_length = strlen($content);

            // error_log("Tab {$tab_id} content length: {$content_length} bytes");
            // error_log("Tab {$tab_id} content preview: " . substr($content, 0, 100));

            $tabs[$tab_id] = $content;
        }

        // error_log('Total tabs rendered: ' . count($tabs));
        // error_log('=== RENDER TAB CONTENTS END ===');

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

    /**
     * Check if current user has any agency role
     *
     * Used to restrict certain tabs (like 'Perusahaan Baru') to agency users only.
     * Customer roles (customer_admin, customer_branch_admin, customer_employee)
     * should not see agency-specific tabs.
     *
     * @return bool True if user has agency role, false otherwise
     */
    private function user_has_agency_role(): bool {
        $user = wp_get_current_user();

        // List of all agency-related roles
        $agency_roles = [
            'agency_employee'
        ];

        // Check if user has any agency role
        return !empty(array_intersect($user->roles, $agency_roles));
    }
}
