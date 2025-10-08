<?php
/**
 * File: class-activator.php
 * Path: /wp-agency/includes/class-activator.php
 * Description: Handles plugin activation and database installation
 * 
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Description: Menangani proses aktivasi plugin dan instalasi database.
 *              Termasuk di dalamnya:
 *              - Instalasi tabel database melalui Database\Installer
 *              - Menambahkan versi plugin ke options table
 *              - Setup permission dan capabilities
 * 
 * Dependencies:
 * - WPAgency\Database\Installer untuk instalasi database
 * - WPAgency\Models\Settings\PermissionModel untuk setup capabilities
 * - WordPress Options API
 * 
 * Changelog:
 * 1.0.1 - 2024-01-07
 * - Refactored database installation to use Database\Installer
 * - Enhanced error handling
 * - Added dependency management
 * 
 * 1.0.0 - 2024-11-23
 * - Initial creation
 * - Added activation handling
 * - Added version management
 * - Added permissions setup
 */
use WPAgency\Models\Settings\PermissionModel;
use WPAgency\Database\Installer;

class WP_Agency_Activator {
    private static function logError($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_Agency_Activator Error: {$message}");
        }
    }

    public static function activate() {
        try {
            // 1. Run database installation first
            $installer = new Installer();
            if (!$installer->run()) {
                self::logError('Failed to install database tables');
                return;
            }

            // 2. Run database migration for wilayah code changes
            if (!$installer->runMigration()) {
                self::logError('Failed to run database migration');
                return;
            }

            // 3. Create roles if they don't exist
            $all_roles = self::getRoles();
            // Exclude 'administrator' as it's a WordPress default role
            $roles_to_create = array_diff_key($all_roles, ['administrator' => '']);

            foreach ($roles_to_create as $role_slug => $role_name) {
                if (!get_role($role_slug)) {
                    add_role(
                        $role_slug,
                        $role_name,
                        [] // Start with empty capabilities
                    );
                }
            }

            // 4. Now initialize permission model and add capabilities
            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities(); // This will add caps to both admin and agency roles
            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // 5. Continue with rest of activation (demo data, version, etc)
            self::addVersion();

            // Add rewrite rules
            add_rewrite_rule(
                'agency-register/?$',
                'index.php?wp_agency_register=1',
                'top'
            );

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }



    private static function addVersion() {
        add_option('wp_agency_version', WP_AGENCY_VERSION);
    }

    /**
     * Get all available roles with their display names
     * Single source of truth for roles in the plugin
     */
    public static function getRoles(): array {
        return [
            'administrator' => __('Administrator', 'wp-agency'),
            'agency' => __('Disnaker', 'wp-agency'),
            'admin_dinas' => __('Admin Dinas', 'wp-agency'),
            'admin_unit' => __('Admin Unit', 'wp-agency'),
            'pengawas' => __('Pengawas', 'wp-agency'),
            'pengawas_spesialis' => __('Pengawas Spesialis', 'wp-agency'),
            'kepala_unit' => __('Kepala Unit', 'wp-agency'),
            'kepala_seksi' => __('Kepala Seksi', 'wp-agency'),
            'kepala_bidang' => __('Kepala Bidang', 'wp-agency'),
            'kepala_dinas' => __('Kepala Dinas', 'wp-agency')
        ];
    }
}
