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

            case 'company':
                return $this->get_accessible_company_ids($user_id);

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

    /**
     * Get accessible company IDs for agency user
     *
     * Returns company (branch) IDs based on role:
     * - agency_admin_unit: Companies in jurisdiction regencies
     * - Other roles (admin_dinas, etc): Companies in same province
     *
     * @param int $user_id User ID
     * @return array Company IDs
     */
    private function get_accessible_company_ids(int $user_id): array {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [0];
        }

        $user_roles = (array) $user->roles;

        // Check if user is agency_admin_unit - filter by jurisdiction regencies
        if (in_array('agency_admin_unit', $user_roles)) {
            return $this->get_companies_by_jurisdiction($user_id);
        }

        // Other agency roles (admin_dinas, kepala_dinas, etc) - filter by province
        return $this->get_companies_by_province($user_id);
    }

    /**
     * Get companies by jurisdiction (for agency_admin_unit)
     *
     * Returns company IDs in regencies that are within user's division jurisdiction.
     *
     * Logic:
     * 1. Get user's division_id
     * 2. Get regency_ids from jurisdictions for that division
     * 3. Return companies in those regencies
     *
     * @param int $user_id User ID
     * @return array Company IDs
     */
    private function get_companies_by_jurisdiction(int $user_id): array {
        global $wpdb;

        // Get user's division_id
        $division_id = $this->get_user_division_id($user_id);

        if (!$division_id) {
            return [0]; // No division = no access
        }

        // Get jurisdiction regency IDs for this division
        $jurisdiction_table = $wpdb->prefix . 'app_agency_jurisdictions';

        $regency_ids = $wpdb->get_col($wpdb->prepare("
            SELECT jurisdiction_regency_id
            FROM {$jurisdiction_table}
            WHERE division_id = %d
        ", $division_id));

        if (empty($regency_ids)) {
            return [0]; // No jurisdictions defined = no access
        }

        // Get companies in these regencies
        $branches_table = $wpdb->prefix . 'app_customer_branches';
        $placeholders = implode(',', array_fill(0, count($regency_ids), '%d'));

        $company_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT id
            FROM {$branches_table}
            WHERE regency_id IN ($placeholders)
            AND status = 'active'
        ", ...$regency_ids));

        if (empty($company_ids)) {
            return [0];
        }

        return $company_ids;
    }

    /**
     * Get companies by province (for agency_admin_dinas and other roles)
     *
     * Returns company IDs in the same province as user's agency.
     *
     * @param int $user_id User ID
     * @return array Company IDs
     */
    private function get_companies_by_province(int $user_id): array {
        global $wpdb;

        // Get user's agency province
        $agency_id = $this->get_user_agency_id($user_id);

        if (!$agency_id) {
            return [0]; // No agency = no access
        }

        // Get province_id from agency
        $province_id = $wpdb->get_var($wpdb->prepare("
            SELECT province_id
            FROM {$wpdb->prefix}app_agencies
            WHERE id = %d
            LIMIT 1
        ", $agency_id));

        if (!$province_id) {
            return [0]; // No province = no access
        }

        // Get all companies (branches) in this province
        $branches_table = $wpdb->prefix . 'app_customer_branches';

        $company_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT id
            FROM {$branches_table}
            WHERE province_id = %d
            AND status = 'active'
        ", $province_id));

        if (empty($company_ids)) {
            return [0];
        }

        return $company_ids;
    }
}
