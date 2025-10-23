<?php
/**
 * Init Hooks Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-init-hooks.php
 *
 * Description: Mendefinisikan semua hooks dan filters yang dibutuhkan
 *              oleh plugin saat inisialisasi. Termasuk URL rewrite,
 *              template, shortcodes, dan AJAX handlers.
 *
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added registration hooks
 * - Added shortcode registration
 */
use WPAgency\Controllers\Auth\AgencyRegistrationHandler;

class WP_Agency_Init_Hooks {

    public function init() {
        // Load text domain - harus di awal sebelum yang lain
        add_action('init', [$this, 'load_textdomain'], 1);
        
        // Query vars
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Templates
        add_action('template_redirect', [$this, 'handle_template_redirect']);
        
        // Shortcodes
        add_action('init', [$this, 'register_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_nopriv_wp_agency_register', [$this, 'handle_registration']);
        add_action('wp_ajax_get_wilayah_id_from_code', [$this, 'handle_get_wilayah_id']);
        add_action('wp_ajax_nopriv_get_wilayah_id_from_code', [$this, 'handle_get_wilayah_id']);
    }

    /**
     * Load plugin textdomain untuk i18n/l10n.
     * Menggunakan konstanta WP_AGENCY_PATH yang sudah didefinisikan di file utama.
     */
    public function load_textdomain() {
        // Gunakan konstanta WP_AGENCY_FILE yang sudah ada untuk mendapatkan path yang benar
        $plugin_rel_path = dirname(plugin_basename(WP_AGENCY_FILE)) . '/languages';
        
        load_plugin_textdomain(
            'wp-agency',
            false,
            $plugin_rel_path
        );
    }
    
    public function register_shortcodes() {
        add_shortcode('agency_register_form', array($this, 'render_register_form'));
    }

    public function render_register_form() {
        if (is_user_logged_in()) {
            return '<p>' . __('Anda sudah login.', 'wp-agency') . '</p>';
        }
        
        ob_start();
        include WP_AGENCY_PATH . 'src/Views/templates/auth/register.php';
        return ob_get_clean();
    }


    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'wp_agency_register';
        return $vars;
    }

    /**
     * Handle template redirects
     */
public function handle_template_redirect() {
    // Ignore favicon requests
    if (strpos($_SERVER['REQUEST_URI'], 'favicon.ico') !== false) {
        return;
    }

    if (get_query_var('wp_agency_register') !== '') {
        error_log('Loading registration template...');
        
        if (is_user_logged_in()) {
            error_log('User is logged in, redirecting...');
            wp_redirect(home_url());
            exit;
        }
        
        error_log('Including template from: ' . WP_AGENCY_PATH . 'src/Views/templates/auth/template-register.php');
        include WP_AGENCY_PATH . 'src/Views/templates/auth/template-register.php';
        exit;
    }
}

    /**
     * Handle registration AJAX
     * Delegate to AgencyRegistrationHandler
     */
    public function handle_registration() {
        $handler = new AgencyRegistrationHandler();
        $handler->handle_registration();
    }

    /**
     * Handle get wilayah ID from code
     * Converts province/regency code to ID
     */
    public function handle_get_wilayah_id() {
        check_ajax_referer('wp_agency_nonce', 'nonce');

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';

        if (empty($type) || empty($code)) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }

        global $wpdb;

        if ($type === 'province') {
            $table = $wpdb->prefix . 'wi_provinces';
            $id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE code = %s",
                $code
            ));
        } elseif ($type === 'regency') {
            $table = $wpdb->prefix . 'wi_regencies';
            $id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE code = %s",
                $code
            ));
        } else {
            wp_send_json_error(['message' => 'Invalid type']);
        }

        if ($id) {
            wp_send_json_success(['id' => $id]);
        } else {
            wp_send_json_error(['message' => 'ID not found']);
        }
    }
}
