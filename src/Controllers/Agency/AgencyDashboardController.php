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

        // Tab content rendering hooks
        add_action('wpapp_tab_content_agency_info', [$this, 'render_info_tab'], 10, 1);
        add_action('wpapp_tab_content_agency_divisions', [$this, 'render_divisions_tab'], 10, 1);
        add_action('wpapp_tab_content_agency_employees', [$this, 'render_employees_tab'], 10, 1);
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

        ?>
        <h1 class="agency-page-title">Daftar Disnaker</h1>
        <p class="agency-page-subtitle">Kelola data dinas tenaga kerja</p>
        <?php
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

        ?>
        <div class="agency-header-buttons">
            <?php if (current_user_can('view_agency_list')): ?>
                <button type="button" class="button agency-print-btn" id="agency-print-btn">
                    <span class="dashicons dashicons-printer"></span>
                    Print
                </button>

                <button type="button" class="button agency-export-btn" id="agency-export-btn">
                    <span class="dashicons dashicons-download"></span>
                    Export
                </button>
            <?php endif; ?>

            <?php if (current_user_can('add_agency')): ?>
                <a href="#" class="button button-primary agency-add-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Tambah Disnaker
                </a>
            <?php endif; ?>
        </div>
        <?php
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

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
        $inactive = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive'");

        ?>
        <div class="agency-statistics-cards" id="agency-statistics">
            <!-- Total Card -->
            <div class="agency-stat-card agency-theme-blue" data-card-id="total-agencies">
                <div class="agency-stat-icon">
                    <span class="dashicons dashicons-building"></span>
                </div>
                <div class="agency-stat-content">
                    <div class="agency-stat-number"><?php echo esc_html($total ?: '0'); ?></div>
                    <div class="agency-stat-label">Total Disnaker</div>
                </div>
            </div>

            <!-- Active Card -->
            <div class="agency-stat-card agency-theme-green" data-card-id="active-agencies">
                <div class="agency-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="agency-stat-content">
                    <div class="agency-stat-number"><?php echo esc_html($active ?: '0'); ?></div>
                    <div class="agency-stat-label">Active</div>
                </div>
            </div>

            <!-- Inactive Card -->
            <div class="agency-stat-card agency-theme-orange" data-card-id="inactive-agencies">
                <div class="agency-stat-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="agency-stat-content">
                    <div class="agency-stat-number"><?php echo esc_html($inactive ?: '0'); ?></div>
                    <div class="agency-stat-label">Inactive</div>
                </div>
            </div>
        </div>
        <?php
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
                'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/info.php',
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
            // Generate divisions DataTable HTML
            ob_start();
            ?>
            <table id="divisions-datatable" class="wpapp-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Code', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Name', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Type', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Status', 'wp-agency'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTable will populate via AJAX -->
                </tbody>
            </table>

            <script>
            jQuery(document).ready(function($) {
                if (!$.fn.DataTable.isDataTable('#divisions-datatable')) {
                    $('#divisions-datatable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: wpAppConfig.ajaxUrl,
                            type: 'POST',
                            data: function(d) {
                                d.action = 'get_divisions_datatable';
                                d.agency_id = <?php echo esc_js($agency_id); ?>;
                                d.nonce = wpAppConfig.nonce;
                                return d;
                            }
                        },
                        columns: [
                            { data: 'code' },
                            { data: 'name' },
                            { data: 'type' },
                            { data: 'status' }
                        ]
                    });
                }
            });
            </script>
            <?php
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
            // Generate employees DataTable HTML
            ob_start();
            ?>
            <table id="employees-datatable" class="wpapp-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Position', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Email', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Phone', 'wp-agency'); ?></th>
                        <th><?php esc_html_e('Status', 'wp-agency'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTable will populate via AJAX -->
                </tbody>
            </table>

            <script>
            jQuery(document).ready(function($) {
                if (!$.fn.DataTable.isDataTable('#employees-datatable')) {
                    $('#employees-datatable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: wpAppConfig.ajaxUrl,
                            type: 'POST',
                            data: function(d) {
                                d.action = 'get_employees_datatable';
                                d.agency_id = <?php echo esc_js($agency_id); ?>;
                                d.nonce = wpAppConfig.nonce;
                                return d;
                            }
                        },
                        columns: [
                            { data: 'name' },
                            { data: 'position' },
                            { data: 'email' },
                            { data: 'phone' },
                            { data: 'status' }
                        ]
                    });
                }
            });
            </script>
            <?php
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
     * Render info tab content
     *
     * Hooked to: wpapp_tab_content_agency_info
     *
     * Displays agency detail information
     *
     * @param array $data Agency data from panel manager
     */
    public function render_info_tab($data): void {
        error_log('=== render_info_tab called ===');
        error_log('Data received: ' . print_r($data, true));

        // Extract agency data (already an object from database)
        $agency = isset($data['agency']) ? $data['agency'] : null;

        if (!$agency) {
            echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
            return;
        }

        // Include info tab view
        $info_tab_file = WP_AGENCY_PATH . 'src/Views/agency/tabs/info.php';

        if (file_exists($info_tab_file)) {
            include $info_tab_file;
        } else {
            echo '<p>' . __('View file not found', 'wp-agency') . '</p>';
        }
    }

    /**
     * Render divisions tab content
     *
     * Hooked to: wpapp_tab_content_agency_divisions
     *
     * Displays lazy-loaded divisions tab
     *
     * @param array $data Agency data from panel manager
     */
    public function render_divisions_tab($data): void {
        error_log('=== render_divisions_tab called ===');
        error_log('Data received: ' . print_r($data, true));

        // Extract agency ID from object
        $agency_id = isset($data['agency']->id) ? (int) $data['agency']->id : 0;

        if (!$agency_id) {
            echo '<p>' . __('Invalid agency ID', 'wp-agency') . '</p>';
            return;
        }

        // Include divisions tab view
        $divisions_tab_file = WP_AGENCY_PATH . 'src/Views/agency/tabs/divisions.php';

        if (file_exists($divisions_tab_file)) {
            include $divisions_tab_file;
        } else {
            echo '<p>' . __('View file not found', 'wp-agency') . '</p>';
        }
    }

    /**
     * Render employees tab content
     *
     * Hooked to: wpapp_tab_content_agency_employees
     *
     * Displays lazy-loaded employees tab
     *
     * @param array $data Agency data from panel manager
     */
    public function render_employees_tab($data): void {
        error_log('=== render_employees_tab called ===');
        error_log('Data received: ' . print_r($data, true));

        // Extract agency ID from object
        $agency_id = isset($data['agency']->id) ? (int) $data['agency']->id : 0;

        if (!$agency_id) {
            echo '<p>' . __('Invalid agency ID', 'wp-agency') . '</p>';
            return;
        }

        // Include employees tab view
        $employees_tab_file = WP_AGENCY_PATH . 'src/Views/agency/tabs/employees.php';

        if (file_exists($employees_tab_file)) {
            include $employees_tab_file;
        } else {
            echo '<p>' . __('View file not found', 'wp-agency') . '</p>';
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
     * Render all tab contents as HTML
     *
     * Used by handle_get_details to populate panel tabs
     *
     * @param object $agency Agency data object
     * @return array Array of tab_id => html_content
     */
    private function render_tab_contents($agency): array {
        error_log('=== RENDER TAB CONTENTS START ===');
        $tabs = [];

        // Get registered tabs
        $registered_tabs = apply_filters('wpapp_datatable_tabs', [], 'agency');
        error_log('Registered tabs: ' . print_r(array_keys($registered_tabs), true));

        foreach ($registered_tabs as $tab_id => $tab_config) {
            error_log("Processing tab: {$tab_id}");

            ob_start();

            // Set $agency variable for template
            $GLOBALS['agency_temp'] = $agency;

            // Trigger tab content action
            $action_name = "wpapp_tab_content_agency_{$tab_id}";
            error_log("Triggering action: {$action_name}");

            do_action($action_name, ['agency' => $agency]);

            $content = ob_get_clean();
            $content_length = strlen($content);

            error_log("Tab {$tab_id} content length: {$content_length} bytes");
            error_log("Tab {$tab_id} content preview: " . substr($content, 0, 100));

            $tabs[$tab_id] = $content;

            // Clean up
            unset($GLOBALS['agency_temp']);
        }

        error_log('Total tabs rendered: ' . count($tabs));
        error_log('=== RENDER TAB CONTENTS END ===');

        return $tabs;
    }
}
