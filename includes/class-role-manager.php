<?php
/**
 * Role Manager Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-role-manager.php
 *
 * Description: Centralized role management for WP Agency plugin.
 *              Single source of truth untuk role definitions.
 *              Accessible untuk plugin lain dan internal components.
 *
 * Usage:
 * - Get all roles: WP_Agency_Role_Manager::getRoles()
 * - Get role slugs: WP_Agency_Role_Manager::getRoleSlugs()
 * - Check if role exists: WP_Agency_Role_Manager::roleExists($slug)
 *
 * Changelog:
 * 1.0.0 - 2025-01-14
 * - Initial creation
 * - Moved role definitions from WP_Agency_Activator
 * - Made accessible for all components
 */

defined('ABSPATH') || exit;

class WP_Agency_Role_Manager {
    /**
     * Get all available roles with their display names
     * Single source of truth for roles in the plugin
     *
     * @return array Array of role_slug => role_name pairs
     */
    public static function getRoles(): array {
        return [
            'agency' => __('Disnaker', 'wp-agency'),
            'agency_employee' => __('Staff Disnaker', 'wp-agency'),
            'agency_admin_dinas' => __('Admin Dinas', 'wp-agency'),
            'agency_admin_unit' => __('Admin Unit', 'wp-agency'),
            'agency_pengawas' => __('Pengawas', 'wp-agency'),
            'agency_pengawas_spesialis' => __('Pengawas Spesialis', 'wp-agency'),
            'agency_kepala_unit' => __('Kepala Unit', 'wp-agency'),
            'agency_kepala_seksi' => __('Kepala Seksi', 'wp-agency'),
            'agency_kepala_bidang' => __('Kepala Bidang', 'wp-agency'),
            'agency_kepala_dinas' => __('Kepala Dinas', 'wp-agency')
        ];
    }

    /**
     * Get only role slugs
     *
     * @return array Array of role slugs
     */
    public static function getRoleSlugs(): array {
        return array_keys(self::getRoles());
    }

    /**
     * Check if a role is managed by this plugin
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role is managed by this plugin
     */
    public static function isPluginRole(string $role_slug): bool {
        return array_key_exists($role_slug, self::getRoles());
    }

    /**
     * Check if a WordPress role exists
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role exists in WordPress
     */
    public static function roleExists(string $role_slug): bool {
        return get_role($role_slug) !== null;
    }

    /**
     * Get display name for a role
     *
     * @param string $role_slug Role slug
     * @return string|null Role display name or null if not found
     */
    public static function getRoleName(string $role_slug): ?string {
        $roles = self::getRoles();
        return $roles[$role_slug] ?? null;
    }
}
