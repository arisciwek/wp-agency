<?php
/**
 * Entity Relation Model
 *
 * @package     WP_Agency
 * @subpackage  Models/Relation
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Relation/EntityRelationModel.php
 *
 * Description: Generic model untuk query entity relations.
 *              Handles role-based access control untuk different entity types.
 *              Pattern inspired by wp-customer EntityRelationModel.
 *
 * Entities Supported:
 * - agency: Agency entities
 * - division: Division entities
 * - employee: Employee entities
 *
 * Role Hierarchy (Consistent with wp-customer pattern):
 * - WordPress administrator: Sees ALL entities (bypass filter)
 * - agency_admin_dinas: Filtered by relation (sees assigned agency/division)
 * - agency_admin_unit: Filtered by relation (sees assigned division)
 * - agency_employee: Filtered by relation (sees assigned division)
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 * - Based on wp-customer pattern
 * - Uses wpdt_ prefix for wp-datatable
 */

namespace WPAgency\Models\Relation;

defined('ABSPATH') || exit;

class EntityRelationModel {
    /**
     * Get accessible entity IDs for user
     *
     * Returns:
     * - [] (empty array) = Platform staff (see all, no filtering)
     * - [1, 5, 12] = Limited access (filtered IDs only)
     *
     * @param string $entity_type Entity type (agency, division, employee)
     * @param int|null $user_id User ID (default: current user)
     * @return array Entity IDs accessible to user
     */
    public function get_accessible_entity_ids(string $entity_type, ?int $user_id = null): array {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Not logged in - block all
        if (!$user_id) {
            return [0]; // Will result in WHERE id IN (0) - no results
        }

        // Check if platform staff (WordPress admin)
        // Only WordPress administrators bypass filtering
        if (current_user_can('manage_options')) {
            return []; // Empty = see all, no filtering
        }

        // Get accessible IDs based on entity type
        // All agency roles (admin_dinas, admin_unit, employee) are filtered by relations
        switch ($entity_type) {
            case 'agency':
                return $this->get_accessible_agency_ids($user_id);

            case 'division':
                return $this->get_accessible_division_ids($user_id);

            case 'employee':
                return $this->get_accessible_employee_ids($user_id);

            default:
                return [0]; // Invalid entity type - block all
        }
    }


    /**
     * Get accessible agency IDs for user
     *
     * For all agency roles (admin_dinas, admin_unit, employee):
     * - Get agency from their assigned division (if employee)
     * - Get agency they own (if owner)
     * - Returns agency based on employee relation record OR ownership
     *
     * @param int $user_id User ID
     * @return array Agency IDs
     */
    private function get_accessible_agency_ids(int $user_id): array {
        global $wpdb;

        $agency_ids = [];

        // Check if user is employee
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT agency_id, division_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d AND status = 'active'
            LIMIT 1
        ", $user_id));

        if ($employee && $employee->agency_id) {
            $agency_ids[] = $employee->agency_id;
        }

        // Check if user owns any agencies
        $owned = $wpdb->get_col($wpdb->prepare("
            SELECT id
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d AND status = 'active'
        ", $user_id));

        if ($owned) {
            $agency_ids = array_merge($agency_ids, $owned);
        }

        // Remove duplicates and return
        $agency_ids = array_unique($agency_ids);

        if (empty($agency_ids)) {
            return [0]; // Not an agency employee or owner - block all
        }

        return $agency_ids;
    }

    /**
     * Get accessible division IDs for user
     *
     * For all agency roles (admin_dinas, admin_unit, employee):
     * - Get only their assigned division
     * - Returns division based on employee relation record
     *
     * @param int $user_id User ID
     * @return array Division IDs
     */
    private function get_accessible_division_ids(int $user_id): array {
        global $wpdb;

        // Get employee record
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT division_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d AND status = 'active'
            LIMIT 1
        ", $user_id));

        if (!$employee || !$employee->division_id) {
            return [0]; // Not assigned to division - block all
        }

        return [$employee->division_id];
    }

    /**
     * Get accessible employee IDs for user
     *
     * For all agency roles (admin_dinas, admin_unit, employee):
     * - Get employees in same division
     * - Returns all employees in user's assigned division
     *
     * @param int $user_id User ID
     * @return array Employee IDs
     */
    private function get_accessible_employee_ids(int $user_id): array {
        global $wpdb;

        // Get user's division
        $division_ids = $this->get_accessible_division_ids($user_id);

        if (empty($division_ids) || $division_ids === [0]) {
            return [0]; // No division - block all
        }

        // Get all employees in these divisions
        $placeholders = implode(',', array_fill(0, count($division_ids), '%d'));

        $employee_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE division_id IN ($placeholders)
            AND status = 'active'
        ", ...$division_ids));

        if (empty($employee_ids)) {
            return [0];
        }

        return $employee_ids;
    }

    /**
     * Get accessible agency ID for current user
     *
     * Helper method untuk single agency access
     *
     * @param int|null $user_id User ID
     * @return int|null Agency ID or null
     */
    public function get_user_agency_id(?int $user_id = null): ?int {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $agency_id = $wpdb->get_var($wpdb->prepare("
            SELECT agency_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d AND status = 'active'
            LIMIT 1
        ", $user_id));

        return $agency_id ? (int) $agency_id : null;
    }

    /**
     * Get accessible division ID for current user
     *
     * Helper method untuk single division access
     *
     * @param int|null $user_id User ID
     * @return int|null Division ID or null
     */
    public function get_user_division_id(?int $user_id = null): ?int {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $division_id = $wpdb->get_var($wpdb->prepare("
            SELECT division_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d AND status = 'active'
            LIMIT 1
        ", $user_id));

        return $division_id ? (int) $division_id : null;
    }

    /**
     * Check if user is agency employee
     *
     * @param int|null $user_id User ID
     * @return bool True if agency employee
     */
    public function is_agency_employee(?int $user_id = null): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d AND status = 'active'
        ", $user_id));

        return $count > 0;
    }

    /**
     * Check if user owns any agency
     *
     * @param int|null $user_id User ID
     * @return bool True if user owns agency
     */
    public function is_agency_owner(?int $user_id = null): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d AND status = 'active'
        ", $user_id));

        return $count > 0;
    }
}
