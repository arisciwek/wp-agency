<?php
/**
 * WP Customer Integration for WP Agency
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-wp-customer-integration.php
 *
 * Description: Integration class untuk menghubungkan wp-agency dengan wp-customer.
 *              Menyediakan filter hooks untuk access control berbasis provinsi.
 *              Agency employee hanya bisa melihat customer di provinsi agency mereka.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial version
 * - Implement access_type='agency' filter
 * - Province-based filtering untuk customer dan branch
 */

defined('ABSPATH') || exit;

class WP_Agency_WP_Customer_Integration {

    /**
     * Initialize integration
     */
    public static function init() {
        // Set access type untuk agency users
        add_filter('wp_customer_access_type', [__CLASS__, 'set_agency_access_type'], 10, 2);

        // Set access type untuk branch
        add_filter('wp_branch_access_type', [__CLASS__, 'set_agency_access_type'], 10, 2);
    }

    /**
     * Set access_type='agency' untuk agency employees
     *
     * IMPORTANT: Berbeda dengan platform yang role-based,
     * agency menggunakan employee-based (cek di app_agency_employees table)
     *
     * @param string $access_type Current access type
     * @param array $context Context data (is_admin, user_id, etc)
     * @return string Modified access type
     */
    public static function set_agency_access_type($access_type, $context) {
        // If already has access type, return it
        if ($access_type !== 'none') {
            return $access_type;
        }

        $user_id = $context['user_id'] ?? get_current_user_id();

        // Check if user is agency employee (NOT by role, but by employee table)
        if (self::is_agency_employee($user_id)) {
            return 'agency';
        }

        return $access_type;
    }

    /**
     * Check if user is agency employee (or agency admin)
     *
     * IMPORTANT: Berbeda dengan platform yang cek role,
     * agency cek apakah user ada di:
     * 1. agencies.user_id (agency_admin) ATAU
     * 2. app_agency_employees.user_id (agency staff dengan role apapun)
     *
     * @param int $user_id User ID
     * @return bool
     */
    private static function is_agency_employee($user_id) {
        global $wpdb;

        // Check cache first
        $cache_key = "is_agency_user_{$user_id}";
        $is_agency_user = wp_cache_get($cache_key, 'wp_agency');

        if ($is_agency_user !== false) {
            return (bool) $is_agency_user;
        }

        // Check 1: Is user an agency admin?
        $is_admin = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));

        if ($is_admin > 0) {
            wp_cache_set($cache_key, true, 'wp_agency', 300);
            return true;
        }

        // Check 2: Is user an agency employee?
        $is_employee = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));

        $is_agency_user = $is_employee > 0;

        // Cache for 5 minutes
        wp_cache_set($cache_key, $is_agency_user, 'wp_agency', 300);

        return $is_agency_user;
    }

    /**
     * Get agency ID for agency user (admin or employee)
     *
     * Returns the agency ID where the user works.
     * Supports both:
     * 1. Agency admin (agencies.user_id)
     * 2. Agency employee (app_agency_employees.user_id)
     *
     * @param int $user_id User ID
     * @return int|null Agency ID or null if not found
     */
    public static function get_user_agency_id($user_id) {
        global $wpdb;

        // Check cache first
        $cache_key = "agency_id_{$user_id}";
        $agency_id = wp_cache_get($cache_key, 'wp_agency');

        if ($agency_id !== false) {
            return $agency_id;
        }

        // Check 1: Is user an agency admin?
        $agency_id = $wpdb->get_var($wpdb->prepare("
            SELECT id
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));

        if ($agency_id) {
            wp_cache_set($cache_key, $agency_id, 'wp_agency', 300);
            return $agency_id;
        }

        // Check 2: Is user an agency employee?
        $agency_id = $wpdb->get_var($wpdb->prepare("
            SELECT a.id
            FROM {$wpdb->prefix}app_agency_employees e
            INNER JOIN {$wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
            INNER JOIN {$wpdb->prefix}app_agencies a ON d.agency_id = a.id
            WHERE e.user_id = %d
            LIMIT 1
        ", $user_id));

        // Cache for 5 minutes
        if ($agency_id) {
            wp_cache_set($cache_key, $agency_id, 'wp_agency', 300);
        }

        return $agency_id;
    }

    /**
     * Get province code for agency employee
     *
     * Returns the province code of the agency where the user works.
     * Used for filtering customers by province.
     *
     * @param int $user_id User ID
     * @return string|null Province code or null if not found
     */
    public static function get_user_agency_province($user_id) {
        global $wpdb;

        // Check cache first
        $cache_key = "agency_province_{$user_id}";
        $province_code = wp_cache_get($cache_key, 'wp_agency');

        if ($province_code !== false) {
            return $province_code;
        }

        // Query to get province code from agency employee
        $query = $wpdb->prepare("
            SELECT a.provinsi_code
            FROM {$wpdb->prefix}app_agency_employees e
            INNER JOIN {$wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
            INNER JOIN {$wpdb->prefix}app_agencies a ON d.agency_id = a.id
            WHERE e.user_id = %d
            LIMIT 1
        ", $user_id);

        $province_code = $wpdb->get_var($query);

        // Cache for 5 minutes
        if ($province_code) {
            wp_cache_set($cache_key, $province_code, 'wp_agency', 300);
        }

        return $province_code;
    }

    /**
     * Get user access level based on their agency roles
     *
     * @param int $user_id User ID
     * @return string 'agency'|'division'|'inspector'|'none'
     */
    public static function get_user_access_level($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return 'none';
        }

        // Check for agency-level roles (Province level)
        $agency_level_roles = ['agency_admin', 'agency_kepala_dinas', 'agency_admin_dinas'];
        foreach ($agency_level_roles as $role) {
            if (in_array($role, $user->roles)) {
                return 'agency';
            }
        }

        // Check for division-level roles (Jurisdiction/Kabupaten level)
        $division_level_roles = [
            'agency_admin_unit',
            'agency_kepala_unit',
            'agency_kepala_bidang',
            'agency_kepala_seksi',
            'agency_admin_bidang',
            'agency_admin_seksi'
        ];
        foreach ($division_level_roles as $role) {
            if (in_array($role, $user->roles)) {
                return 'division';
            }
        }

        // Check for inspector-level roles (Per branch level)
        $inspector_level_roles = ['agency_pengawas', 'agency_pengawas_spesialis'];
        foreach ($inspector_level_roles as $role) {
            if (in_array($role, $user->roles)) {
                return 'inspector';
            }
        }

        // Default: no specific access level
        return 'none';
    }

    /**
     * Get division jurisdiction regency IDs for division-level users
     *
     * @param int $user_id User ID
     * @return array Array of regency IDs
     */
    public static function get_user_division_jurisdictions($user_id) {
        global $wpdb;

        // Check cache first
        $cache_key = "division_jurisdictions_{$user_id}";
        $jurisdictions = wp_cache_get($cache_key, 'wp_agency');

        if ($jurisdictions !== false) {
            return $jurisdictions;
        }

        // Get division_id from employee
        $division_id = $wpdb->get_var($wpdb->prepare("
            SELECT division_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));

        if (!$division_id) {
            return [];
        }

        // Get jurisdiction codes for this division
        $jurisdiction_codes = $wpdb->get_col($wpdb->prepare("
            SELECT jurisdiction_code
            FROM {$wpdb->prefix}app_agency_jurisdictions
            WHERE division_id = %d
        ", $division_id));

        if (empty($jurisdiction_codes)) {
            return [];
        }

        // Convert jurisdiction codes to regency IDs
        $placeholders = implode(',', array_fill(0, count($jurisdiction_codes), '%s'));
        $query = "SELECT id FROM {$wpdb->prefix}wi_regencies WHERE code IN ($placeholders)";
        $regency_ids = $wpdb->get_col($wpdb->prepare($query, $jurisdiction_codes));

        // Cache for 5 minutes
        wp_cache_set($cache_key, $regency_ids, 'wp_agency', 300);

        return $regency_ids;
    }

    /**
     * Get inspector ID for inspector-level users
     *
     * @param int $user_id User ID
     * @return int|null Inspector ID (employee ID in agency system)
     */
    public static function get_user_inspector_id($user_id) {
        global $wpdb;

        // Inspector ID is the employee ID in agency_employees table
        return $wpdb->get_var($wpdb->prepare("
            SELECT id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));
    }

    /**
     * Get province ID from province code
     *
     * @param string $province_code Province code (e.g., "32")
     * @return int|null Province ID or null if not found
     */
    public static function get_province_id_from_code($province_code) {
        global $wpdb;

        if (empty($province_code)) {
            return null;
        }

        // Check cache first
        $cache_key = "province_id_{$province_code}";
        $province_id = wp_cache_get($cache_key, 'wp_agency');

        if ($province_id !== false) {
            return $province_id;
        }

        $query = $wpdb->prepare("
            SELECT id
            FROM {$wpdb->prefix}wi_provinces
            WHERE code = %s
            LIMIT 1
        ", $province_code);

        $province_id = $wpdb->get_var($query);

        // Cache for 5 minutes
        if ($province_id) {
            wp_cache_set($cache_key, $province_id, 'wp_agency', 300);
        }

        return $province_id;
    }
}

// Initialize integration
WP_Agency_WP_Customer_Integration::init();
