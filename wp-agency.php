<?php
/**
 * Plugin Name: WP Agency
 * Plugin URI:
 * Description: Plugin untuk mengelola data Agency dan Divisionnya
 * Version: 1.0.7
 * Author: arisciwek
 * Author URI:
 * License: GPL v2 or later
 *
 * @package     WP_Agency
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/wp-agency.php
 *
 * Changelog:
 * 1.0.7 - 2025-01-23
 * - Task-2070: Employee demo generator runtime flow migration
 * - Task-2069: Division demo generator runtime flow with orphan cleanup
 * - Added comprehensive hooks documentation (17 hooks: 9 actions + 8 filters)
 * - Migrated demo generators to production validation patterns
 * - Enhanced cache clearing for WordPress user operations
 * - Fixed duplicate employee usernames
 * - Updated README.md with hooks system documentation
 *
 * 1.0.6 - 2025-01-22
 * - Task-2066: Added AutoEntityCreator hook system
 * - Auto-create division pusat when agency is created
 * - Auto-create employee when division is created
 * - Lifecycle hooks for agency, division, employee (created, before_delete, deleted)
 *
 * 1.0.0 - 2024-01-07
 * - Initial release
 */

defined('ABSPATH') || exit;

// Define plugin constants first, before anything else
define('WP_AGENCY_VERSION', '1.0.7');
define('WP_AGENCY_FILE', __FILE__);
define('WP_AGENCY_PATH', plugin_dir_path(__FILE__));
define('WP_AGENCY_URL', plugin_dir_url(__FILE__));
define('WP_AGENCY_DEVELOPMENT', false);

// Check wp-app-core dependency
// Note: We only check if plugin is active, not class existence
// Class existence will be verified when autoloader tries to load them
function wp_agency_check_dependencies() {
    // Check if wp-app-core plugin is active
    if (!is_plugin_active('wp-app-core/wp-app-core.php')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo '<strong>WP Agency:</strong> Plugin memerlukan WP App Core untuk berfungsi. ';
            echo 'Silakan aktifkan plugin WP App Core terlebih dahulu.';
            echo '</p></div>';
        });
        return false;
    }

    return true;
}

// Early dependency check (before autoloader)
if (!wp_agency_check_dependencies()) {
    return; // Stop plugin initialization
}

add_filter('wp_customer_access_type', 'add_agency_access_type', 10, 2);
function add_agency_access_type($access_type, $relation) {
    // Cek apakah user adalah vendor untuk customer ini
    if ($relation['is_agency'] ?? false) {
        return 'agency';
    }
    return $access_type;
}

/**
 * Main plugin class
 */
class WPAgency {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    private $loader;
    private $plugin_name;
    private $version;
    private $agency_controller;
    private $dashboard_controller;
    private $menu_manager;
    private $audit_log_controller;

    /**
     * Get single instance of WPAgency
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_name = 'wp-agency';
        $this->version = WP_AGENCY_VERSION;

        // Register autoloader first
        require_once WP_AGENCY_PATH . 'includes/class-autoloader.php';
        $autoloader = new WPAgencyAutoloader('WPAgency\\', WP_AGENCY_PATH);
        $autoloader->register();
        
        load_textdomain('wp-agency', WP_AGENCY_PATH . 'languages/wp-agency-id_ID.mo');

        $this->includeDependencies();
        $this->initHooks();
    }

    /**
     * Include required dependencies
     */
    private function includeDependencies() {
        require_once WP_AGENCY_PATH . 'includes/class-loader.php';
        require_once WP_AGENCY_PATH . 'includes/class-role-manager.php';
        require_once WP_AGENCY_PATH . 'includes/class-activator.php';
        require_once WP_AGENCY_PATH . 'includes/class-deactivator.php';
        require_once WP_AGENCY_PATH . 'includes/class-init-hooks.php';

        // Load WP Customer integration (TODO-2065)
        require_once WP_AGENCY_PATH . 'includes/class-wp-customer-integration.php';

        $this->loader = new WP_Agency_Loader();

        // Initialize Settings Controller
        new \WPAgency\Controllers\SettingsController();
    }

    /**
     * Initialize hooks and controllers
     */
    private function initHooks() {
        // NOTE: Activation/deactivation hooks now registered at file level (before plugins_loaded)
        // This ensures tables are created during plugin activation

        // Enable wp-datatable dual panel assets for agency list page
        // MUST be registered early (before admin_enqueue_scripts)
        add_filter('wpdt_use_dual_panel', function($use_dual_panel) {
            if (isset($_GET['page']) && $_GET['page'] === 'wp-agency-disnaker') {
                error_log('[WPAgency] Filter wpdt_use_dual_panel called, returning TRUE for wp-agency-disnaker');
                return true;
            }
            error_log('[WPAgency] Filter wpdt_use_dual_panel called, page=' . ($_GET['page'] ?? 'NOT SET') . ', returning: ' . ($use_dual_panel ? 'TRUE' : 'FALSE'));
            return $use_dual_panel;
        }, 5);

        // Initialize AssetController (wp-agency-specific assets only)
        // AssetController is a Singleton - registers hooks in constructor
        \WPAgency\Controllers\Assets\AssetController::get_instance()->init();

        // Initialize menu
        $this->menu_manager = new \WPAgency\Controllers\MenuManager($this->plugin_name, $this->version);
        $this->loader->add_action('init', $this->menu_manager, 'init');

        // Initialize controllers
        $this->initControllers();

        // Initialize role-based filtering (wpdt_ pattern)
        $this->initFilters();

        // Initialize other hooks
        $init_hooks = new WP_Agency_Init_Hooks();
        $init_hooks->init();

        // Task-2066: Register AutoEntityCreator hooks for automatic entity creation
        // Direct registration to support all contexts (web, CLI, cron)
        $auto_entity_creator = new \WPAgency\Handlers\AutoEntityCreator();
        add_action('wp_agency_agency_created', [$auto_entity_creator, 'handleAgencyCreated'], 10, 2);
        add_action('wp_agency_division_created', [$auto_entity_creator, 'handleDivisionCreated'], 10, 2);
        add_action('wp_agency_division_deleted', [$auto_entity_creator, 'handleDivisionDeleted'], 10, 3);

        // Integration with wp-customer for company agency assignment
        // Delay initialization until after wp-customer is loaded
        add_action('plugins_loaded', function() {
            if (defined('WP_CUSTOMER_VERSION')) {
                new \WPAgency\Integrations\CompanyAgencyIntegration();
            }
        }, 20);

        /**
         * Hook: wp_agency_employee_created
         *
         * Fires after an employee is successfully created.
         * Triggered by AgencyEmployeeModel->create() method.
         *
         * @since 2.2.0 (Task-2070)
         * @param int   $employee_id   The newly created employee ID
         * @param array $employee_data The employee data used for creation
         *
         * Use cases:
         * - Send welcome email notification
         * - Create employee audit log
         * - Trigger onboarding workflow
         * - Update statistics cache
         */
        // Hook registered but no default handler yet (future: notification, email, etc)

        // NEW: Simplified WP App Core integration (v2.0)
        // wp-app-core handles ALL WordPress queries (user, role, permission)
        // wp-agency ONLY provides entity data from its tables
        add_filter('wp_app_core_user_entity_data', [$this, 'provide_entity_data'], 10, 3);

        // Custom role names for wp-agency roles
        add_filter('wp_app_core_role_display_name', [$this, 'get_role_display_name'], 10, 2);

        // Fix wp-customer DataTableAccessFilter config for customer_branches
        // Override hardcoded alias 'cb' to 'b' (NewCompanyDataTableModel uses 'b')
        add_filter('wp_customer_datatable_access_configs', function($configs) {
            if (isset($configs['customer_branches'])) {
                $configs['customer_branches']['table_alias'] = 'b';
            }
            return $configs;
        }, 1, 1); // Priority 1 to run early, before DataTableAccessFilter init

        // Bypass wp-customer DataTableAccessFilter for agency users
        // Agency users should see branches in their province, not filtered by customer_id
        add_filter('wp_customer_should_filter_datatable', function($should_filter, $user_id, $entity_type, $config) {
            // Only for customer_branches entity
            if ($entity_type !== 'customer_branches') {
                return $should_filter;
            }

            // Check if user is agency employee
            global $wpdb;
            $is_agency_employee = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees WHERE user_id = %d",
                $user_id
            ));

            // Also check if user is agency owner
            $is_agency_owner = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_agencies WHERE user_id = %d",
                $user_id
            ));

            // Agency users should NOT be filtered by customer access control
            if ($is_agency_employee || $is_agency_owner) {
                return false; // Don't filter agency users
            }

            return $should_filter;
        }, 10, 4);

        // DEBUG: Log DataTable queries (development only)
        if (WP_AGENCY_DEVELOPMENT || (defined('WP_DEBUG') && WP_DEBUG)) {
            add_filter('query', [$this, 'log_datatable_queries']);
        }
    }

    /**
     * Initialize plugin controllers
     */
    private function initControllers() {
        // NOTE: This is called from initHooks() which is called during __construct()
        // __construct() is called during plugins_loaded priority 30
        // So we can safely instantiate controllers here directly
        error_log("[wp-agency] initControllers() called, initializing all controllers...");

        // Check if wp-app-core AbstractCrudModel is available
        if (!class_exists("WPAppCore\Models\Abstract\AbstractCrudModel")) {
            error_log("ERROR: wp-app-core AbstractCrudModel not loaded, controllers not initialized");
            return;
        }
        error_log("[wp-agency] AbstractCrudModel found, proceeding with controller initialization...");

        // Agency Controller (needs AbstractCrudModel)
        $this->agency_controller = new \WPAgency\Controllers\Agency\AgencyController();

        // Set AgencyController to MenuManager (for menu callback)
        if ($this->menu_manager) {
            $this->menu_manager->setAgencyController($this->agency_controller);
        }

        // Agency Dashboard Controller (needs wp-datatable)
        error_log("[wp-agency] Checking for wp-datatable DashboardTemplate...");
        if (class_exists("WPDataTable\Templates\DualPanel\DashboardTemplate")) {
            error_log("[wp-agency] DashboardTemplate found, initializing AgencyDashboardController...");
            // Force autoloader to check for the class
            if (!class_exists("WPAgency\Controllers\Agency\AgencyDashboardController")) {
                error_log("ERROR: AgencyDashboardController class not found by autoloader");
                return;
            }

            try {
                $this->dashboard_controller = new \WPAgency\Controllers\Agency\AgencyDashboardController();
                error_log("[wp-agency] AgencyDashboardController initialized successfully");
            } catch (\Exception $e) {
                error_log("ERROR initializing AgencyDashboardController: " . $e->getMessage());
            } catch (\Error $e) {
                error_log("FATAL ERROR initializing AgencyDashboardController: " . $e->getMessage());
            }
        } else {
            error_log("ERROR: wp-datatable not loaded, AgencyDashboardController not initialized");
        }

        // Employee Controller
        new \WPAgency\Controllers\Employee\AgencyEmployeeController();

        // Division Controller
        new \WPAgency\Controllers\Division\DivisionController();

        // Jurisdiction Controller
        new \WPAgency\Controllers\Division\JurisdictionController();

        // New Company Controller
        new \WPAgency\Controllers\Company\NewCompanyController();

        // Audit Log Controller
        $this->audit_log_controller = new \WPAgency\Controllers\AuditLog\AuditLogController();

        // Register Audit Log AJAX handlers
        add_action('wp_ajax_get_agency_audit_logs', [$this->audit_log_controller, 'handleGetAuditLogs']);
        add_action('wp_ajax_view_agency_audit_detail', [$this->audit_log_controller, 'handleViewAuditDetail']);
    }

    /**
     * Initialize role-based filtering classes
     *
     * NEW: Role-based filter system (simplified)
     * Single unified filter for all agency entities based on user role
     * Handles: agency, division, employee, company
     * Supports: agency_admin_dinas, agency_admin_unit roles
     *
     * @since 1.0.0
     */
    private function initFilters() {
        // 1. Generic DataTable Access Filter (framework) - REMOVED
        // Replaced by RoleBasedFilter (role-based hooks instead of entity-based)
        // new \WPAgency\Controllers\Integration\DataTableAccessFilter();

        // 2. Unified role-based filter (replaces DataTableAccessFilter + 3 Integration filters)
        new \WPAgency\Filters\RoleBasedFilter();

        // 5. wp-customer integration: Bypass wp-agency filtering for customer employees
        // Customer employees should be filtered by wp-customer's AgencyAccessFilter instead
        add_filter('wpdt_agency_should_bypass_filter', function($should_bypass, $user_id, $user) {
            // Check if user is customer employee (wp-customer table)
            global $wpdb;
            $customer_employee_table = $wpdb->prefix . 'app_customer_employees';

            // Check if table exists (wp-customer plugin active)
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$customer_employee_table}'");

            if (!$table_exists) {
                return $should_bypass; // wp-customer not active
            }

            // Check if user is customer employee
            $is_customer_employee = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$customer_employee_table} WHERE user_id = %d",
                $user_id
            ));

            if ($is_customer_employee) {
                // Customer employee - let wp-customer's AgencyAccessFilter handle filtering
                return true;
            }

            return $should_bypass;
        }, 10, 3);
    }

    /**
     * Provide entity data for wp-app-core admin bar (v2.0 simplified integration)
     *
     * wp-app-core queries WordPress user, roles, and permissions
     * wp-agency only provides agency/division entity data
     *
     * @param array|null $entity_data Existing entity data (from other plugins)
     * @param int $user_id WordPress user ID
     * @param WP_User $user WordPress user object
     * @return array|null Entity data or null if not found
     */
    public function provide_entity_data($entity_data, $user_id, $user) {
        // Skip if another plugin already provided data
        if ($entity_data) {
            return $entity_data;
        }

        // Query agency entity data from Model
        $employee_model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
        $user_info = $employee_model->getUserInfo($user_id);

        if (!$user_info) {
            return null;
        }

        // Return ONLY entity data (agency/division info)
        // wp-app-core will merge this with WordPress user/role/permission data
        return [
            'entity_name' => $user_info['entity_name'] ?? '',
            'entity_code' => $user_info['entity_code'] ?? '',
            'division_name' => $user_info['division_name'] ?? '',
            //'division_type' => $user_info['division_type'] ?? '',
            //'division_code' => $user_info['division_code'] ?? '',
            //'jurisdiction_codes' => $user_info['jurisdiction_codes'] ?? '',
            'position' => $user_info['position'] ?? '',
            'icon' => '🏛️',
            'relation_type' => $user_info['relation_type'] ?? 'agency'
        ];
    }

    /**
     * Get custom role display name for wp-agency roles
     *
     * @param string $name Current display name
     * @param string $slug Role slug
     * @return string Role display name
     */
    public function get_role_display_name($name, $slug) {
        return WP_Agency_Role_Manager::getRoleName($slug) ?? $name;
    }

    /**
     * Log DataTable queries for debugging
     *
     * @param string $query SQL query
     * @return string Unmodified query
     */
    public function log_datatable_queries($query) {
        // Only log SELECT queries on agency tables during AJAX requests
        if (
            wp_doing_ajax() &&
            strpos($query, 'app_agencies') !== false &&
            strpos($query, 'SELECT') === 0
        ) {
            $user = wp_get_current_user();
            $user_info = sprintf(
                'User: %s (ID: %d, Roles: %s)',
                $user->user_login,
                $user->ID,
                implode(', ', $user->roles)
            );

            error_log('[WP_Agency] ========================================');
            error_log('[WP_Agency] ' . $user_info);
            error_log('[WP_Agency] DATATABLE QUERY:');
            error_log('[WP_Agency] ' . $query);
            error_log('[WP_Agency] ========================================');
        }

        return $query;
    }

    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
}

/**
 * Returns the main instance of WPAgency
 */
function wp_agency() {
    return WPAgency::getInstance();
}

/**
 * Add agency data to WP Admin Bar
 *
 * Hook into: wp_admin_bar_user_data
 *
 * Displays:
 * - Agency name (Disnaker)
 * - Division name (Unit Kerja)
 * - Employee position/name
 *
 * @param array $data Current user data
 * @param int $user_id WordPress user ID
 * @param WP_User $user WordPress user object
 * @return array Enhanced user data
 */
function wp_agency_add_admin_bar_user_data($data, $user_id, $user) {
    global $wpdb;

    // Check if user is agency employee
    $employee = $wpdb->get_row($wpdb->prepare(
        "SELECT ae.name as employee_name,
                ae.position,
                a.name as agency_name,
                d.name as division_name
         FROM {$wpdb->prefix}app_agency_employees ae
         LEFT JOIN {$wpdb->prefix}app_agencies a ON ae.agency_id = a.id
         LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON ae.division_id = d.id
         WHERE ae.user_id = %d
         LIMIT 1",
        $user_id
    ));

    // If not employee, check if user is agency owner/admin
    if (!$employee) {
        $agency = $wpdb->get_row($wpdb->prepare(
            "SELECT name as agency_name
             FROM {$wpdb->prefix}app_agencies
             WHERE user_id = %d
             LIMIT 1",
            $user_id
        ));

        if (!$agency) {
            return $data; // Not an agency user
        }

        // Agency owner/admin - use custom_fields for custom labels
        return array_merge($data, [
            'entity_icon' => '🏛️',
            'custom_fields' => [
                'Kantor Dinas' => $agency->agency_name,
                'Jabatan' => 'Admin Dinas',
            ]
        ]);
    }

    // Prepare enhanced data for agency employee
    $custom_fields = [
        'Kantor Dinas' => $employee->agency_name,
    ];

    // Add Unit Kerja (division) if available
    if (!empty($employee->division_name)) {
        $custom_fields['Unit Kerja'] = $employee->division_name;
    }

    // Add position/jabatan
    if (!empty($employee->position)) {
        $custom_fields['Jabatan'] = $employee->position;
    } elseif (!empty($employee->employee_name)) {
        $custom_fields['Jabatan'] = $employee->employee_name;
    }

    return array_merge($data, [
        'entity_icon' => '🏛️',
        'custom_fields' => $custom_fields
    ]);
}

// WP Admin Bar Integration - Add agency info to admin bar
add_filter('wp_admin_bar_user_data', 'wp_agency_add_admin_bar_user_data', 10, 3);

// ============================================================================
// ACTIVATION/DEACTIVATION HOOKS
// Must be registered at file level, NOT inside plugins_loaded
// ============================================================================

/**
 * Activation hook
 * Runs when plugin is activated - creates tables, roles, etc.
 */
register_activation_hook(__FILE__, function() {
    // Load autoloader first
    require_once WP_AGENCY_PATH . 'includes/class-autoloader.php';
    $autoloader = new WPAgencyAutoloader('WPAgency\\', WP_AGENCY_PATH);
    $autoloader->register();

    // Now load and run activator
    require_once WP_AGENCY_PATH . 'includes/class-activator.php';
    WP_Agency_Activator::activate();
});

/**
 * Deactivation hook
 * Runs when plugin is deactivated - cleanup if needed
 */
register_deactivation_hook(__FILE__, function() {
    require_once WP_AGENCY_PATH . 'includes/class-deactivator.php';
    WP_Agency_Deactivator::deactivate();
});

// ============================================================================
// PLUGIN INITIALIZATION
// Uses plugins_loaded to ensure dependencies are available
// ============================================================================

// Initialize the plugin
// Initialize after all plugins loaded to ensure dependencies are available
add_action('plugins_loaded', function() {
    // Check required dependencies
    $dependencies = [
        'WPAppCore\Models\Abstract\AbstractCrudModel' => 'wp-app-core',
        'WPDataTable\Core\AbstractDataTable' => 'wp-datatable',
    ];

    $missing = [];
    foreach ($dependencies as $class => $plugin) {
        if (!class_exists($class)) {
            $missing[] = $plugin;
        }
    }

    if (!empty($missing)) {
        add_action('admin_notices', function() use ($missing) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>WP Agency:</strong> Requires the following plugins: ' . implode(', ', $missing);
            echo '</p></div>';
        });
        return;
    }

    wp_agency()->run();
}, 30); // Priority 30: after wp-datatable (10) and wp-app-core (20)
