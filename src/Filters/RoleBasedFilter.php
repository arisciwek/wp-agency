<?php
/**
 * Role-Based Filter - BRUTAL SIMPLE VERSION
 *
 * @package     WP_Agency
 * @subpackage  Filters
 * @version     2.4.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Filters/RoleBasedFilter.php
 *
 * Description: Simple role-based filtering dengan DIRECT SQL.
 *              Supports 8 agency roles (3 implementations + 5 delegations).
 *              Works untuk SEMUA datatable dari ANY plugin.
 *              Supports cross-plugin filtering (wp-agency ↔ wp-customer).
 *              ASSIGNMENT-BASED: Only shows officially assigned entities.
 *
 * Changelog:
 * 2.4.0 - 2025-12-27
 * - CRITICAL FIX: NewCompanyDataTableModel filter conflict resolved
 * - Added: 'new_company' entity type for jurisdiction-based filtering
 * - Changed: Assignment page (new-companies) now uses JURISDICTION not assignment
 * - agency_admin_dinas: sees unassigned branches in their PROVINCE
 * - agency_admin_unit: sees unassigned branches in their JURISDICTION (regency)
 * - agency_pengawas: NO ACCESS to assignment page (1=0)
 * - Fixed: Empty data bug on new-companies page
 * - Logic: unassigned branches filtered by location, not by agency_id
 *
 * 2.3.0 - 2025-12-27
 * - Added: Inspector-based filtering for agency_pengawas role
 * - Added: filter_agency_pengawas() method (3rd main implementation)
 * - Added: build_where_for_inspector() method
 * - Added: Support for agency_pengawas_spesialis via delegation
 * - Inspector filtering based on inspector_id assignment in customer_branches table
 * - Inspectors see ONLY branches/customers where they are assigned as inspector
 * - Total supported roles: 8 (3 main + 5 delegated)
 * - Access levels: agency → division → inspector (hierarchical)
 *
 * 2.2.0 - 2025-12-27
 * - Added: Support for 4 additional agency roles via delegation pattern:
 *   * agency_kepala_dinas → delegates to admin_dinas (agency-level access)
 *   * agency_kepala_bidang → delegates to admin_dinas (agency-level access)
 *   * agency_kepala_seksi → delegates to admin_dinas (agency-level access)
 *   * agency_kepala_unit → delegates to admin_unit (division-level access)
 * - Total supported roles: 6 (2 main + 4 delegated)
 * - No code duplication: reuses existing filter methods
 * - Delegation pattern: role hierarchy mapped to access levels
 *
 * 2.1.0 - 2025-12-27
 * - BREAKING: Changed from jurisdiction-based to ASSIGNMENT-BASED filtering
 * - Added: Cross-plugin support for wp-customer entities (customer, branch, customer_employee)
 * - Updated: detect_entity() now recognizes customer, branch, customer_employee
 * - Updated: build_where_for_agency_admin() uses agency_id assignments only
 * - Updated: build_where_for_unit_admin() uses division_id assignments only
 * - Fixed: Inconsistency with wp-customer RoleBasedFilter (now both use assignment-based)
 * - agency_admin_dinas: sees ONLY entities assigned to their agency (not all in province)
 * - agency_admin_unit: sees ONLY entities assigned to their division
 * - Security: Prevents viewing unassigned customer data (privacy improvement)
 *
 * 2.0.0 - 2025-12-27
 * - BRUTAL REVISION: Direct SQL, no EntityRelationModel
 * - Simple helper functions
 * - Easy to understand and maintain
 */

namespace WPAgency\Filters;

defined('ABSPATH') || exit;

class RoleBasedFilter {

    public function __construct() {
        // Register filters untuk agency roles (3 main + 5 delegated)

        // Main roles (actual implementation)
        add_filter('wpapp_datatable_where_agency_admin_dinas', [$this, 'filter_agency_admin_dinas'], 10, 3);
        add_filter('wpapp_datatable_where_agency_admin_unit', [$this, 'filter_agency_admin_unit'], 10, 3);
        add_filter('wpapp_datatable_where_agency_pengawas', [$this, 'filter_agency_pengawas'], 10, 3);

        // Delegated roles (same level access)
        // Agency-level access (delegate to admin_dinas filter)
        add_filter('wpapp_datatable_where_agency_kepala_dinas', [$this, 'filter_agency_admin_dinas'], 10, 3);
        add_filter('wpapp_datatable_where_agency_kepala_bidang', [$this, 'filter_agency_admin_dinas'], 10, 3);
        add_filter('wpapp_datatable_where_agency_kepala_seksi', [$this, 'filter_agency_admin_dinas'], 10, 3);

        // Division-level access (delegate to admin_unit filter)
        add_filter('wpapp_datatable_where_agency_kepala_unit', [$this, 'filter_agency_admin_unit'], 10, 3);

        // Inspector-level access (delegate to pengawas filter)
        add_filter('wpapp_datatable_where_agency_pengawas_spesialis', [$this, 'filter_agency_pengawas'], 10, 3);
    }

    /**
     * Filter for agency_admin_dinas - lihat data di agency mereka
     */
    public function filter_agency_admin_dinas($where, $request, $model) {
        global $wpdb;

        // Admin bypass
        if (current_user_can('manage_options')) {
            return $where;
        }

        $user_id = get_current_user_id();

        // Get agency_id dari user (DIRECT SQL)
        // Try as employee first
        $agency_id = $wpdb->get_var($wpdb->prepare(
            "SELECT agency_id FROM {$wpdb->prefix}app_agency_employees
             WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        // If not employee, try as agency owner
        if (!$agency_id) {
            $agency_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agencies
                 WHERE user_id = %d LIMIT 1",
                $user_id
            ));
        }

        if (!$agency_id) {
            return $where; // No agency - no filter
        }

        // Detect entity dari model class
        $entity = $this->detect_entity($model);

        error_log('=== WPAgency RoleBasedFilter::filter_agency_admin_dinas ===');
        error_log('User ID: ' . $user_id);
        error_log('Agency ID: ' . $agency_id);
        error_log('Entity: ' . $entity);
        error_log('Model: ' . get_class($model));

        // Build WHERE condition
        $condition = $this->build_where_for_agency_admin($entity, $agency_id);

        if ($condition) {
            $where[] = $condition;
            error_log('Added WHERE: ' . $condition);
        } else {
            error_log('No WHERE condition built');
        }

        error_log('Final WHERE array: ' . print_r($where, true));
        error_log('=== END WPAgency RoleBasedFilter ===');

        return $where;
    }

    /**
     * Filter for agency_admin_unit - lihat data di division mereka
     */
    public function filter_agency_admin_unit($where, $request, $model) {
        global $wpdb;

        if (current_user_can('manage_options')) {
            return $where;
        }

        $user_id = get_current_user_id();

        // Get division_id dari user (DIRECT SQL)
        $division_id = $wpdb->get_var($wpdb->prepare(
            "SELECT division_id FROM {$wpdb->prefix}app_agency_employees
             WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        if (!$division_id) {
            return $where;
        }

        // Detect entity
        $entity = $this->detect_entity($model);

        error_log('=== WPAgency RoleBasedFilter::filter_agency_admin_unit ===');
        error_log('User ID: ' . $user_id);
        error_log('Division ID: ' . $division_id);
        error_log('Entity: ' . $entity);
        error_log('Model: ' . get_class($model));

        // Build WHERE condition
        $condition = $this->build_where_for_unit_admin($entity, $division_id);

        if ($condition) {
            $where[] = $condition;
            error_log('Added WHERE: ' . $condition);
        } else {
            error_log('No WHERE condition built');
        }

        error_log('Final WHERE array: ' . print_r($where, true));
        error_log('=== END WPAgency RoleBasedFilter ===');

        return $where;
    }

    /**
     * Filter for agency_pengawas - lihat data sesuai inspector assignment
     */
    public function filter_agency_pengawas($where, $request, $model) {
        global $wpdb;

        if (current_user_can('manage_options')) {
            return $where;
        }

        $user_id = get_current_user_id();

        // Get employee_id dari user (as inspector)
        $employee_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_agency_employees
             WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        if (!$employee_id) {
            return $where;
        }

        // Detect entity
        $entity = $this->detect_entity($model);

        error_log('=== WPAgency RoleBasedFilter::filter_agency_pengawas ===');
        error_log('User ID: ' . $user_id);
        error_log('Employee ID (Inspector): ' . $employee_id);
        error_log('Entity: ' . $entity);
        error_log('Model: ' . get_class($model));

        // Build WHERE condition
        $condition = $this->build_where_for_inspector($entity, $employee_id);

        if ($condition) {
            $where[] = $condition;
            error_log('Added WHERE: ' . $condition);
        } else {
            error_log('No WHERE condition built');
        }

        error_log('Final WHERE array: ' . print_r($where, true));
        error_log('=== END WPAgency RoleBasedFilter ===');

        return $where;
    }

    /**
     * Detect entity dari model class name
     */
    private function detect_entity($model) {
        $class = get_class($model);

        // wp-agency entities
        if (strpos($class, 'AgencyDataTableModel') !== false) {
            return 'agency';
        }
        if (strpos($class, 'DivisionDataTableModel') !== false) {
            return 'division';
        }
        if (strpos($class, 'EmployeeDataTableModel') !== false) {
            // Check namespace to differentiate agency vs customer employees
            if (strpos($class, 'WPAgency') !== false) {
                return 'agency_employee';
            }
            return 'customer_employee';
        }

        // wp-customer entities (cross-plugin)
        // NewCompanyDataTableModel - special case for assignment page (unassigned branches)
        if (strpos($class, 'NewCompanyDataTableModel') !== false) {
            return 'new_company'; // Special entity for jurisdiction-based filtering
        }
        // CompanyDataTableModel uses 'cc' alias (companies view in wp-customer)
        if (strpos($class, 'CompanyDataTableModel') !== false) {
            return 'company';
        }
        if (strpos($class, 'BranchDataTableModel') !== false) {
            return 'branch';
        }
        if (strpos($class, 'CustomerDataTableModel') !== false) {
            return 'customer';
        }

        return 'unknown';
    }

    /**
     * Build WHERE condition untuk agency_admin_dinas
     *
     * ASSIGNMENT-BASED: Only show entities that are officially assigned to this agency
     */
    private function build_where_for_agency_admin($entity, $agency_id) {
        global $wpdb;

        switch ($entity) {
            case 'agency':
                return "a.id = {$agency_id}";

            case 'division':
                return "d.agency_id = {$agency_id}";

            case 'agency_employee':
                return "e.agency_id = {$agency_id}";

            // Cross-plugin entities (wp-customer) - ASSIGNMENT-BASED
            case 'company':
                // Only companies (branches) assigned to this agency
                return "cc.agency_id = {$agency_id}";

            case 'branch':
                // Only branches assigned to this agency
                return "cb.agency_id = {$agency_id}";

            case 'new_company':
                // Special case: NEW companies (unassigned branches) on assignment page
                // Filter by JURISDICTION (province) instead of assignment
                // Agency only sees unassigned branches in their province
                $province_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT province_id FROM {$wpdb->prefix}app_agencies
                     WHERE id = %d LIMIT 1",
                    $agency_id
                ));
                return $province_id ? "cb.province_id = {$province_id}" : null;

            case 'customer':
                // Only customers that have branches assigned to this agency
                return "c.id IN (
                    SELECT DISTINCT customer_id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE agency_id = {$agency_id}
                )";

            case 'customer_employee':
                // Only employees from branches assigned to this agency
                return "ce.branch_id IN (
                    SELECT id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE agency_id = {$agency_id}
                )";

            default:
                return null;
        }
    }

    /**
     * Build WHERE condition untuk agency_admin_unit
     *
     * ASSIGNMENT-BASED: Only show entities that are officially assigned to this division
     */
    private function build_where_for_unit_admin($entity, $division_id) {
        global $wpdb;

        switch ($entity) {
            case 'agency':
                // Get agency from division
                $agency_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT agency_id FROM {$wpdb->prefix}app_agency_divisions
                     WHERE id = %d LIMIT 1",
                    $division_id
                ));
                return $agency_id ? "a.id = {$agency_id}" : null;

            case 'division':
                // Unit admin only sees their division
                return "d.id = {$division_id}";

            case 'agency_employee':
                // Unit admin sees employees in their division
                return "e.division_id = {$division_id}";

            // Cross-plugin entities (wp-customer) - ASSIGNMENT-BASED
            case 'company':
                // Only companies (branches) assigned to this division
                return "cc.division_id = {$division_id}";

            case 'branch':
                // Only branches assigned to this division
                return "cb.division_id = {$division_id}";

            case 'new_company':
                // Special case: NEW companies (unassigned branches) on assignment page
                // Filter by JURISDICTION (regency) for division/unit level
                // Unit admin only sees unassigned branches in their jurisdiction
                $jurisdiction_regencies = $wpdb->get_col($wpdb->prepare(
                    "SELECT jurisdiction_regency_id
                     FROM {$wpdb->prefix}app_agency_jurisdictions
                     WHERE division_id = %d",
                    $division_id
                ));

                if (empty($jurisdiction_regencies)) {
                    return "1=0"; // No jurisdiction = no access
                }

                $placeholders = implode(',', array_fill(0, count($jurisdiction_regencies), '%d'));
                return $wpdb->prepare("cb.regency_id IN ($placeholders)", ...$jurisdiction_regencies);

            case 'customer':
                // Only customers that have branches assigned to this division
                return "c.id IN (
                    SELECT DISTINCT customer_id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE division_id = {$division_id}
                )";

            case 'customer_employee':
                // Only employees from branches assigned to this division
                return "ce.branch_id IN (
                    SELECT id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE division_id = {$division_id}
                )";

            default:
                return null;
        }
    }

    /**
     * Build WHERE condition untuk agency_pengawas (inspector)
     *
     * ASSIGNMENT-BASED: Only show entities where inspector is assigned
     */
    private function build_where_for_inspector($entity, $employee_id) {
        global $wpdb;

        switch ($entity) {
            // wp-agency entities - no filtering (inspectors don't manage internal agency data)
            case 'agency':
            case 'division':
            case 'agency_employee':
                return null;

            // NEW companies assignment page - inspectors have NO access (only dinas/unit can assign)
            case 'new_company':
                return "1=0"; // Inspectors cannot access new company assignment page

            // Cross-plugin entities (wp-customer) - INSPECTOR ASSIGNMENT
            case 'company':
                // Only companies (branches) where this user is assigned as inspector
                return "cc.inspector_id = {$employee_id}";

            case 'branch':
                // Only branches where this user is assigned as inspector
                return "cb.inspector_id = {$employee_id}";

            case 'customer':
                // Only customers that have branches assigned to this inspector
                return "c.id IN (
                    SELECT DISTINCT customer_id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE inspector_id = {$employee_id}
                )";

            case 'customer_employee':
                // Only employees from branches assigned to this inspector
                return "ce.branch_id IN (
                    SELECT id
                    FROM {$wpdb->prefix}app_customer_branches
                    WHERE inspector_id = {$employee_id}
                )";

            default:
                return null;
        }
    }
}
