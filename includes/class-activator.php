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

            // 2. Create agency role first if it doesn't exist
            if (!get_role('agency')) {
                add_role(
                    'agency',
                    __('Agency', 'wp-agency'),
                    [] // Start with empty capabilities
                );
            }

            // 3. Now initialize permission model and add capabilities
            try {
                $permission_model = new PermissionModel();
                $permission_model->addCapabilities(); // This will add caps to both admin and agency roles
            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // 4. Continue with rest of activation (demo data, version, etc)
            self::addVersion();
            self::setupMembershipDefaults();

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

    // Tambahkan metode baru ini
    private static function setupMembershipDefaults() {
        try {
            // Periksa apakah settings sudah ada
            if (!get_option('wp_agency_membership_settings')) {
                $default_settings = [
                    'regular_max_staff' => 2,
                    'regular_can_add_staff' => true,
                    'regular_can_export' => false,
                    'regular_can_bulk_import' => false,
                    
                    'priority_max_staff' => 5,
                    'priority_can_add_staff' => true,
                    'priority_can_export' => true,
                    'priority_can_bulk_import' => false,
                    
                    'utama_max_staff' => -1,
                    'utama_can_add_staff' => true,
                    'utama_can_export' => true,
                    'utama_can_bulk_import' => true,
                    
                    'default_level' => 'regular'
                ];

                add_option('wp_agency_membership_settings', $default_settings);
            }
        } catch (\Exception $e) {
            self::logError('Error setting up membership defaults: ' . $e->getMessage());
        }
    }

    private static function addVersion() {
        add_option('wp_agency_version', WP_AGENCY_VERSION);
    }
}
