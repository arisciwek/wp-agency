<?php
/**
 * WordPress User Generator for Demo Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/WPUserGenerator.php
 *
 * Changelog:
 * 2.0.0 - 2025-01-22 (Task-2067 Runtime Flow)
 * - BREAKING: Changed user creation to use wp_insert_user() instead of direct DB INSERT
 * - Uses WordPress hooks and validation for proper integration
 * - Updates ID to static value after creation (FOREIGN_KEY_CHECKS=0)
 * - Added comprehensive debug logging
 * - Added deleteUsers() method for cleanup
 * - Follows wp-customer pattern exactly
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\AgencyUsersData;
use WPAgency\Database\Demo\Data\DivisionUsersData;

defined('ABSPATH') || exit;

class WPUserGenerator {
    use AgencyDemoDataHelperTrait;

    private static $usedUsernames = [];
    
    // Reference the data from separate files
    public static $agency_users;
    public static $division_users;

    public function __construct() {
        // Initialize the static properties from the data files
        self::$agency_users = AgencyUsersData::$data;
        self::$division_users = DivisionUsersData::$data;
    }

    protected function validate(): bool {
        if (!current_user_can('create_users')) {
            $this->debug('Current user cannot create users');
            return false;
        }
        return true;
    }

    public function generateUser($data) {
        global $wpdb;

        if (!$this->validate()) {
            throw new \Exception('Validation failed: cannot create users');
        }

        $this->debug("=== generateUser called ===");
        $this->debug("Input data: " . json_encode($data));

        // 1. Check if user with this ID already exists using DIRECT DATABASE QUERY
        $existing_user_row = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, user_login, display_name FROM {$wpdb->users} WHERE ID = %d",
            $data['id']
        ));

        $this->debug("Checking existing user with ID {$data['id']}: " . ($existing_user_row ? 'EXISTS' : 'NOT FOUND'));

        if ($existing_user_row) {
            $this->debug("User exists - Display Name: {$existing_user_row->display_name}");

            // Update display name if different
            if ($existing_user_row->display_name !== $data['display_name']) {
                wp_update_user([
                    'ID' => $data['id'],
                    'display_name' => $data['display_name']
                ]);
                $this->debug("Updated user display name: {$data['display_name']}");
            }

            // Update roles if provided as array
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->updateUserRoles($data['id'], $data['roles']);
            }

            $this->debug("Returning existing user ID: {$data['id']}");
            return $data['id'];
        }

        // 2. Use username from data or generate new one
        $username = isset($data['username'])
            ? $data['username']
            : $this->generateUniqueUsername($data['display_name']);

        $this->debug("Username to use: {$username}");

        // Check if username already exists with DIFFERENT ID
        $username_exists = username_exists($username);
        $this->debug("Username '{$username}' exists check: " . ($username_exists ? "YES (ID: {$username_exists})" : 'NO'));

        if ($username_exists && $username_exists != $data['id']) {
            $this->debug("WARNING: Username '{$username}' exists with ID {$username_exists}, need ID {$data['id']}");
            $this->debug("Deleting existing user ID {$username_exists} to re-create with static ID {$data['id']}");

            // Delete existing user to make room for static ID
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            $delete_result = wp_delete_user($username_exists);

            if ($delete_result) {
                $this->debug("Successfully deleted user ID {$username_exists}");
            } else {
                throw new \Exception("Cannot delete existing user '{$username}' (ID: {$username_exists})");
            }
        }

        // 3. Insert new user via wp_insert_user() for proper WordPress integration
        $user_data_to_insert = [
            'user_login' => $username,
            'user_pass' => 'Demo_Data-2025',  // Will be hashed by wp_insert_user()
            'user_email' => $username . '@example.com',
            'display_name' => $data['display_name'],
            'role' => 'agency'  // Base role (first role only)
        ];

        $this->debug("Creating user via wp_insert_user()");

        // Create user with wp_insert_user() (auto-generates ID)
        $user_id = wp_insert_user($user_data_to_insert);

        if (is_wp_error($user_id)) {
            $error_message = $user_id->get_error_message();
            $this->debug("ERROR: wp_insert_user failed: {$error_message}");
            throw new \Exception($error_message);
        }

        $this->debug("User created successfully with auto ID: {$user_id}");

        // 4. Add additional roles (agency pattern: base + admin role)
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                foreach ($data['roles'] as $role) {
                    if ($role !== 'agency') {  // Skip base role already set
                        $user->add_role($role);
                        $this->debug("Added role: {$role}");
                    }
                }
            }
        }

        // 5. Now update the ID to match static ID from data
        $this->debug("Updating user ID from {$user_id} to static ID: {$data['id']}");

        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

        // Update wp_users ID
        $result_users = $wpdb->update(
            $wpdb->users,
            ['ID' => $data['id']],
            ['ID' => $user_id],
            ['%d'],
            ['%d']
        );

        // Update wp_usermeta user_id references
        $result_meta = $wpdb->update(
            $wpdb->usermeta,
            ['user_id' => $data['id']],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );

        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        $this->debug("ID update results - users: {$result_users}, usermeta: {$result_meta}");

        if ($result_users === false || $result_meta === false) {
            $this->debug("ERROR: Failed to update user ID - " . $wpdb->last_error);
            // Delete the created user
            wp_delete_user($user_id);
            throw new \Exception("Failed to set static user ID: " . $wpdb->last_error);
        }

        // Clear WordPress user caches for both old and new IDs
        $old_user_id = $user_id;
        $new_user_id = $data['id'];

        clean_user_cache($old_user_id);  // Clear cache for old auto ID
        clean_user_cache($new_user_id);  // Clear cache for new static ID

        // Also clear the email-to-ID mapping cache (if exists)
        wp_cache_delete($data['username'], 'userlogins');
        wp_cache_delete($data['username'], 'userslugs');
        if (isset($data['email'])) {
            wp_cache_delete(md5($data['email']), 'useremail');
        }

        $this->debug("Cleared WordPress user caches for IDs {$old_user_id} and {$new_user_id}");

        $user_id = $data['id'];
        $this->debug("User ID successfully changed to static ID: {$user_id}");

        // Add demo user meta
        update_user_meta($user_id, 'wp_agency_demo_user', '1');
        $this->debug("Added demo user meta");

        $roles_string = isset($data['roles']) ? implode(', ', $data['roles']) : 'agency';
        $this->debug("=== User creation completed ===");
        $this->debug("Created user: {$data['display_name']} with ID: {$user_id} and roles: {$roles_string}");

        return $user_id;
    }

    /**
     * Update user roles (for existing users)
     */
    private function updateUserRoles(int $user_id, array $roles): void {
        global $wpdb;

        // Build capabilities array with all roles
        $capabilities = [];
        foreach ($roles as $role) {
            $capabilities[$role] = true;
        }

        // Update capabilities in usermeta
        $wpdb->update(
            $wpdb->usermeta,
            ['meta_value' => serialize($capabilities)],
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'capabilities'
            ],
            ['%s'],
            ['%d', '%s']
        );

        $roles_string = implode(', ', $roles);
        $this->debug("Updated roles for user {$user_id}: {$roles_string}");
    }

    private function generateUniqueUsername($display_name) {
        $base_username = strtolower(str_replace(' ', '_', $display_name));
        $username = $base_username;
        $suffix = 1;

        while (in_array($username, self::$usedUsernames) || username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        self::$usedUsernames[] = $username;
        return $username;
    }

    /**
     * Delete demo users by IDs
     *
     * @param array $user_ids Array of user IDs to delete
     * @param bool $force_delete Force delete without demo user check (for development)
     * @return int Number of users deleted
     */
    public function deleteUsers(array $user_ids, bool $force_delete = false): int {
        if (empty($user_ids)) {
            return 0;
        }

        $this->debug("=== Deleting demo users ===");
        $this->debug("User IDs to delete: " . json_encode($user_ids));
        $this->debug("Force delete mode: " . ($force_delete ? 'YES' : 'NO'));

        $deleted_count = 0;

        foreach ($user_ids as $user_id) {
            // Check if user exists
            $existing_user = get_user_by('ID', $user_id);

            if (!$existing_user) {
                $this->debug("User ID {$user_id} not found, skipping");
                continue;
            }

            // Skip ID 1 (main admin) for safety
            if ($user_id == 1) {
                $this->debug("User ID 1 is main admin, skipping for safety");
                continue;
            }

            // Check if this is a demo user (unless force delete)
            if (!$force_delete) {
                $is_demo = get_user_meta($user_id, 'wp_agency_demo_user', true);

                if ($is_demo !== '1') {
                    $this->debug("User ID {$user_id} is not a demo user, skipping for safety");
                    continue;
                }
            } else {
                $this->debug("Force deleting user ID {$user_id} ({$existing_user->user_login})");
            }

            // Use WordPress function to delete user (deletes meta automatically)
            require_once(ABSPATH . 'wp-admin/includes/user.php');

            $result = wp_delete_user($user_id);

            if ($result) {
                $deleted_count++;
                $this->debug("Deleted user ID {$user_id} ({$existing_user->user_login})");
            } else {
                $this->debug("Failed to delete user ID {$user_id}");
            }
        }

        $this->debug("Deleted {$deleted_count} users");

        return $deleted_count;
    }

    private function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WPUserGenerator] ' . $message);
        }
    }
}
