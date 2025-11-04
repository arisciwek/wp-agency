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
        $agency_hook = add_menu_page(
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
                include \WP_AGENCY_PATH . 'src/Views/DataTable/Templates/dashboard.php';
            },
            'dashicons-building',
            31
        );

        // REMOVED (TODO-1192): Plug & Play Pattern Implementation
        //
        // Assets sekarang di-load OTOMATIS oleh DashboardTemplate::render()
        // Plugin TIDAK PERLU register hooks ke core - ini adalah anti-pattern!
        //
        // Arsitektur yang benar:
        // - wp-app-core = CONTAINER (menyediakan slot kosong)
        // - wp-agency = PLUGIN (mengisi slot via hooks)
        // - Container auto-detect usage dan load assets secara otomatis
        //
        // Prinsip: Plugin TIDAK boleh modifikasi core behavior
        // OLD (BAD): add_filter('wpapp_datatable_allowed_hooks', ...)
        // NEW (GOOD): Just call DashboardTemplate::render() - assets loaded automatically!

        // Submenu Settings - tetap menggunakan manage_options untuk admin only
        add_submenu_page(
            'wp-agency',
            __('Pengaturan Disnaker', 'wp-agency'),
            __('Pengaturan', 'wp-agency'),
            'manage_options',
            'wp-agency-settings',
            [$this->settings_controller, 'renderPage']
        );
    }
}