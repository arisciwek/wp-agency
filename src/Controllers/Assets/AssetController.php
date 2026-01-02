<?php
/**
 * Asset Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Assets
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Assets/AssetController.php
 *
 * Description: Mengelola asset loading untuk plugin wp-agency.
 *              Menggantikan class-dependencies.php dengan pattern
 *              yang lebih modular menggunakan Singleton pattern.
 *              Inspired by wp-customer AssetController.
 *
 * Changelog:
 * 2.0.0 - 2025-12-29
 * - Initial creation - migrated from class-dependencies.php
 * - Singleton pattern implementation
 * - Modular methods per screen
 * - Consistent with wp-customer pattern
 */

namespace WPAgency\Controllers\Assets;

class AssetController {
    /**
     * Singleton instance
     *
     * @var AssetController|null
     */
    private static $instance = null;

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Get singleton instance
     *
     * @return AssetController
     */
    public static function get_instance(): AssetController {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->plugin_name = 'wp-agency';
        $this->version = defined('WP_AGENCY_VERSION') ? WP_AGENCY_VERSION : '1.0.0';

        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Initialize AssetController
     * Called from main plugin file
     *
     * @return void
     */
    public function init(): void {
        // Hook for extensions to register additional assets
        do_action('wpa_register_assets', $this);
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Ignore admin and ajax requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Registration page
        if (get_query_var('wp_agency_register')) {
            $this->enqueue_registration_assets();
        }
    }

    /**
     * Enqueue admin assets (styles and scripts)
     *
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Agency Dashboard (main page)
        if (in_array($screen->id, ['toplevel_page_wp-agency', 'toplevel_page_wp-agency-disnaker'])) {
            $this->enqueue_agency_dashboard_assets();
        }

        // Settings page
        if ($screen->id === 'wp-agency_page_wp-agency-settings') {
            $this->enqueue_settings_assets();
        }
    }

    // ==========================================
    // FRONTEND ASSETS
    // ==========================================

    /**
     * Enqueue registration page assets
     *
     * @return void
     */
    private function enqueue_registration_assets(): void {
        // Register page specific style
        wp_enqueue_style(
            'wp-agency-register',
            WP_AGENCY_URL . 'assets/css/auth/register.css',
            [],
            $this->version
        );

        // Form styles
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

        // Wilayah Indonesia plugin scripts
        if (defined('WILAYAH_INDONESIA_URL')) {
            $wilayah_plugin_url = WILAYAH_INDONESIA_URL;

            wp_enqueue_script(
                'wilayah-select-handler-core',
                $wilayah_plugin_url . 'assets/js/components/select-handler-core.js',
                ['jquery'],
                '1.1.0',
                true
            );

            wp_enqueue_script(
                'wilayah-select-handler-ui',
                $wilayah_plugin_url . 'assets/js/components/select-handler-ui.js',
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
        }

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

        // Localize script
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
    }

    // ==========================================
    // ADMIN ASSETS
    // ==========================================

    /**
     * Enqueue agency dashboard assets
     *
     * @return void
     */
    private function enqueue_agency_dashboard_assets(): void {
        // ==========================================
        // STYLES
        // ==========================================

        // Core styles
        wp_enqueue_style('wp-agency-toast', WP_AGENCY_URL . 'assets/css/agency/toast.css', [], $this->version);
        wp_enqueue_style('wp-agency-modal', WP_AGENCY_URL . 'assets/css/agency/confirmation-modal.css', [], $this->version);
        wp_enqueue_style('wp-agency-forms', WP_AGENCY_URL . 'assets/css/agency/agency-forms.css', [], $this->version);

        // Division styles
        wp_enqueue_style('division-toast', WP_AGENCY_URL . 'assets/css/division/division-toast.css', [], $this->version);
        wp_enqueue_style('wp-agency-division', WP_AGENCY_URL . 'assets/css/division/division-style.css', [], $this->version);

        // Employee styles
        wp_enqueue_style('wp-agency-employee', WP_AGENCY_URL . 'assets/css/employee/employee-style.css', [], $this->version);
        wp_enqueue_style('employee-toast', WP_AGENCY_URL . 'assets/css/employee/employee-toast.css', [], $this->version);

        // New Company styles
        wp_enqueue_style('wp-agency-new-company', WP_AGENCY_URL . 'assets/css/company/new-company-style.css', [], $this->version);

        // Audit Log styles
        wp_enqueue_style('wp-agency-audit-log', WP_AGENCY_URL . 'assets/css/audit-log/audit-log.css', [], $this->version);

        // DataTables
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

        // Agency specific styles
        wp_enqueue_style('wp-agency-agency', WP_AGENCY_URL . 'assets/css/agency/agency-style.css', [], $this->version);
        wp_enqueue_style('wp-agency-detail', WP_AGENCY_URL . 'assets/css/agency/agency-detail.css', [], $this->version);

        // Select2 for jurisdiction selects
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');

        // Leaflet for maps
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');

        // ==========================================
        // SCRIPTS
        // ==========================================

        // Core dependencies
        wp_enqueue_script('jquery-validate', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js', ['jquery'], '1.19.5', true);
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);
        wp_enqueue_script('jquery-inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', ['jquery'], null, true);
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        // Leaflet & WPApp Map Components (from wp-app-core)
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        // WPApp Core Map Picker (shared component)
        wp_enqueue_script(
            'wpapp-map-picker',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-picker.js',
            ['jquery', 'leaflet'],
            '1.0.0',
            true
        );

        // WPApp Core Map Adapter (handles WPModal integration)
        wp_enqueue_script(
            'wpapp-map-adapter',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-adapter.js',
            ['jquery', 'leaflet', 'wpapp-map-picker'],
            '1.0.0',
            true
        );

        // Localize map settings for MapPicker
        wp_localize_script('wpapp-map-picker', 'wpAgencyMapSettings', [
            'defaultLat' => get_option('wp_agency_settings')['map_default_lat'] ?? -6.200000,
            'defaultLng' => get_option('wp_agency_settings')['map_default_lng'] ?? 106.816666,
            'defaultZoom' => get_option('wp_agency_settings')['map_default_zoom'] ?? 12,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);

        // Components
        wp_enqueue_script('wp-agency-toast', WP_AGENCY_URL . 'assets/js/agency/agency-toast.js', ['jquery'], $this->version, true);
        wp_enqueue_script('confirmation-modal', WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js', ['jquery'], $this->version, true);
        wp_enqueue_script('division-toast', WP_AGENCY_URL . 'assets/js/division/division-toast.js', ['jquery'], $this->version, true);

        // Wilayah handler
        $this->enqueue_wilayah_handler();

        // Agency scripts
        wp_enqueue_script('agency-datatable', WP_AGENCY_URL . 'assets/js/agency/agency-datatable.js', ['jquery', 'datatables'], '2.0.0', true);

        // Localize agency-datatable
        wp_localize_script('agency-datatable', 'wpAgencyDataTable', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdt_nonce'),
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

        // Agency filter
        wp_enqueue_script('agency-filter', WP_AGENCY_URL . 'assets/js/agency/agency-filter.js', ['jquery', 'datatables'], $this->version, true);

        // DEPRECATED: Commented out - now using auto-wire modal system from wp-datatable
        // wp_enqueue_script('agency-modal-handler', WP_AGENCY_URL . 'assets/js/agency-modal-handler.js', ['jquery', 'wp-modal'], $this->version, true);
        // wp_localize_script('agency-modal-handler', 'wpAgencyConfig', [
        //     'ajaxUrl' => admin_url('admin-ajax.php'),
        //     'nonce' => wp_create_nonce('wp_agency_nonce'),
        //     'i18n' => [
        //         'loading' => __('Loading...', 'wp-agency'),
        //         'error' => __('Error', 'wp-agency'),
        //         'success' => __('Success', 'wp-agency'),
        //         'confirm' => __('Are you sure?', 'wp-agency')
        //     ]
        // ]);

        // Forms
        wp_enqueue_script('create-agency-form', WP_AGENCY_URL . 'assets/js/agency/create-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);
        wp_enqueue_script('edit-agency-form', WP_AGENCY_URL . 'assets/js/agency/edit-agency-form.js', ['jquery', 'jquery-validate', 'wp-agency-toast'], $this->version, true);

        // Division scripts
        wp_enqueue_script('division-datatable', WP_AGENCY_URL . 'assets/js/division/division-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency-datatable'], $this->version, true);
        wp_enqueue_script('create-division-form', WP_AGENCY_URL . 'assets/js/division/create-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable'], $this->version, true);
        wp_enqueue_script('edit-division-form', WP_AGENCY_URL . 'assets/js/division/edit-division-form.js', ['jquery', 'jquery-validate', 'division-toast', 'division-datatable'], $this->version, true);

        // Localize division data
        wp_localize_script('division-datatable', 'wpAgencyData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'perPage' => 10
        ]);

        // Employee scripts
        wp_enqueue_script('employee-datatable', WP_AGENCY_URL . 'assets/js/employee/employee-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency-datatable'], $this->version, true);
        wp_enqueue_script('employee-toast', WP_AGENCY_URL . 'assets/js/employee/employee-toast.js', ['jquery'], $this->version, true);
        wp_enqueue_script('create-employee-form', WP_AGENCY_URL . 'assets/js/employee/create-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);
        wp_enqueue_script('edit-employee-form', WP_AGENCY_URL . 'assets/js/employee/edit-employee-form.js', ['jquery', 'jquery-validate', 'employee-toast', 'employee-datatable'], $this->version, true);

        // Localize employee data
        wp_localize_script('employee-datatable', 'wpAgencyData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'perPage' => 10
        ]);

        // New Company scripts
        wp_enqueue_script('new-company-datatable', WP_AGENCY_URL . 'assets/js/company/new-company-datatable.js', ['jquery', 'datatables', 'wp-agency-toast', 'agency-datatable'], $this->version, true);

        // Audit Log scripts (cache busting for recent fixes)
        $audit_log_version = $this->version . '.' . filemtime(WP_AGENCY_PATH . 'assets/js/audit-log/audit-log.js');
        wp_enqueue_script('audit-log', WP_AGENCY_URL . 'assets/js/audit-log/audit-log.js', ['jquery', 'datatables'], $audit_log_version, true);

        // Localize audit log
        wp_localize_script('audit-log', 'wpAgencyAuditLog', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_agency_nonce'),
            'i18n' => [
                'processing' => __('Loading...', 'wp-agency'),
                'search' => __('Search:', 'wp-agency'),
                'lengthMenu' => __('Show _MENU_ entries', 'wp-agency'),
                'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-agency'),
                'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'wp-agency'),
                'infoFiltered' => __('(filtered from _MAX_ total entries)', 'wp-agency'),
                'zeroRecords' => __('No matching records found', 'wp-agency'),
                'emptyTable' => __('No data available in table', 'wp-agency'),
                'field' => __('Field', 'wp-agency'),
                'oldValue' => __('Old Value', 'wp-agency'),
                'newValue' => __('New Value', 'wp-agency'),
                'detailTitle' => __('Audit Log Details', 'wp-agency'),
                'close' => __('Close', 'wp-agency'),
                'modalLibraryNotLoaded' => __('Modal library not loaded', 'wp-agency'),
                'paginate' => [
                    'first' => __('First', 'wp-agency'),
                    'previous' => __('Previous', 'wp-agency'),
                    'next' => __('Next', 'wp-agency'),
                    'last' => __('Last', 'wp-agency')
                ]
            ]
        ]);
    }

    /**
     * Enqueue settings page assets
     *
     * @return void
     */
    private function enqueue_settings_assets(): void {
        // Main settings styles
        wp_enqueue_style(
            'wp-agency-settings',
            WP_AGENCY_URL . 'assets/css/settings/settings-style.css',
            [],
            $this->version
        );

        wp_enqueue_style(
            'wp-agency-modal',
            WP_AGENCY_URL . 'assets/css/agency/confirmation-modal.css',
            [],
            $this->version
        );

        // Common scripts for settings page
        wp_enqueue_script(
            'wp-agency-toast',
            WP_AGENCY_URL . 'assets/js/agency/agency-toast.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_script(
            'confirmation-modal',
            WP_AGENCY_URL . 'assets/js/agency/confirmation-modal.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_script(
            'wp-agency-settings',
            WP_AGENCY_URL . 'assets/js/settings/settings-script.js',
            ['jquery', 'wp-agency-toast'],
            $this->version,
            true
        );

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Enqueue tab-specific assets
        $this->enqueue_settings_tab_styles($current_tab);
        $this->enqueue_settings_tab_scripts($current_tab);
    }

    /**
     * Enqueue settings tab-specific styles
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_styles(string $current_tab): void {
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

    /**
     * Enqueue settings tab-specific scripts
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_scripts(string $current_tab): void {
        switch ($current_tab) {
            case 'permissions':
                wp_enqueue_script(
                    'wp-agency-permissions-tab',
                    WP_AGENCY_URL . 'assets/js/settings/agency-permissions-tab-script.js',
                    ['jquery', 'wp-agency-settings'],
                    $this->version,
                    true
                );

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

    /**
     * Enqueue wilayah handler scripts
     *
     * @return void
     */
    private function enqueue_wilayah_handler(): void {
        if (!defined('WILAYAH_INDONESIA_URL')) {
            return;
        }

        if (wp_script_is('wilayah-select-handler-core', 'enqueued')) {
            return;
        }

        wp_enqueue_script(
            'wilayah-select-handler-core',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-core.js',
            ['jquery'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

        wp_enqueue_script(
            'wilayah-select-handler-ui',
            WILAYAH_INDONESIA_URL . 'assets/js/components/select-handler-ui.js',
            ['jquery', 'wilayah-select-handler-core'],
            defined('WILAYAH_INDONESIA_VERSION') ? WILAYAH_INDONESIA_VERSION : '1.0.0',
            true
        );

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

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
