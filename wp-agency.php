<?php
/**
 * Plugin Name: WP Agency
 * Plugin URI: 
 * Description: Plugin untuk mengelola data Agency dan Divisionnya
 * Version: 1.0.0
 * Author: arisciwek
 * Author URI: 
 * License: GPL v2 or later
 * 
 * @package     WP_Agency
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-agency/wp-agency.php
 */

defined('ABSPATH') || exit;

// Define plugin constants first, before anything else
define('WP_AGENCY_VERSION', '1.0.0');
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
        require_once WP_AGENCY_PATH . 'includes/class-activator.php';
        require_once WP_AGENCY_PATH . 'includes/class-deactivator.php';
        require_once WP_AGENCY_PATH . 'includes/class-dependencies.php';
        require_once WP_AGENCY_PATH . 'includes/class-init-hooks.php';

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

        // Initialize other hooks
        $init_hooks = new WP_Agency_Init_Hooks();
        $init_hooks->init();
    }

    /**
     * Initialize plugin controllers
     */
    private function initControllers() {
        // Agency Controller
        $this->agency_controller = new \WPAgency\Controllers\AgencyController();

        // Employee Controller
        new \WPAgency\Controllers\Employee\AgencyEmployeeController();

        // Division Controller
        new \WPAgency\Controllers\Division\DivisionController();

        // Jurisdiction Controller
        new \WPAgency\Controllers\Division\JurisdictionController();

        // Membership Controller
        new \WPAgency\Controllers\Membership\AgencyMembershipController();

        // Register AJAX handlers
        add_action('wp_ajax_get_agency_stats', [$this->agency_controller, 'getStats']);
        add_action('wp_ajax_handle_agency_datatable', [$this->agency_controller, 'handleDataTableRequest']);
        add_action('wp_ajax_get_agency', [$this->agency_controller, 'show']);
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
