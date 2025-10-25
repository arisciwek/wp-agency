<?php
/**
 * File: MenuManager.php
 * Path: /wp-agency/src/Controllers/MenuManager.php
 * 
 * @package     WP_Agency
 * @subpackage  Admin/Controllers
 * @version     1.0.7
 * @author      arisciwek
 */

namespace WPAgency\Controllers;

use WPAgency\Controllers\SettingsController;
use WPAgency\Controllers\AgencyController;


class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;
    private $agency_controller;
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new SettingsController();
        $this->agency_controller = new AgencyController();
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Menu WP Agency - menggunakan view_agency_list agar role agency bisa akses
        add_menu_page(
            __('WP Agency', 'wp-agency'),
            __('WP Agency', 'wp-agency'),
            'view_agency_list',
            'wp-agency',
            [$this->agency_controller, 'renderMainPage'],
            'dashicons-businessperson',
            30
        );

        // NEW: Disnaker Menu - Base Panel System (TODO-2071)
        $disnaker_hook = add_menu_page(
            __('Disnaker', 'wp-agency'),
            __('Disnaker', 'wp-agency'),
            'view_agency_list',  // Use existing capability
            'wp-agency-disnaker',
            function() {
                include \WP_AGENCY_PATH . 'src/Views/agency/dashboard.php';
            },
            'dashicons-building',
            31
        );

        // Register page hook for wp-app-core assets
        add_filter('wpapp_datatable_allowed_hooks', function($hooks) use ($disnaker_hook) {
            $hooks[] = $disnaker_hook;
            return $hooks;
        });

        // Enqueue DataTable assets for Disnaker page
        add_action('admin_enqueue_scripts', function($hook) use ($disnaker_hook) {
            if ($hook === $disnaker_hook) {
                // Enqueue jQuery DataTables library from CDN
                wp_enqueue_style(
                    'jquery-datatables-css',
                    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
                    [],
                    '1.13.7'
                );

                wp_enqueue_script(
                    'jquery-datatables',
                    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                    ['jquery'],
                    '1.13.7',
                    true
                );

                // Localize script with nonce and ajaxurl
                // Use wpapp_panel_nonce for consistency with wp-app-core panel system
                wp_localize_script('jquery-datatables', 'wpAgency', [
                    'nonce' => wp_create_nonce('wpapp_panel_nonce'),
                    'ajaxurl' => admin_url('admin-ajax.php')
                ]);

                // Localize wpAppConfig for wp-app-core panel manager
                // MUST use wpapp_panel_nonce to match DataTableAssetsController
                wp_localize_script('jquery-datatables', 'wpAppConfig', [
                    'nonce' => wp_create_nonce('wpapp_panel_nonce'),
                    'ajaxUrl' => admin_url('admin-ajax.php')
                ]);

                // Force enqueue DataTable assets from wp-app-core
                if (class_exists('WPAppCore\\Controllers\\DataTable\\DataTableAssetsController')) {
                    \WPAppCore\Controllers\DataTable\DataTableAssetsController::force_enqueue();
                }

                // Enqueue agency header cards CSS (scope local)
                wp_enqueue_style(
                    'agency-header-cards',
                    \WP_AGENCY_URL . 'assets/css/agency/agency-header-cards.css',
                    [],
                    '1.0.0'
                );

                // Enqueue agency filter CSS (scope local)
                wp_enqueue_style(
                    'agency-filter',
                    \WP_AGENCY_URL . 'assets/css/agency/agency-filter.css',
                    [],
                    '1.0.0'
                );

                // Enqueue agency filter JS (scope local)
                wp_enqueue_script(
                    'agency-filter',
                    \WP_AGENCY_URL . 'assets/js/agency/agency-filter.js',
                    ['jquery', 'jquery-datatables'],
                    '1.0.0',
                    true
                );

                // Enqueue diagnostic script if ?diagnostic=1 in URL
                if (isset($_GET['diagnostic']) && $_GET['diagnostic'] == '1') {
                    wp_enqueue_script(
                        'wp-agency-diagnostic',
                        \WP_AGENCY_URL . 'diagnostic-script.js',
                        ['jquery', 'wpapp-panel-manager'],
                        '1.0.0',
                        true
                    );
                }
            }
        });

        // Submenu Settings - tetap menggunakan manage_options untuk admin only
        add_submenu_page(
            'wp-agency',
            __('Pengaturan', 'wp-agency'),
            __('Pengaturan', 'wp-agency'),
            'manage_options',
            'wp-agency-settings',
            [$this->settings_controller, 'renderPage']
        );
    }
}