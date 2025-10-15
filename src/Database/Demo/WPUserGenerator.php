<?php
/**
 * WordPress User Generator for Demo Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/WPUserGenerator.php
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

        // 1. Check if user with this ID already exists using direct DB query
        $existing_user_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d", $data['id']));
        if ($existing_user_id) {
            // Get user object for display name and email check
            $existing_user = get_user_by('ID', $data['id']);
            $expected_email = $data['username'] . '@example.com';
            $updates = [];
            if ($existing_user && $existing_user->display_name !== $data['display_name']) {
                $updates['display_name'] = $data['display_name'];
            }
            if ($existing_user && $existing_user->user_email !== $expected_email) {
                $updates['user_email'] = $expected_email;
            }
            if (!empty($updates)) {
                $updates['ID'] = $data['id'];
                wp_update_user($updates);
                $this->debug("Updated user data for ID: {$data['id']}");
            }

            // Update roles if provided as array
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->updateUserRoles($existing_user_id, $data['roles']);
            }

            return $existing_user_id;
        }

        // 2. Use username from data or generate new one
        $username = isset($data['username'])
            ? $data['username']
            : $this->generateUniqueUsername($data['display_name']);

        // 3. Insert new user into database
        $result = $wpdb->insert(
            $wpdb->users,
            [
                'ID' => $data['id'],
                'user_login' => $username,
                'user_pass' => wp_hash_password('Demo_Data-2025'),
                'user_email' => $username . '@example.com',
                'display_name' => $data['display_name'],
                'user_registered' => current_time('mysql')
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($result === false) {
            throw new \Exception($wpdb->last_error);
        }

        $user_id = $data['id'];

        // Insert user meta directly
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => 'wp_agency_demo_user',
                'meta_value' => '1'
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        // Handle roles - support both single role (string) and multiple roles (array)
        $roles = [];
        if (isset($data['roles']) && is_array($data['roles'])) {
            // Multiple roles provided as array
            $roles = $data['roles'];
        } elseif (isset($data['role'])) {
            // Single role provided as string (backward compatibility)
            $roles = [$data['role']];
        } else {
            // Default role if none provided
            $roles = ['agency'];
        }

        // Build capabilities array with all roles
        $capabilities = [];
        foreach ($roles as $role) {
            $capabilities[$role] = true;
        }

        // Add role capability
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'capabilities',
                'meta_value' => serialize($capabilities)
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        // Update user level for backward compatibility
        $wpdb->insert(
            $wpdb->usermeta,
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'user_level',
                'meta_value' => '0'
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );

        $roles_string = implode(', ', $roles);
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

    private function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WPUserGenerator] ' . $message);
        }
    }
}
