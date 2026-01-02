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
     * Constructor
     * Register all hooks for dashboard components
     */
    public function __construct() {
        $this->datatable_model = new AgencyDataTableModel();
        $this->model = new AgencyModel();

        // Register hooks for dashboard components
        $this->register_hooks();

        // WORKAROUND: Force load wp-datatable dual-panel JS
        // AssetController enqueue_assets() tidak terpanggil - debug needed
        add_action('admin_enqueue_scripts', [$this, 'force_load_dual_panel_js'], 999);
    }

    /**
     * WORKAROUND: Force load dual-panel JavaScript
     *
     * Temporary fix - AssetController enqueue_assets() not being called
     */
    public function force_load_dual_panel_js() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_wp-agency-disnaker') {
            $plugin_url = plugin_dir_url(WP_DATATABLE_FILE);
            $version = WP_DATATABLE_VERSION;

            // Panel Manager
            wp_enqueue_script(
                'wpdt-panel-manager',
                $plugin_url . 'assets/js/dual-panel/panel-manager.js',
                ['jquery', 'datatables'],
                $version,
                true
            );

            // Tab Manager
            wp_enqueue_script(
                'wpdt-tab-manager',
                $plugin_url . 'assets/js/dual-panel/tab-manager.js',
                ['jquery', 'wpdt-panel-manager'],
                $version,
                true
            );

            // Auto Refresh
            wp_enqueue_script(
                'wpdt-auto-refresh',
                $plugin_url . 'assets/js/dual-panel/auto-refresh.js',
                ['jquery', 'datatables'],
                $version,
                true
            );

            // Localize config
            wp_localize_script('wpdt-panel-manager', 'wpdtConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpdt_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
            ]);

            // CRITICAL FIX: Add inline CSS to ensure tabs hide/show correctly
            wp_add_inline_style('wpdt-dual-panel', '
                .wpdt-tab-content {
                    display: none !important;
                }
                .wpdt-tab-content.active {
                    display: block !important;
                }
            ');
        }
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

        // Enable wp-datatable dual panel assets
        add_filter('wpdt_use_dual_panel', [$this, 'enable_dual_panel']);

        // Statistics hook
        add_filter('wpdt_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Tabs hook
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Tab content injection hooks (per-tab registration for better decoupling)
        // Using Hook-Based Pattern because panel content loaded via AJAX
        add_action('wpdt_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_divisions_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_employees_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_new_companies_tab'], 10, 3);
        add_action('wpdt_tab_view_content', [$this, 'render_history_tab'], 10, 3);

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

        // AJAX handlers - Modal CRUD
        add_action('wp_ajax_get_agency_form', [$this, 'handle_get_agency_form']);
        add_action('wp_ajax_save_agency', [$this, 'handle_save_agency']);
        add_action('wp_ajax_delete_agency', [$this, 'handle_delete_agency']);

        // Auto-wire config injection for modal system
        add_filter('wpdt_localize_data', [$this, 'inject_autowire_config'], 10, 1);

        // Backup: Direct localize via admin_enqueue_scripts (priority 20, after wp-datatable's 10)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_autowire_config'], 20);

        error_log('[wp-agency] Registered wpdt_localize_data filter hook in AgencyDashboardController');
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
        // Extract entity from config
        if (!is_array($config)) {
            return;
        }

        $entity = $config['entity'] ?? '';

        if ($entity !== 'agency') {
            return;
        }

        // NOTE: agency-modal-handler.js already enqueued in class-dependencies.php
        // No need to enqueue again here

        // Enqueue New Company Assignment scripts and styles
        // These are needed for the "Perusahaan Baru" tab
        // Loaded on main page so modal and events are always ready
        wp_enqueue_script(
            'wp-agency-new-company-datatable',
            WP_AGENCY_URL . 'assets/js/company/new-company-datatable.js',
            array('jquery', 'datatables', 'agency-datatable', 'wp-modal'),
            WP_AGENCY_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-agency-new-company-style',
            WP_AGENCY_URL . 'assets/css/company/new-company-style.css',
            array(),
            WP_AGENCY_VERSION
        );

        // Localize script - agency_id will be set when tab is clicked
        // For now, use 0 as placeholder - will be updated via AJAX response
        wp_localize_script('wp-agency-new-company-datatable', 'wpAgencyNewCompany', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'agency_id' => 0, // Placeholder - actual ID from tab data-agency-id
            'i18n' => array(
                'loading' => __('Loading...', 'wp-agency'),
                'error' => __('Error loading data', 'wp-agency'),
                'success' => __('Inspector assigned successfully', 'wp-agency'),
                'confirm' => __('Are you sure?', 'wp-agency')
            )
        ));

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
     * Enable wp-datatable dual panel assets
     *
     * Hooked to: wpdt_use_dual_panel
     *
     * Activates dual panel CSS and JS loading for agency list page.
     * Required for panel opening, tab switching, and AJAX functionality.
     *
     * @param bool $use_dual_panel Current value
     * @return bool True to enable dual panel
     */
    public function enable_dual_panel($use_dual_panel): bool {
        // Enable dual panel on agency list page
        if (isset($_GET['page']) && $_GET['page'] === 'wp-agency-disnaker') {
            return true;
        }

        return $use_dual_panel;
    }

    /**
     * Register tabs via filter hook
     *
     * Hooked to: wpdt_datatable_tabs
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity name
     * @return array Modified tabs array
     */
    public function register_tabs($tabs, $entity) {
        if ($entity !== 'agency') {
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

        // History/Audit Log tab
        $agency_tabs['history'] = [
            'title' => __('History', 'wp-agency'),
            'priority' => 50
        ];

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
        include WP_AGENCY_PATH . 'src/Views/admin/agency/new-companies-tab.php';
    }

    /**
     * Render history tab HTML content
     *
     * Hook handler for wpdt_tab_view_content (history tab).
     * Renders the actual HTML content for the history/audit log tab.
     * Shows complete timeline: agency + divisions + employees.
     *
     * Entity-owned hook implementation pattern:
     * - render_tab_contents() triggers wpdt_tab_view_content hook
     * - This method responds to that hook for 'agency' entity, 'history' tab
     * - Priority 10: Core content rendering
     *
     * Hook Flow:
     * 1. render_tab_contents() → do_action('wpdt_tab_view_content')
     * 2. This method → Includes history-tab.php template
     *
     * @param string $entity Entity type (e.g., 'agency')
     * @param string $tab_id Tab identifier (should be 'history')
     * @param array  $data   Data passed to tab (contains $agency object)
     * @return void
     */
    public function render_history_tab($entity, $tab_id, $data): void {
        // Only respond to agency entity and history tab
        if ($entity !== 'agency' || $tab_id !== 'history') {
            return;
        }

        // Extract $agency from $data for view file
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include audit log template
        $template_path = WP_AGENCY_PATH . 'src/Views/templates/audit-log/history-tab.php';

        include $template_path;
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
        // Verify nonce - use base panel system nonce
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        $agency_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$agency_id) {
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
            $agency = $this->model->find($agency_id);

            if (!$agency) {
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

            wp_send_json_success($response);

        } catch (\Exception $e) {
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
        // Verify nonce for security
        check_ajax_referer('wpdt_nonce', 'nonce');

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
        // Verify nonce for security
        check_ajax_referer('wpdt_nonce', 'nonce');

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
        // Verify nonce for security
        check_ajax_referer('wpdt_nonce', 'nonce');

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
            // Scripts and styles already enqueued in render_datatable()
            // Just return the HTML content

            // Generate new companies DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-new-companies-datatable', compact('agency_id'), 'agency');
            $html = ob_get_clean();

            wp_send_json_success([
                'html' => $html
            ]);

        } catch (\Exception $e) {
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
            'agency_employee',
            'administrator'
        ];

        // Check if user has any agency role
        return !empty(array_intersect($user->roles, $agency_roles));
    }

    // ========================================
    // MODAL CRUD HANDLERS
    // ========================================

    /**
     * Handle get agency form (create/edit)
     */
    public function handle_get_agency_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            echo '<p class="error">' . __('Security check failed', 'wp-agency') . '</p>';
            wp_die();
        }

        // Auto-wire sends 'id', legacy sends 'agency_id'
        $agency_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['agency_id']) ? (int) $_GET['agency_id'] : 0);

        // Determine mode: if ID exists, it's edit mode
        $mode = $agency_id > 0 ? 'edit' : ($_GET['mode'] ?? 'create');

        // Check permissions
        if ($mode === 'edit') {
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_agencies') &&
                !current_user_can('edit_own_agency')) {
                echo '<p class="error">' . __('Permission denied', 'wp-agency') . '</p>';
                wp_die();
            }
        } else {
            if (!current_user_can('manage_options') && !current_user_can('add_agency')) {
                echo '<p class="error">' . __('Permission denied', 'wp-agency') . '</p>';
                wp_die();
            }
        }

        try {
            if ($mode === 'edit' && $agency_id) {
                $agency = $this->model->find($agency_id);

                if (!$agency) {
                    wp_send_json_error(['message' => __('Agency not found', 'wp-agency')]);
                    return;
                }

                // Load edit form and capture output for auto-wire system
                ob_start();
                include WP_AGENCY_PATH . 'src/Views/admin/agency/forms/edit-agency-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            } else {
                // Create mode - check if create form exists
                $create_form_path = WP_AGENCY_PATH . 'src/Views/admin/agency/forms/create-agency-form.php';
                if (!file_exists($create_form_path)) {
                    wp_send_json_error(['message' => __('Create form not found', 'wp-agency')]);
                    return;
                }

                ob_start();
                include $create_form_path;
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle save agency (create/update)
     */
    public function handle_save_agency(): void {
        @ini_set('display_errors', '0');
        ob_start();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Auto-wire sends 'id', legacy sends 'agency_id'
        $agency_id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0);

        // Determine mode: if ID exists, it's edit mode
        $mode = $agency_id > 0 ? 'edit' : ($_POST['mode'] ?? 'create');

        // Check permissions
        if ($mode === 'edit') {
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_agencies') &&
                !current_user_can('edit_own_agency')) {
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
                return;
            }
        } else {
            if (!current_user_can('manage_options') && !current_user_can('add_agency')) {
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
                return;
            }
        }

        // Prepare data (only fields that exist in database)
        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'status' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'inactive',
            'province_id' => !empty($_POST['province_id']) ? (int) $_POST['province_id'] : null,
            'regency_id' => !empty($_POST['regency_id']) ? (int) $_POST['regency_id'] : null,
        ];

        try {
            if ($mode === 'edit' && $agency_id) {
                // Update existing
                $result = $this->model->update($agency_id, $data);

                if ($result) {
                    $agency = $this->model->find($agency_id);

                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Agency updated successfully', 'wp-agency'),
                        'agency' => $agency
                    ]);
                } else {
                    ob_end_clean();
                    wp_send_json_error(['message' => __('Failed to update agency', 'wp-agency')]);
                }
            } else {
                // Create new
                $result = $this->model->create($data);

                if ($result) {
                    $agency = $this->model->find($result);

                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Agency created successfully', 'wp-agency'),
                        'agency' => $agency
                    ]);
                } else {
                    ob_end_clean();
                    wp_send_json_error(['message' => __('Failed to create agency', 'wp-agency')]);
                }
            }
        } catch (\Exception $e) {
            ob_end_clean();
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delete agency
     */
    public function handle_delete_agency(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
        }

        if (!current_user_can('manage_options') && !current_user_can('delete_agency')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
        }

        $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;

        if (!$agency_id) {
            wp_send_json_error(['message' => __('Invalid agency ID', 'wp-agency')]);
        }

        try {
            $this->model->delete($agency_id);
            wp_send_json_success(['message' => __('Agency deleted successfully', 'wp-agency')]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Inject auto-wire config for modal system
     *
     * Provides configuration for agency, division, and employee CRUD operations
     * via auto-wire modal system from wp-datatable.
     *
     * @param array $data Existing localize data from wp-datatable
     * @return array Modified data with entity configs
     */
    public function inject_autowire_config($data) {
        error_log('[wp-agency] ========================================');
        error_log('[wp-agency] inject_autowire_config() CALLED');
        error_log('[wp-agency] Current page: ' . ($_GET['page'] ?? 'NOT SET'));
        error_log('[wp-agency] Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        error_log('[wp-agency] Data keys before: ' . print_r(array_keys($data), true));

        // Only inject on wp-agency-disnaker page
        if (!isset($_GET['page']) || $_GET['page'] !== 'wp-agency-disnaker') {
            error_log('[wp-agency] Page check FAILED - returning unchanged');
            error_log('[wp-agency] ========================================');
            return $data;
        }

        error_log('[wp-agency] Page check PASSED - injecting config');

        // Agency entity config
        $data['agency'] = [
            'action_buttons' => [
                'edit' => [
                    'enabled' => true,
                    'ajax_action' => 'get_agency_form',
                    'submit_action' => 'save_agency',
                    'modal_title' => __('Edit Agency', 'wp-agency'),
                    'success_message' => __('Agency updated successfully!', 'wp-agency'),
                    'modal_size' => 'large',
                ],
                'delete' => [
                    'enabled' => true,
                    'ajax_action' => 'delete_agency',
                    'confirm_title' => __('Delete Agency', 'wp-agency'),
                    'confirm_message' => __('Are you sure you want to delete this agency? This action cannot be undone.', 'wp-agency'),
                    'success_message' => __('Agency deleted successfully!', 'wp-agency'),
                ],
            ],
        ];

        // Division entity config
        $data['division'] = [
            'action_buttons' => [
                'edit' => [
                    'enabled' => true,
                    'ajax_action' => 'get_division_form',
                    'submit_action' => 'save_division',
                    'modal_title' => __('Edit Division', 'wp-agency'),
                    'success_message' => __('Division updated successfully!', 'wp-agency'),
                    'modal_size' => 'large',
                    'datatable_id' => '#division-table',  // Custom table ID
                ],
                'delete' => [
                    'enabled' => true,
                    'ajax_action' => 'delete_division',
                    'confirm_title' => __('Delete Division', 'wp-agency'),
                    'confirm_message' => __('Are you sure you want to delete this division?', 'wp-agency'),
                    'success_message' => __('Division deleted successfully!', 'wp-agency'),
                    'datatable_id' => '#division-table',  // Custom table ID
                ],
            ],
        ];

        // Employee entity config
        $data['employee'] = [
            'action_buttons' => [
                'edit' => [
                    'enabled' => true,
                    'ajax_action' => 'get_agency_employee_form',
                    'submit_action' => 'save_agency_employee',
                    'modal_title' => __('Edit Employee', 'wp-agency'),
                    'success_message' => __('Employee updated successfully!', 'wp-agency'),
                    'modal_size' => 'large',
                    'datatable_id' => '#employee-table',  // Custom table ID
                ],
                'delete' => [
                    'enabled' => true,
                    'ajax_action' => 'delete_agency_employee',
                    'confirm_title' => __('Delete Employee', 'wp-agency'),
                    'confirm_message' => __('Are you sure you want to delete this employee?', 'wp-agency'),
                    'success_message' => __('Employee deleted successfully!', 'wp-agency'),
                    'datatable_id' => '#employee-table',  // Custom table ID
                ],
            ],
        ];

        // New Company entity config (for assign inspector)
        $data['new-company'] = [
            'action_buttons' => [
                'edit' => [
                    'enabled' => true,
                    'ajax_action' => 'get_assignment_form',
                    'submit_action' => 'assign_inspector',
                    'modal_title' => __('Assign to Agency', 'wp-agency'),
                    'success_message' => __('Agency, division, dan inspector berhasil ditugaskan!', 'wp-agency'),
                    'modal_size' => 'medium',
                    'datatable_id' => '#new-companies-datatable',  // Custom table ID
                ],
            ],
        ];

        error_log('[wp-agency] Data after inject: ' . print_r(array_keys($data), true));
        error_log('[wp-agency] Agency config injected successfully');

        return $data;
    }

    /**
     * Enqueue auto-wire config directly via wp_localize_script
     * Fallback method if wpdt_localize_data filter doesn't work
     */
    public function enqueue_autowire_config() {
        // Only on wp-agency-disnaker page
        if (!isset($_GET['page']) || $_GET['page'] !== 'wp-agency-disnaker') {
            return;
        }

        error_log('[wp-agency] enqueue_autowire_config() called - injecting directly');

        // Build config
        $config = [
            'agency' => [
                'action_buttons' => [
                    'edit' => [
                        'enabled' => true,
                        'ajax_action' => 'get_agency_form',
                        'submit_action' => 'save_agency',
                        'modal_title' => __('Edit Agency', 'wp-agency'),
                        'success_message' => __('Agency updated successfully!', 'wp-agency'),
                        'modal_size' => 'large',
                    ],
                    'delete' => [
                        'enabled' => true,
                        'ajax_action' => 'delete_agency',
                        'confirm_title' => __('Delete Agency', 'wp-agency'),
                        'confirm_message' => __('Are you sure you want to delete this agency? This action cannot be undone.', 'wp-agency'),
                        'success_message' => __('Agency deleted successfully!', 'wp-agency'),
                    ],
                ],
            ],
            'division' => [
                'action_buttons' => [
                    'edit' => [
                        'enabled' => true,
                        'ajax_action' => 'get_division_form',
                        'submit_action' => 'save_division',
                        'modal_title' => __('Edit Division', 'wp-agency'),
                        'success_message' => __('Division updated successfully!', 'wp-agency'),
                        'modal_size' => 'large',
                        'datatable_id' => '#division-table',  // Custom table ID
                    ],
                    'delete' => [
                        'enabled' => true,
                        'ajax_action' => 'delete_division',
                        'confirm_title' => __('Delete Division', 'wp-agency'),
                        'confirm_message' => __('Are you sure you want to delete this division?', 'wp-agency'),
                        'success_message' => __('Division deleted successfully!', 'wp-agency'),
                        'datatable_id' => '#division-table',  // Custom table ID
                    ],
                ],
            ],
            'employee' => [
                'action_buttons' => [
                    'edit' => [
                        'enabled' => true,
                        'ajax_action' => 'get_agency_employee_form',
                        'submit_action' => 'save_agency_employee',
                        'modal_title' => __('Edit Employee', 'wp-agency'),
                        'success_message' => __('Employee updated successfully!', 'wp-agency'),
                        'modal_size' => 'large',
                        'datatable_id' => '#employee-table',  // Custom table ID
                    ],
                    'delete' => [
                        'enabled' => true,
                        'ajax_action' => 'delete_agency_employee',
                        'confirm_title' => __('Delete Employee', 'wp-agency'),
                        'confirm_message' => __('Are you sure you want to delete this employee?', 'wp-agency'),
                        'success_message' => __('Employee deleted successfully!', 'wp-agency'),
                        'datatable_id' => '#employee-table',  // Custom table ID
                    ],
                ],
            ],
            'new-company' => [
                'action_buttons' => [
                    'edit' => [
                        'enabled' => true,
                        'ajax_action' => 'get_assignment_form',
                        'submit_action' => 'assign_inspector',
                        'modal_title' => __('Assign to Agency', 'wp-agency'),
                        'success_message' => __('Agency, division, dan inspector berhasil ditugaskan!', 'wp-agency'),
                        'modal_size' => 'medium',
                        'datatable_id' => '#new-companies-datatable',  // Custom table ID
                    ],
                ],
            ],
        ];

        // Inject into wpdtConfig
        wp_localize_script('wpdt-panel-manager', 'wpAgencyAutoWireConfig', $config);

        error_log('[wp-agency] Config injected as wpAgencyAutoWireConfig');

        // Also try to merge into wpdtConfig via inline script
        $inline_script = '
        (function($) {
            $(document).ready(function() {
                if (typeof wpdtConfig !== "undefined" && typeof wpAgencyAutoWireConfig !== "undefined") {
                    console.log("[wp-agency] Merging wpAgencyAutoWireConfig into wpdtConfig");
                    $.extend(true, wpdtConfig, wpAgencyAutoWireConfig);
                    console.log("[wp-agency] Final wpdtConfig:", wpdtConfig);
                }
            });
        })(jQuery);
        ';

        wp_add_inline_script('wpdt-panel-manager', $inline_script);

        error_log('[wp-agency] Inline script added to merge configs');
    }
}
