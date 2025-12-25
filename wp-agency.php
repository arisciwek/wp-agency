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
        require_once WP_AGENCY_PATH . 'includes/class-dependencies.php';
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
        // Register activation/deactivation hooks
        register_activation_hook(WP_AGENCY_FILE, array('WP_Agency_Activator', 'activate'));
        register_deactivation_hook(WP_AGENCY_FILE, array('WP_Agency_Deactivator', 'deactivate'));

        // Initialize dependencies
        $dependencies = new WP_Agency_Dependencies($this->plugin_name, $this->version);

        // Register asset hooks
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $dependencies, 'enqueue_scripts');

        // Initialize menu
        $menu_manager = new \WPAgency\Controllers\MenuManager($this->plugin_name, $this->version);
        $this->loader->add_action('init', $menu_manager, 'init');

        // Initialize controllers
        $this->initControllers();

        // Initialize role-based filtering (wpdt_ pattern)
        $this->initFilters();

        // Initialize other hooks
        $init_hooks = new WP_Agency_Init_Hooks();
        $init_hooks->init();

        // Task-2066: Register AutoEntityCreator hooks for automatic entity creation
        $auto_entity_creator = new \WPAgency\Handlers\AutoEntityCreator();
        add_action('wp_agency_agency_created', [$auto_entity_creator, 'handleAgencyCreated'], 10, 2);
        add_action('wp_agency_division_created', [$auto_entity_creator, 'handleDivisionCreated'], 10, 2);
        add_action('wp_agency_division_deleted', [$auto_entity_creator, 'handleDivisionDeleted'], 10, 3);

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

        // DEBUG: Log DataTable queries (development only)
        if (WP_AGENCY_DEVELOPMENT || (defined('WP_DEBUG') && WP_DEBUG)) {
            add_filter('query', [$this, 'log_datatable_queries']);
        }
    }

    /**
     * Initialize plugin controllers
     */
    private function initControllers() {
        // Agency Controller
        $this->agency_controller = new \WPAgency\Controllers\AgencyController();

        // Agency Dashboard Controller (TODO-2071 - Base Panel System)
        // Delay initialization until after wp-app-core is loaded (plugins_loaded hook)
        $self = $this; // Store reference for closure
        add_action('plugins_loaded', function() use ($self) {
            if (class_exists('WPAppCore\\Models\\DataTable\\DataTableModel')) {
//                 error_log('=== AGENCY DASHBOARD CONTROLLER INIT (plugins_loaded) ===');

                // Force autoloader to check for the class
                if (!class_exists('WPAgency\\Controllers\\Agency\\AgencyDashboardController')) {
                    error_log('ERROR: AgencyDashboardController class not found by autoloader');
                    return;
                }

                try {
                    $self->dashboard_controller = new \WPAgency\Controllers\Agency\AgencyDashboardController();
                } catch (\Exception $e) {
                    error_log('ERROR initializing AgencyDashboardController: ' . $e->getMessage());
                } catch (\Error $e) {
                    error_log('FATAL ERROR initializing AgencyDashboardController: ' . $e->getMessage());
                }
            } else {
                error_log('ERROR: wp-app-core not loaded, AgencyDashboardController not initialized');
            }
        }, 20); // Priority 20 ensures wp-app-core loaded first

        // Employee Controller
        new \WPAgency\Controllers\Employee\AgencyEmployeeController();

        // Division Controller
        new \WPAgency\Controllers\Division\DivisionController();

        // Jurisdiction Controller
        new \WPAgency\Controllers\Division\JurisdictionController();

        // New Company Controller
        new \WPAgency\Controllers\Company\NewCompanyController();

        // Register AJAX handlers
        add_action('wp_ajax_get_agency_stats', [$this->agency_controller, 'getStats']);
        add_action('wp_ajax_handle_agency_datatable', [$this->agency_controller, 'handleDataTableRequest']);
        // Updated for centralized panel handler (TODO-1180)
        add_action('wp_ajax_get_agency', [$this->agency_controller, 'handle_get_agency']);

        // Check setiap request apakah WP Customer sudah available
        add_action('init', function() {
            // Skip jika sudah diinit via custom hook
            static $initialized = false;
            if ($initialized) {
                return;
            }
            
            // Check if WP Customer available
            if (!class_exists('WPCustomer\\Validators\\Company\\CompanyValidator')) {
                return; // WP Customer belum loaded atau tidak ada
            }
            
            // Check if filters class exists
            if (!class_exists('WPAgency\\Filters\\CompanyFilters')) {
                return;
            }
            
            // Initialize
            new \WPAgency\Filters\CompanyFilters();
            $initialized = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP Agency: Company filters initialized via init fallback');
            }
        }, 999); // Priority 999 untuk memastikan plugin lain sudah loaded

    }

    /**
     * Initialize role-based filtering classes
     *
     * Implements wp-customer pattern for DataTable filtering.
     * Uses wpdt_ prefix for wp-datatable framework.
     *
     * Filters initialized:
     * - DataTableAccessFilter: Generic framework for all entities
     * - AgencyRoleFilter: Agency DataTable filtering
     * - DivisionAccessFilter: Division DataTable filtering
     * - EmployeeAccessFilter: Employee DataTable filtering
     *
     * @since 1.0.0
     */
    private function initFilters() {
        // 1. Generic DataTable Access Filter (framework)
        new \WPAgency\Controllers\Integration\DataTableAccessFilter();

        // 2. Agency Role Filter (specific implementation)
        new \WPAgency\Integrations\AgencyRoleFilter();

        // 3. Division Access Filter
        new \WPAgency\Integrations\DivisionAccessFilter();

        // 4. Employee Access Filter
        new \WPAgency\Integrations\EmployeeAccessFilter();
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

// Initialize the plugin
wp_agency()->run();
