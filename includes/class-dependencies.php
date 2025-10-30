<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.0.8
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS, JavaScript,
 *              dan library eksternal
 *
 * Changelog:
 * 1.0.8 - 2025-10-25
 * - Updated agency-datatable.js to version 2.0.0 (TODO-3077)
 * - Added wp_localize_script for wpAgencyDataTable with i18n translations
 * - Removed wp-agency-toast dependency from agency-datatable
 * - Integrated with base panel system from wp-app-core
 * 1.1.0 - 2024-12-10
 * - Added division management dependencies
 * - Added division CSS and JS files
 * - Updated screen checks for division assets
 * - Fixed path inconsistencies
 * - Added common-style.css
 *
 * 1.0.0 - 2024-11-23
 * - Initial creation
 * - Added asset enqueuing methods
 * - Added CDN dependencies
 */
class WP_Agency_Dependencies {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'leaflet_enqueue_scripts']); // Add this line

    }

public function enqueue_frontend_assets() {
    // Ignore admin and ajax requests
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    if (get_query_var('wp_agency_register') !== '') {
        error_log('Enqueuing registration assets...');

        // Register page specific style
        wp_enqueue_style(
            'wp-agency-register',
            WP_AGENCY_URL . 'assets/css/auth/register.css',
            [],
            $this->version
        );


        // Enqueue styles
        wp_enqueue_style(
            'wp-agency-form',
            WP_AGENCY_URL . 'assets/css/agency/agency-form.css',
            [],
            $this->version
        );

        wp_enqueue_style(
            'wp-agency-toast',
            WP_AGENCY_URL . 'assets/css/agency/toast.css',
            [],
            $this->version
        );

        // Core scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'jquery-validate',
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js',
            ['jquery'],
            '1.19.5',
            true
        );

        // Toast component
        wp_enqueue_script(
            'wp-agency-toast',
            WP_AGENCY_URL . 'assets/js/agency/agency-toast.js',
            ['jquery'],
            $this->version,
            true
        );

        // Wilayah Indonesia plugin scripts (for cascade select)
        $wilayah_plugin_url = plugins_url('wilayah-indonesia');
        wp_enqueue_script(
            'wilayah-select-handler-core',
            $wilayah_plugin_url . '/assets/js/components/select-handler-core.js',
            ['jquery'],
            '1.1.0',
            true
        );
        wp_enqueue_script(
            'wilayah-select-handler-ui',
            $wilayah_plugin_url . '/assets/js/components/select-handler-ui.js',
            ['jquery', 'wilayah-select-handler-core'],
            '1.1.0',
            true
        );

        // Localize wilayah scripts
        wp_localize_script('wilayah-select-handler-core', 'wilayahData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wilayah_select_nonce'),
            'debug' => false,
            'texts' => [
                'loading' => __('Memuat...', 'wp-agency'),
                'error' => __('Gagal memuat data', 'wp-agency'),
                'select_regency' => __('Pilih Kabupaten/Kota', 'wp-agency')
            ]
        ]);

        // Wilayah sync helper
        wp_enqueue_script(
            'wp-agency-wilayah-sync',
            WP_AGENCY_URL . 'assets/js/auth/wilayah-sync.js',
            ['jquery', 'wilayah-select-handler-core'],
            $this->version,
            true
        );

        // Registration form handler
        wp_enqueue_script(
            'wp-agency-register',
            WP_AGENCY_URL . 'assets/js/auth/register.js',
            ['jquery', 'jquery-validate', 'wp-agency-toast', 'wp-agency-wilayah-sync'],
            $this->version,
            true
        );

        // Localize script (using wp_agency_nonce for consistency)
        wp_localize_script(
            'wp-agency-register',
            'wpAgencyData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_agency_nonce'),
                'i18n' => [
                    'registering' => __('Mendaftar...', 'wp-agency'),
                    'register' => __('Daftar', 'wp-agency'),
                    'error' => __('Terjadi kesalahan. Silakan coba lagi.', 'wp-agency')
                ]
            ]
        );
        error_log('Registration assets enqueued');
    }
}



    public function enqueue_styles() {

        $screen = get_current_screen();
        if (!$screen) return;
        // Check if we're on the registration page
        if (get_query_var('wp_agency_register')) {
            // Enqueue registration-specific styles
            wp_enqueue_style('wp-agency-register', WP_AGENCY_URL . 'assets/css/auth/register.css', [], $this->version);
            wp_enqueue_style('wp-agency-toast', WP_AGENCY_URL . 'assets/css/agency/toast.css', [], $this->version);
            return;
        }


        // Settings page styles
        if ($screen->id === 'wp-agency_page_wp-agency-settings') {
           // Main settings styles (includes common styles)
           wp_enqueue_style('wp-agency-settings', WP_AGENCY_URL . 'assets/css/settings/settings-style.css', [], $this->version);
           wp_enqueue_style('wp-agency-modal', WP_AGENCY_URL . 'assets/css/agency/confirmation-modal.css', [], $this->version);

           // Get current tab and permission tab
           $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
           $permission_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : '';

           switch ($current_tab) {
               case 'permissions':
                   wp_enqueue_style(
                       'wp-agency-permissions-tab',
                       WP_AGENCY_URL . 'assets/css/settings/permissions-tab-style.css',
                       ['wp-agency-settings'],
                       $this->version
                   );
                   break;

               case 'general':
                   wp_enqueue_style(
                       'wp-agency-general-tab', 
                       WP_AGENCY_URL . 'assets/css/settings/general-tab-style.css',
                       ['wp-agency-settings'],
                       $this->version
                   );
                   break;



               case 'demo-data':
                   wp_enqueue_style(
                       'wp-agency-demo-data-tab',
                       WP_AGENCY_URL . 'assets/css/settings/demo-data-tab-style.css',
                       ['wp-agency-settings'],
                       $this->version
                   );
                   break;
           }
        }

        // Agency and Division pages styles
        // Support both old menu (wp-agency) and new menu (wp-agency-disnaker)
        if (in_array($screen->id, ['toplevel_page_wp-agency', 'toplevel_page_wp-agency-disnaker'])) {
            // Core styles
            wp_enqueue_style('wp-agency-toast', WP_AGENCY_URL . 'assets/css/agency/toast.css', [], $this->version);
            wp_enqueue_style('wp-agency-modal', WP_AGENCY_URL . 'assets/css/agency/confirmation-modal.css', [], $this->version);
            // Division toast - terpisah
            wp_enqueue_style('division-toast', WP_AGENCY_URL . 'assets/css/division/division-toast.css', [], $this->version);

            // DataTables
            wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

            // Agency styles - LOCAL SCOPE ONLY
            // Global layout/structure handled by wp-app-core/wpapp-datatable.css
            // This file contains agency-specific enhancements (hover effects, colors, etc.)
            wp_enqueue_style('wp-agency-agency', WP_AGENCY_URL . 'assets/css/agency/agency-style.css', [], $this->version);

            // Agency detail panel styles - LOCAL SCOPE ONLY
            // Styles for info.php and details.php tabs in right panel
            // All classes use agency-* prefix (strict scope separation)
            wp_enqueue_style('wp-agency-detail', WP_AGENCY_URL . 'assets/css/agency/agency-detail.css', [], $this->version);

            // Division styles
            wp_enqueue_style('wp-agency-division', WP_AGENCY_URL . 'assets/css/division/division-style.css', [], $this->version);

            // Tambahkan Employee styles
            wp_enqueue_style('wp-agency-employee', WP_AGENCY_URL . 'assets/css/employee/employee-style.css', [], $this->version);
            wp_enqueue_style('employee-toast', WP_AGENCY_URL . 'assets/css/employee/employee-toast.css', [], $this->version);
            
            // New Company styles
            wp_enqueue_style('wp-agency-new-company', WP_AGENCY_URL . 'assets/css/company/new-company-style.css', [], $this->version);
        }

    }

    public function enqueue_scripts() {
        // Check if we're on the registration page (BEFORE get_current_screen check)
        if (get_query_var('wp_agency_register')) {
            error_log('WP_Agency: Enqueuing registration scripts...');
            // Enqueue registration-specific scripts
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-agency-wilayah-sync', WP_AGENCY_URL . 'assets/js/auth/wilayah-sync.js', ['jquery'], $this->version, true);
            error_log('WP_Agency: wilayah-sync enqueued');
            wp_enqueue_script('wp-agency-register', WP_AGENCY_URL . 'assets/js/auth/register.js', ['jquery', 'jquery-validate', 'wp-agency-toast', 'wp-agency-wilayah-sync'], $this->version, true);

            // Localize script for both register and wilayah-sync
            wp_localize_script('wp-agency-register', 'wpAgencyData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_agency_nonce')
            ]);
            return;
        }

        // Get screen for admin pages
        $screen = get_current_screen();
        if (!$screen) return;

        // Settings page scripts
        if ($screen->id === 'wp-agency_page_wp-agency-settings') {
            // Common scripts for settings page
            wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-agency-settings', WP_AGENCY_URL . 'assets/js/settings/settings-script.js', ['jquery', 'wp-agency-toast'], $this->version, true);
            
            // Get current tab and permission tab
            $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
            $permission_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : '';

            switch ($current_tab) {
                case 'permissions':
                    wp_enqueue_script(
                        'wp-agency-permissions-tab',
                        WP_AGENCY_URL . 'assets/js/settings/agency-permissions-tab-script.js',
                        ['jquery', 'wp-agency-settings'],
                        $this->version,
                        true
                    );
                               
                    // Add localize script here
                    wp_localize_script('wp-agency-permissions-tab', 'wpAgencyData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_agency_reset_permissions'),
                        'i18n' => [
                            'resetConfirmTitle' => __('Reset Permissions?', 'wp-agency'),
                            'resetConfirmMessage' => __('This will restore all permissions to their default settings. This action cannot be undone.', 'wp-agency'),
                            'resetConfirmButton' => __('Reset Permissions', 'wp-agency'),
                            'resetting' => __('Resetting...', 'wp-agency'),
                            'cancelButton' => __('Cancel', 'wp-agency')
                        ]
                    ]);
                    break;
                case 'general':
                        // Modal components
                        wp_enqueue_script(
                            'confirmation-modal',
                            WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js',
                            ['jquery'],
                            $this->version,
                            true
                        );

                        // Localize script
                        wp_localize_script('wp-agency-settings', 'wpAgencyData', [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'clearCacheNonce' => wp_create_nonce('wp_agency_clear_cache')
                        ]);
                    break;

                case 'demo-data':
                    wp_enqueue_script(
                        'wp-agency-demo-data-tab',
                        WP_AGENCY_URL . 'assets/js/settings/agency-demo-data-tab-script.js',
                        ['jquery', 'wp-agency-settings'],
                        $this->version,
                        true
                    );

                    wp_localize_script('wp-agency-demo-data-tab', 'wpAgencyDemoData', [
                        'i18n' => [
                            'errorMessage' => __('An error occurred while generating demo data.', 'wp-agency'),
                            'generating' => __('Generating...', 'wp-agency')
                        ]
                    ]);
                    break;
            }

        }

        // Agency and Division pages scripts
        // Support both old menu (wp-agency) and new menu (wp-agency-disnaker)
        if (in_array($screen->id, ['toplevel_page_wp-agency', 'toplevel_page_wp-agency-disnaker'])) {
            // Core dependencies
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);
            wp_enqueue_script('jquery-inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', array('jquery'), null, true);

            // Select2 for jurisdiction selects
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
            // Components
            wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js', ['jquery'], $this->version, true);
            // Division toast
            wp_enqueue_script('division-toast', WP_AGENCY_URL . 'assets/js/division/division-toast.js', ['jquery'], $this->version, true);
        
            // Tambah handler untuk wilayah
            $this->enqueue_wilayah_handler();


            // Agency scripts - path fixed according to tree.md
            wp_enqueue_script('agency-datatable', WP_AGENCY_URL . 'assets/js/agency/agency-datatable.js', ['jquery', 'datatables'], '2.0.0', true);

            // Localize agency-datatable with translations (TODO-3077)
            wp_localize_script('agency-datatable', 'wpAgencyDataTable', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpapp_panel_nonce'),
                'i18n' => [
                    'processing' => __('Loading...', 'wp-agency'),
                    'search' => __('Search:', 'wp-agency'),
                    'lengthMenu' => __('Show _MENU_ entries', 'wp-agency'),
                    'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-agency'),
                    'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'wp-agency'),
                    'infoFiltered' => __('(filtered from _MAX_ total entries)', 'wp-agency'),
                    'zeroRecords' => __('No matching records found', 'wp-agency'),
                    'emptyTable' => __('No data available in table', 'wp-agency'),
                    'confirmDelete' => __('Are you sure you want to delete this agency?', 'wp-agency'),
                    'paginate' => [
                        'first' => __('First', 'wp-agency'),
                        'previous' => __('Previous', 'wp-agency'),
                        'next' => __('Next', 'wp-agency'),
                        'last' => __('Last', 'wp-agency')
                    ]
                ]
            ]);

            // Agency filter JS (status filter dropdown)
            wp_enqueue_script('agency-filter', WP_AGENCY_URL . 'assets/js/agency/agency-filter.js', ['jquery', 'datatables'], $this->version, true);

            wp_enqueue_script('create-agency-form', WP_AGENCY_URL . 'assets/js/agency/create-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);
            wp_enqueue_script('edit-agency-form', WP_AGENCY_URL . 'assets/js/agency/edit-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);

            // DISABLED for testing (TODO-3080)
            // Test if scroll jump comes from agency-script.js
            // wpAppPanelManager should handle everything now
            /*
            wp_enqueue_script('agency',
                WP_AGENCY_URL . 'assets/js/agency/agency-script.js',
                [
                    'jquery',
                    'wp-agency-toast',
                    'agency-datatable',
                    'create-agency-form',
                    'edit-agency-form'
                ],
                $this->version,
                true
            );
            */

            // Division scripts
            wp_enqueue_script('division-datatable', WP_AGENCY_URL . 'assets/js/division/division-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency'], $this->version, true);
            wp_enqueue_script('division-toast', WP_AGENCY_URL . 'assets/js/division/division-toast.js', ['jquery'], $this->version, true);
            // Update dependencies untuk form - add agency dependency for wpAgencyData
            wp_enqueue_script('create-division-form', WP_AGENCY_URL . 'assets/js/division/create-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable', 'agency'], $this->version, true);
            wp_enqueue_script('edit-division-form', WP_AGENCY_URL . 'assets/js/division/edit-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable', 'agency'], $this->version, true);

            // Employee scripts - mengikuti pola division yang sudah berhasil
            wp_enqueue_script('employee-datatable', WP_AGENCY_URL . 'assets/js/employee/employee-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency'], $this->version, true);
            wp_enqueue_script('employee-toast', WP_AGENCY_URL . 'assets/js/employee/employee-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('create-employee-form', WP_AGENCY_URL . 'assets/js/employee/create-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
            wp_enqueue_script('edit-employee-form', WP_AGENCY_URL . 'assets/js/employee/edit-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);

            // New Company scripts
            wp_enqueue_script('new-company-datatable', WP_AGENCY_URL . 'assets/js/company/new-company-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency'], $this->version, true);

            // Gunakan wpAgencyData untuk semua
            $agency_nonce = wp_create_nonce('wp_agency_nonce');
            wp_localize_script('agency', 'wpAgencyData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => $agency_nonce,
                'debug' => true,
                'perPage' => 10
            ]);
        }

    }

    private function enqueue_wilayah_handler() {
        // Use direct constant check first
        if (!defined('WILAYAH_INDONESIA_URL')) {
            error_log('Wilayah Indonesia plugin is not installed');
            return;
        }

        // Cek apakah sudah di-enqueue sebelumnya
        if (wp_script_is('wilayah-select-handler-core', 'enqueued')) {
            return;
        }

        // Enqueue core handler dari plugin wilayah-indonesia
        wp_enqueue_script(
            'wilayah-select-handler-core',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-core.js',
            ['jquery'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

        // Enqueue UI handler dari plugin wilayah-indonesia
        wp_enqueue_script(
            'wilayah-select-handler-ui',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-ui.js',
            ['jquery', 'wilayah-select-handler-core'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

        // Localize script data
        wp_localize_script('wilayah-select-handler-core', 'wilayahData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wilayah_select_nonce'),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG),
            'texts' => [
                'select_provinsi' => __('Pilih Provinsi', 'wp-agency'),
                'select_regency' => __('Pilih Kabupaten/Kota', 'wp-agency'),
                'loading' => __('Memuat...', 'wp-agency'),
                'error' => __('Gagal memuat data', 'wp-agency')
            ]
        ]);
    }

    public function leaflet_enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) return;

        // Support both old menu (wp-agency) and new menu (wp-agency-disnaker)
        if (in_array($screen->id, ['toplevel_page_wp-agency', 'toplevel_page_wp-agency-disnaker'])) {
            // Leaflet CSS & JS
            wp_enqueue_style(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                [],
                '1.9.4'
            );
            
            wp_enqueue_script(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                [],
                '1.9.4',
                true
            );

            // Custom map picker
            wp_enqueue_script(
                'wp-agency-map-picker',
                WP_AGENCY_URL . 'assets/js/division/map-picker.js',
                ['jquery', 'leaflet'],
                $this->version,
                true
            );

            // Localize script dengan settings
            wp_localize_script(
                'wp-agency-map-picker',
                'wpAgencyMapSettings',
                [
                    'defaultLat' => get_option('wp_agency_settings')['map_default_lat'] ?? -6.200000,
                    'defaultLng' => get_option('wp_agency_settings')['map_default_lng'] ?? 106.816666,
                    'defaultZoom' => get_option('wp_agency_settings')['map_default_zoom'] ?? 12
                ]
            );
        }
    }

}
