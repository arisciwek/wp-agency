<?php
/**
 * File: MenuManager.php
 * Path: /wp-agency/src/Controllers/MenuManager.php
 * 
 * @package     WP_Agency
 * @subpackage  Admin/Controllers
 * @version     1.0.1
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
        // Menu WP Agency
        add_menu_page(
            __('WP Agency', 'wp-agency'),
            __('WP Agency', 'wp-agency'),
            'manage_options',
            'wp-agency',
            [$this->agency_controller, 'renderMainPage'],
            'dashicons-businessperson',
            30
        );

        // Submenu Settings
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