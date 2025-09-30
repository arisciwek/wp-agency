<?php
/**
 * Plugin Deactivator Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.1.0
 * @author      arisciwek
 *
 * path : /wp-agency/includes/class-deactivator.php
 * 
 * Description: Menangani proses deaktivasi plugin:
 *              - Database cleanup (hanya dalam mode development)
 *              - Cache cleanup 
 *              - Settings cleanup
 */

use WPAgency\Cache\AgencyCacheManager;

class WP_Agency_Deactivator {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WP_Agency_Deactivator] {$message}");
        }
    }

    private static function should_clear_data() {
        $dev_settings = get_option('wp_agency_development_settings');
        if (isset($dev_settings['clear_data_on_deactivate']) && 
            $dev_settings['clear_data_on_deactivate']) {
            return true;
        }
        return defined('WP_AGENCY_DEVELOPMENT') && WP_AGENCY_DEVELOPMENT;
    }

    public static function deactivate() {
        global $wpdb;

        $should_clear_data = self::should_clear_data();

        // Hapus development settings setelah menentukan apakah perlu clear data
        delete_option('wp_agency_development_settings');
        self::debug("Development settings cleared");

        try {
            // Only proceed with data cleanup if in development mode
            if (!$should_clear_data) {
                self::debug("Skipping data cleanup on plugin deactivation");
                return;
            }

            // Add this new method call at the start
            self::remove_capabilities();

            // Start transaction
            $wpdb->query('START TRANSACTION');

            // First, drop all foreign key constraints to avoid dependency issues
            self::drop_foreign_key_constraints();

            // Delete tables in correct order (child tables first)
            $tables = [
                // First level - no dependencies
                'app_agency_memberships',  // Drop this first as it references agencies, divisions, and levels
                'app_agency_employees',    // Drop this next as it references agencies and divisions
                'app_agency_jurisdictions', // Drop this before divisions as it references divisions
                'app_jurisdictions',        // Old table name, drop if exists
                'app_divisions',             // Drop this after jurisdictions as it only references agencies
                // Second level - referenced by others
                'app_agency_membership_levels',  // Can now be dropped as memberships is gone
                'app_agency_membership_features',  // Drop features before groups as it references groups
                'app_agency_membership_feature_groups',  // Drop groups last in this level
                'app_agencies'             // Drop this last as it's referenced by all
            ];

            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . $table;
                self::debug("Attempting to drop table: {$table_name}");
                $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
            }

            // Delete demo users (after tables are gone)
            self::delete_demo_users();

            // Hapus semua opsi terkait membership
            self::cleanupMembershipOptions();

            // Clear cache using AgencyCacheManager
            try {
                $cache_manager = new AgencyCacheManager();
                $cleared = $cache_manager->clearAll();
                self::debug("Cache clearing result: " . ($cleared ? 'success' : 'failed'));
            } catch (\Exception $e) {
                self::debug("Error clearing cache: " . $e->getMessage());
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            
            self::debug("Plugin deactivation complete");

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug("Error during deactivation: " . $e->getMessage());
        }
    }

    private static function remove_capabilities() {
        try {
            $permission_model = new \WPAgency\Models\Settings\PermissionModel();
            $capabilities = array_keys($permission_model->getAllCapabilities());

            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }

            $roles_to_remove = [
                'agency',
                'admin_dinas',
                'admin_unit',
                'pengawas',
                'pengawas_spesialis',
                'kepala_unit',
                'kepala_seksi',
                'kepala_bidang',
                'kepala_dinas'
            ];

            foreach ($roles_to_remove as $role_slug) {
                remove_role($role_slug);
            }
            self::debug("Capabilities and roles removed successfully");
        } catch (\Exception $e) {
            self::debug("Error removing capabilities: " . $e->getMessage());
        }
    }

    private static function delete_demo_users() {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            $demo_users = $wpdb->get_col("
                SELECT DISTINCT u.ID 
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE u.ID != 1
                AND (
                    (um.meta_key = 'wp_agency_demo_user' AND um.meta_value = '1')
                    OR EXISTS (
                        SELECT 1 
                        FROM {$wpdb->usermeta} um2 
                        WHERE um2.user_id = u.ID 
                        AND um2.meta_key = 'wp_capabilities'
                        AND um2.meta_value LIKE '%agency%'
                    )
                )
            ");
            
            if (!empty($demo_users)) {
                $user_ids = implode(',', array_map('intval', $demo_users));
                
                if (in_array(1, explode(',', $user_ids))) {
                    throw new \Exception("Attempted to delete admin user - operation aborted");
                }
                
                $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE user_id IN ($user_ids) AND user_id != 1");
                $wpdb->query("DELETE FROM {$wpdb->comments} WHERE user_id IN ($user_ids) AND user_id != 1");
                $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_author IN ($user_ids) AND post_author != 1");
                $wpdb->query("DELETE FROM {$wpdb->users} WHERE ID IN ($user_ids) AND ID != 1");
                
                self::debug("Successfully deleted " . count($demo_users) . " demo users");
                
                if (defined('WP_AGENCY_DEVELOPMENT') && WP_AGENCY_DEVELOPMENT) {
                    $wpdb->query("ALTER TABLE {$wpdb->users} AUTO_INCREMENT = 2");
                    self::debug("Reset users table AUTO_INCREMENT to 2");
                }
            }
            
            $wpdb->query('COMMIT');
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug("Error managing users: " . $e->getMessage());
        }
    }

    private static function drop_foreign_key_constraints() {
        global $wpdb;

        try {
            // Drop specific foreign key constraints
            $constraint_queries = [
                // Handle both old and new jurisdiction table names
                "ALTER TABLE {$wpdb->prefix}app_agency_jurisdictions DROP FOREIGN KEY {$wpdb->prefix}app_agency_jurisdictions_ibfk_1",
                "ALTER TABLE {$wpdb->prefix}app_jurisdictions DROP FOREIGN KEY {$wpdb->prefix}app_jurisdictions_ibfk_1",
                "ALTER TABLE {$wpdb->prefix}app_agency_memberships DROP FOREIGN KEY fk_membership_agency",
                "ALTER TABLE {$wpdb->prefix}app_agency_memberships DROP FOREIGN KEY fk_membership_division",
                "ALTER TABLE {$wpdb->prefix}app_agency_memberships DROP FOREIGN KEY fk_agency_membership_level",
                "ALTER TABLE {$wpdb->prefix}app_agency_employees DROP FOREIGN KEY {$wpdb->prefix}app_agency_employees_ibfk_1",
                "ALTER TABLE {$wpdb->prefix}app_agency_employees DROP FOREIGN KEY {$wpdb->prefix}app_agency_employees_ibfk_2",
                "ALTER TABLE {$wpdb->prefix}app_divisions DROP FOREIGN KEY {$wpdb->prefix}app_divisions_ibfk_1",
                "ALTER TABLE {$wpdb->prefix}app_agency_membership_features DROP FOREIGN KEY fk_agency_feature_group_id",
            ];

            foreach ($constraint_queries as $query) {
                // Try to drop the constraint, ignore errors if it doesn't exist
                $wpdb->query($query);
            }

            self::debug("Foreign key constraints dropped");
        } catch (\Exception $e) {
            self::debug("Error dropping foreign key constraints: " . $e->getMessage());
        }
    }

    private static function cleanupMembershipOptions() {
        try {
            delete_option('wp_agency_membership_settings');
            delete_transient('wp_agency_membership_cache');
            self::debug("Membership settings and transients cleared");
        } catch (\Exception $e) {
            self::debug("Error cleaning up membership options: " . $e->getMessage());
        }
    }
}
