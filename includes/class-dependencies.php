<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS, JavaScript,
 *              dan library eksternal
 *
 * Changelog:
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
            WP_AGENCY_URL . 'assets/css/agency-form.css',
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

        // Registration form handler
        wp_enqueue_script(
            'wp-agency-register',
            WP_AGENCY_URL . 'assets/js/auth/register.js',
            ['jquery', 'jquery-validate', 'wp-agency-toast'],
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            'wp-agency-register',
            'wpAgencyData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_agency_register'),
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


        // Settings page styles// Settings page styles
        if ($screen->id === 'wp-agency_page_wp-agency-settings') {
           // Common styles for settings page
           wp_enqueue_style('wp-agency-common', WP_AGENCY_URL . 'assets/css/settings/common-style.css', [], $this->version);
           wp_enqueue_style('wp-agency-settings', WP_AGENCY_URL . 'assets/css/settings/settings-style.css', ['wp-agency-common'], $this->version);
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

               case 'membership-levels':
                   wp_enqueue_style(
                       'wp-agency-membership-levels-tab',
                       WP_AGENCY_URL . 'assets/css/settings/agency-membership-levels-tab-style.css',
                       ['wp-agency-settings'],
                       $this->version
                   );
                   break;

                case 'membership-features':
                    wp_enqueue_style(
                        'wp-agency-membership-features-tab',
                        WP_AGENCY_URL . 'assets/css/settings/membership-features-tab-style.css',
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
        if ($screen->id === 'toplevel_page_wp-agency') {
            // Core styles
            wp_enqueue_style('wp-agency-toast', WP_AGENCY_URL . 'assets/css/agency/toast.css', [], $this->version);
            wp_enqueue_style('wp-agency-modal', WP_AGENCY_URL . 'assets/css/agency/confirmation-modal.css', [], $this->version);
            // Division toast - terpisah
            wp_enqueue_style('division-toast', WP_AGENCY_URL . 'assets/css/division/division-toast.css', [], $this->version);

            // DataTables
            wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

            // Agency styles
            wp_enqueue_style('wp-agency-agency', WP_AGENCY_URL . 'assets/css/agency/agency-style.css', [], $this->version);
            wp_enqueue_style('wp-agency-membership-levels-tab', WP_AGENCY_URL . 'assets/css/agency/agency-membership-tab-style.css', [], $this->version);

            wp_enqueue_style('wp-agency-agency-form', WP_AGENCY_URL . 'assets/css/agency/agency-form.css', [], $this->version);

            // Division styles
            wp_enqueue_style('wp-agency-division', WP_AGENCY_URL . 'assets/css/division/division-style.css', [], $this->version);

            // Tambahkan Employee styles
            wp_enqueue_style('wp-agency-employee', WP_AGENCY_URL . 'assets/css/employee/employee-style.css', [], $this->version);
            wp_enqueue_style('employee-toast', WP_AGENCY_URL . 'assets/css/employee/employee-toast.css', [], $this->version);
        }

    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) return;

        // Check if we're on the registration page
        if (get_query_var('wp_agency_register')) {
            // Enqueue registration-specific scripts
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('wp-agency-register', WP_AGENCY_URL . 'assets/js/auth/register.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);
            
            // Localize script
            wp_localize_script('wp-agency-register', 'wpAgencyData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_agency_register')
            ]);
            return;
        }

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
                case 'membership-features':
                    wp_enqueue_script(
                        'wp-agency-membership-features-tab',
                        WP_AGENCY_URL . 'assets/js/settings/agency-membership-features-tab-script.js',
                        ['jquery', 'wp-agency-settings'],
                        $this->version,
                        true
                    );
                    wp_localize_script(
                        'wp-agency-membership-features-tab',
                        'wpAgencySettings',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('wp_agency_nonce'),
                            'i18n' => [
                                'addFeature' => __('Add New Feature', 'wp-agency'),
                                'editFeature' => __('Edit Feature', 'wp-agency'),
                                'deleteConfirm' => __('Are you sure you want to delete this feature?', 'wp-agency'),
                                'loadError' => __('Failed to load feature data', 'wp-agency'),
                                'saveError' => __('Failed to save feature', 'wp-agency'),
                                'deleteError' => __('Failed to delete feature', 'wp-agency'),
                                'saving' => __('Saving...', 'wp-agency'),
                                'loading' => __('Loading...', 'wp-agency')
                            ]
                        ]
                    );
                    break;
                case 'membership-levels':
                    wp_enqueue_script(
                        'wp-agency-membership',
                        WP_AGENCY_URL . 'assets/js/settings/agency-membership-levels-tab-script.js',
                        ['jquery', 'wp-agency-settings'],
                        WP_AGENCY_VERSION,
                        true
                    );

                    wp_localize_script('wp-agency-membership', 'wpAgencyData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wp_agency_nonce'),
                        'i18n' => [
                            'confirmDelete' => __('Are you sure you want to delete this membership level?', 'wp-agency'),
                            'saveSuccess' => __('Membership level saved successfully.', 'wp-agency'),
                            'saveError' => __('Failed to save membership level.', 'wp-agency'),
                            'deleteSuccess' => __('Membership level deleted successfully.', 'wp-agency'),
                            'deleteError' => __('Failed to delete membership level.', 'wp-agency'),
                            'loadError' => __('Failed to load membership level data.', 'wp-agency'),
                            'required' => __('This field is required.', 'wp-agency'),
                            'invalidNumber' => __('Please enter a valid number.', 'wp-agency')
                        ]
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
        if ($screen->id === 'toplevel_page_wp-agency') {
            // Core dependencies
            wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);
            wp_enqueue_script('jquery-inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', array('jquery'), null, true);
            // Components
            wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('confirmation-modal', WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js', ['jquery'], $this->version, true);
            // Division toast
            wp_enqueue_script('division-toast', WP_AGENCY_URL . 'assets/js/division/division-toast.js', ['jquery'], $this->version, true);
        
            // Tambah handler untuk wilayah
            $this->enqueue_wilayah_handler();


            // Agency scripts - path fixed according to tree.md
            wp_enqueue_script('agency-datatable', WP_AGENCY_URL . 'assets/js/agency/agency-datatable.js', ['jquery', 'datatables', 'wp-agency-toast'], $this->version, true);
            wp_enqueue_script('create-agency-form', WP_AGENCY_URL . 'assets/js/agency/create-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);
            wp_enqueue_script('edit-agency-form', WP_AGENCY_URL . 'assets/js/agency/edit-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);

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

            // Division scripts
            wp_enqueue_script('division-datatable', WP_AGENCY_URL . 'assets/js/division/division-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency'], $this->version, true);
            wp_enqueue_script('division-toast', WP_AGENCY_URL . 'assets/js/division/division-toast.js', ['jquery'], $this->version, true);
            // Update dependencies untuk form
            wp_enqueue_script('create-division-form', WP_AGENCY_URL . 'assets/js/division/create-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable'], $this->version, true);
            wp_enqueue_script('edit-division-form', WP_AGENCY_URL . 'assets/js/division/edit-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable'], $this->version, true);

            // Employee scripts - mengikuti pola division yang sudah berhasil
            wp_enqueue_script('employee-datatable', WP_AGENCY_URL . 'assets/js/employee/employee-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency'], $this->version, true);
            wp_enqueue_script('employee-toast', WP_AGENCY_URL . 'assets/js/employee/employee-toast.js', ['jquery'], $this->version, true);
            wp_enqueue_script('create-employee-form', WP_AGENCY_URL . 'assets/js/employee/create-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
            wp_enqueue_script('edit-employee-form', WP_AGENCY_URL . 'assets/js/employee/edit-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
            wp_enqueue_script(
                'wp-agency-membership',
                WP_AGENCY_URL . 'assets/js/agency/agency-membership.js',
                ['jquery', 'wp-agency', 'wp-agency-toast'],
                WP_AGENCY_VERSION,
                true
            );

            // Gunakan wpAgencyData untuk semua
            $agency_nonce = wp_create_nonce('wp_agency_nonce');
            wp_localize_script('agency', 'wpAgencyData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => $agency_nonce,
                'debug' => true
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
        
        if ($screen->id === 'toplevel_page_wp-agency') {
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
