<?php
/**
 * File: SettingsController.php 
 * Path: /wp-agency/src/Controllers/Settings/SettingsController.php
 * Description: Controller untuk mengelola halaman pengaturan plugin termasuk matrix permission
 * Version: 3.0.0
 * Last modified: 2024-11-28 08:45:00
 * 
 * Changelog:
 * v3.0.0 - 2024-11-28
 * - Perbaikan handling permission matrix
 * - Penambahan validasi dan error handling
 * - Optimasi performa loading data
 * - Penambahan logging aktivitas
 * 
 * v2.0.0 - 2024-11-27
 * - Integrasi dengan WordPress Roles API
 * 
 * Dependencies:
 * - PermissionModel
 * - SettingsModel 
 * - WordPress admin functions
 */

namespace WPAgency\Controllers;


use WPAgency\Cache\AgencyCacheManager;

class SettingsController {
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->register_ajax_handlers();
    }

    // Add this to your SettingsController or appropriate controller class
    public function register_ajax_handlers() {
        add_action('wp_ajax_reset_agency_permissions', [$this, 'handle_reset_agency_permissions']);
        add_action('wp_ajax_agency_generate_demo_data', [$this, 'handle_generate_demo_data']);
        add_action('wp_ajax_agency_check_demo_data', [$this, 'handle_check_demo_data']);
    


    }







    public function handle_reset_agency_permissions() {
        try {
            // Verify nonce
            check_ajax_referer('wp_agency_reset_permissions', 'nonce');

            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'wp-agency'));
            }

            // Reset permissions using PermissionModel
            $permission_model = new \WPAgency\Models\Settings\PermissionModel();
            $success = $permission_model->resetToDefault();

            if (!$success) {
                throw new \Exception(__('Failed to reset permissions.', 'wp-agency'));
            }

            wp_send_json_success([
                'message' => __('Permissions have been reset to default settings.', 'wp-agency')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function register_settings() {
        // General Settings
        register_setting(
            'wp_agency_settings',
            'wp_agency_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => array(
                    'datatables_page_length' => 25,
                    'enable_cache' => 0,
                    'cache_duration' => 3600,
                    'enable_debug' => 0,
                    'enable_pusher' => 0,
                    'pusher_app_key' => '',
                    'pusher_app_secret' => '',
                    'pusher_cluster' => 'ap1'
                )
            )
        );

        // Development Settings
        register_setting(
            'wp_agency_development_settings',
            'wp_agency_development_settings',
            array(
                'sanitize_callback' => [$this, 'sanitize_development_settings'],
                'default' => array(
                    'enable_development' => 0,
                    'clear_data_on_deactivate' => 0
                )
            )
        );
    }

    public function sanitize_development_settings($input) {
        $sanitized = array();
        $sanitized['enable_development'] = isset($input['enable_development']) ? 1 : 0;
        $sanitized['clear_data_on_deactivate'] = isset($input['clear_data_on_deactivate']) ? 1 : 0;
        return $sanitized;
    }

    public function sanitize_settings($input) {
        $sanitized = array();

        // General settings sanitization
        $sanitized['datatables_page_length'] = absint($input['datatables_page_length']);
        $sanitized['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;
        $sanitized['cache_duration'] = absint($input['cache_duration']);
        $sanitized['enable_debug'] = isset($input['enable_debug']) ? 1 : 0;

        // Pusher sanitization
        $sanitized['enable_pusher'] = isset($input['enable_pusher']) ? 1 : 0;
        $sanitized['pusher_app_key'] = sanitize_text_field($input['pusher_app_key']);
        $sanitized['pusher_app_secret'] = sanitize_text_field($input['pusher_app_secret']);
        $sanitized['pusher_cluster'] = sanitize_text_field($input['pusher_cluster']);

        return $sanitized;
    }

    /**
     * Get the appropriate generator class based on data type
     *
     * @param string $type The type of data to generate
     * @return AbstractDemoData Generator instance
     * @throws \Exception If invalid type specified
     */
    private function getGeneratorClass($type) {

        error_log('=== Start WP Agency getGeneratorClass ===');  // Log 1
        error_log('Received type: ' . $type);          // Log 2
        
        error_log('getGeneratorClass received type: [' . $type . ']');
        error_log('Type length: ' . strlen($type));
        error_log('Type character codes: ' . json_encode(array_map('ord', str_split($type))));   

        switch ($type) {
            case 'users':
                return new \WPAgency\Database\Demo\WPUserGenerator();
            case 'agency':
                return new \WPAgency\Database\Demo\AgencyDemoData();
            case 'division':
                return new \WPAgency\Database\Demo\DivisionDemoData();
            case 'employee':
                return new \WPAgency\Database\Demo\AgencyEmployeeDemoData();

            case 'jurisdiction':
                return new \WPAgency\Database\Demo\JurisdictionDemoData();
            default:
                throw new \Exception('Invalid demo data type: ' . $type);
        }
    }

    public function handle_generate_demo_data() {
        try {

            // Validate nonce and permissions first
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $type = sanitize_text_field($_POST['type']);
            $nonce = sanitize_text_field($_POST['nonce']);

            if (!wp_verify_nonce($nonce, "generate_demo_{$type}")) {
                throw new \Exception('Invalid security token');
            }

            // Get the generator class based on type
            $generator = $this->getGeneratorClass($type);
            
            // Check if development mode is enabled before proceeding
            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => 'Cannot generate demo data - Development mode is not enabled. Please enable it in settings first.',
                    'type' => 'dev_mode_off'  // Menandakan error karena development mode off
                ]);
                return;
            }

            // If development mode is on, proceed with generation
            if ($generator->run()) {
                wp_send_json_success([
                    'message' => ucfirst($type) . ' data generated successfully.',
                    'type' => 'success'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to generate demo data.',
                    'type' => 'error'  // Menandakan error teknis
                ]);
            }

        } catch (\Exception $e) {
            error_log('Demo data generation failed: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Failed to generate demo data.',
                'type' => 'error'
            ]);
        }
    }

    public function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-agency'));
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        
        require_once WP_AGENCY_PATH . 'src/Views/templates/settings/settings_page.php';
        $this->loadTabView($current_tab);
    }


    
    private function loadTabView($tab) {
        $allowed_tabs = [
            'general' => 'tab-general.php',
            'permissions' => 'tab-permissions.php',
            'demo-data' => 'tab-demo-data.php'
        ];
        
        $tab = isset($allowed_tabs[$tab]) ? $tab : 'general';


            
        $tab_file = WP_AGENCY_PATH . 'src/Views/templates/settings/' . $allowed_tabs[$tab];
        
        if (file_exists($tab_file)) {
            if (isset($view_data)) {
                extract($view_data);
            }
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file tidak ditemukan: %s', 'wp-agency'),
                esc_html($tab_file)
            );
        }
    }



    public function handle_check_demo_data() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $type = sanitize_text_field($_POST['type']);
            $nonce = sanitize_text_field($_POST['nonce']);

            if (!wp_verify_nonce($nonce, "check_demo_{$type}")) {
                throw new \Exception('Invalid security token');
            }

            global $wpdb;
            $has_data = false;
            $count = 0;

            switch($type) {
                case 'division':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions");
                    $has_data = ($count > 0);
                    break;
                case 'agency':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_agencies");
                    $has_data = ($count > 0);
                    break;

                case 'jurisdiction':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_jurisdictions");
                    $has_data = ($count > 0);
                    break;
                default:
                    throw new \Exception('Invalid data type');
            }

            wp_send_json_success([
                'has_data' => $has_data,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

}
