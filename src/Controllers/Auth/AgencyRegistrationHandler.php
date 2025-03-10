<?php
/**
 * Agency Registration Handler
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Auth
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Auth/AgencyRegistrationHandler.php
 *
 * Description: Handler untuk memproses registrasi agency baru.
 *              Menangani pembuatan user WordPress dan data agency,
 *              termasuk validasi field unik (username, email, NIB, NPWP).
 *              Mengimplementasikan rollback jika terjadi error.
 *
 * Dependencies:
 * - WordPress user functions
 * - wp_agency_settings
 * - Agencys table
 *
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added agency registration handler
 * - Added unique field validation
 * - Added agency code generator
 * - Added rollback functionality
 */

namespace WPAgency\Controllers\Auth;

defined('ABSPATH') || exit;

class AgencyRegistrationHandler {
    
    public function handle_registration() {
        check_ajax_referer('wp_agency_register', 'register_nonce');
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $name = sanitize_text_field($_POST['name']);
        $nib = sanitize_text_field($_POST['nib']);
        $npwp = $this->format_npwp(sanitize_text_field($_POST['npwp']));

        // Validasi dasar
        if (empty($username) || empty($email) || empty($password) || 
            empty($name) || empty($nib) || empty($npwp)) {
            wp_send_json_error([
                'message' => __('Semua field wajib diisi.', 'wp-agency')
            ]);
        }

        // Validasi format NPWP
        if (!$this->validate_npwp($npwp)) {
            wp_send_json_error([
                'message' => __('Format NPWP tidak valid.', 'wp-agency')
            ]);
        }

        // Cek username dan email
        if (username_exists($username)) {
            wp_send_json_error([
                'message' => __('Username sudah digunakan.', 'wp-agency')
            ]);
        }

        if (email_exists($email)) {
            wp_send_json_error([
                'message' => __('Email sudah terdaftar.', 'wp-agency')
            ]);
        }

        // Cek NIB dan NPWP di database
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agencies';
        
        $existing_nib = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE nib = %s", $nib)
        );
        
        if ($existing_nib) {
            wp_send_json_error([
                'message' => __('NIB sudah terdaftar.', 'wp-agency')
            ]);
        }

        $existing_npwp = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE npwp = %s", $npwp)
        );
        
        if ($existing_npwp) {
            wp_send_json_error([
                'message' => __('NPWP sudah terdaftar.', 'wp-agency')
            ]);
        }

        // Buat user WordPress
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error([
                'message' => $user_id->get_error_message()
            ]);
        }

        // Tambahkan role agency
        $user = new \WP_User($user_id);
        $user->set_role('agency');

        // Generate kode agency
        $code = $this->generate_agency_code();

        // Insert data agency
        $agency_data = [
            'code' => $code,
            'name' => $name,
            'nib' => $nib,
            'npwp' => $npwp,
            'user_id' => $user_id,
            'created_by' => $user_id
        ];

        $inserted = $wpdb->insert($table_name, $agency_data);

        if ($inserted === false) {
            // Log error detail
            error_log('WP Agency Insert Error: ' . $wpdb->last_error);
            error_log('Agency Data: ' . print_r($agency_data, true));

            // Rollback - hapus user jika insert agency gagal
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            
            wp_send_json_error([
                'message' => __('Gagal membuat akun agency.', 'wp-agency')
            ]);
        }

        wp_send_json_success([
            'message' => __('Registrasi berhasil! Silakan login.', 'wp-agency'),
            'redirect' => wp_login_url()
        ]);
    }

    private function format_npwp($npwp) {
        // Remove non-digits
        $numbers = preg_replace('/\D/', '', $npwp);
        
        // Format to XX.XXX.XXX.X-XXX.XXX
        if (strlen($numbers) === 15) {
            return substr($numbers, 0, 2) . '.' .
                   substr($numbers, 2, 3) . '.' .
                   substr($numbers, 5, 3) . '.' .
                   substr($numbers, 8, 1) . '-' .
                   substr($numbers, 9, 3) . '.' .
                   substr($numbers, 12, 3);
        }
        
        return $npwp;
    }

    private function validate_npwp($npwp) {
        // Check if NPWP matches the format: XX.XXX.XXX.X-XXX.XXX
        return (bool) preg_match('/^\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}$/', $npwp);
    }

    private function generate_agency_code() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agencies';
        
        // Ambil kode terakhir
        $last_code = $wpdb->get_var("SELECT code FROM $table_name ORDER BY id DESC LIMIT 1");
        
        if (!$last_code) {
            return '01';
        }

        // Generate kode baru
        $next_number = intval($last_code) + 1;
        return str_pad($next_number, 2, '0', STR_PAD_LEFT);
    }
}
