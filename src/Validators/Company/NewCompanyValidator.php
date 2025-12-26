<?php
/**
 * New Company Validator Class
 *
 * @package     WP_Agency
 * @subpackage  Validators/Company
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Validators/Company/NewCompanyValidator.php
 *
 * Description: Validator untuk permission checks dan validasi data
 *              untuk company (branch) yang belum memiliki inspector.
 *              Includes capability checks, agency access validation,
 *              dan inspector assignment validation.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 * - Added permission checks
 * - Added inspector validation
 * - Added agency access validation
 */

namespace WPAgency\Validators\Company;

use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Company\NewCompanyModel;

class NewCompanyValidator {
    private AgencyModel $agencyModel;
    private NewCompanyModel $companyModel;

    public function __construct() {
        $this->agencyModel = new AgencyModel();
        $this->companyModel = new NewCompanyModel();
    }

    /**
     * Check if user can view new companies list
     * 
     * @param int $agency_id Agency ID
     * @return bool
     */
    public function canViewNewCompanies(int $agency_id): bool {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user_id = get_current_user_id();

        // Super admin can view all
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check agency-specific permissions
        if (current_user_can('view_agency')) {
            return true;
        }

        // Check if user is agency owner
        $agency = $this->agencyModel->find($agency_id);
        if ($agency && $agency->user_id == $current_user_id) {
            return true;
        }

        // Check if user is agency employee
        global $wpdb;
        $employees_table = $wpdb->prefix . 'app_agency_employees';
        
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$employees_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $current_user_id, $agency_id
        ));

        if ($is_employee > 0) {
            return true;
        }

        // Check if user is division admin in this agency
        $divisions_table = $wpdb->prefix . 'app_agency_divisions';
        
        $is_division_admin = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$divisions_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $current_user_id, $agency_id
        ));

        return $is_division_admin > 0;
    }

    /**
     * Check if user can assign inspector
     * 
     * @param int $agency_id Agency ID
     * @return bool
     */
    public function canAssignInspector(int $agency_id): bool {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user_id = get_current_user_id();

        // Super admin can assign
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check specific capability - NEW: use assign_inspector_to_branch
        if (current_user_can('assign_inspector_to_branch')) {
            return true;
        }

        // Check if user is agency owner
        $agency = $this->agencyModel->find($agency_id);
        if ($agency && $agency->user_id == $current_user_id) {
            return true;
        }

        // Check if user is agency employee with proper role
        global $wpdb;
        $employees_table = $wpdb->prefix . 'app_agency_employees';
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$employees_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $current_user_id, $agency_id
        ));

        if ($employee) {
            // Check if employee has supervisor or manager role
            $user = get_userdata($current_user_id);
            if ($user) {
                $roles = $user->roles;
                $allowed_roles = ['administrator', 'agency_manager', 'agency_supervisor'];
                
                foreach ($allowed_roles as $role) {
                    if (in_array($role, $roles)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Validate inspector assignment
     * 
     * @param int $branch_id Branch ID
     * @param int $inspector_id Inspector user ID
     * @return array Validation result with 'valid' and 'message' keys
     */
    public function validateInspectorAssignment(int $branch_id, int $inspector_id): array {
        global $wpdb;

        // Check if branch exists
        $branch = $this->companyModel->getBranchById($branch_id);
        if (!$branch) {
            return [
                'valid' => false,
                'message' => __('Branch not found', 'wp-agency')
            ];
        }

        // Check if branch already has inspector
        if ($branch->inspector_id) {
            return [
                'valid' => false,
                'message' => __('Branch already has an inspector assigned', 'wp-agency')
            ];
        }

        // Check if inspector user exists
        $inspector = get_userdata($inspector_id);
        if (!$inspector) {
            return [
                'valid' => false,
                'message' => __('Inspector user not found', 'wp-agency')
            ];
        }

        // Check if inspector is an agency employee
        $employees_table = $wpdb->prefix . 'app_agency_employees';
        
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$employees_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $inspector_id, $branch->agency_id
        ));

        if ($is_employee == 0) {
            return [
                'valid' => false,
                'message' => __('Selected user is not an employee of this agency', 'wp-agency')
            ];
        }

        // Check if inspector has proper capabilities
        if (!user_can($inspector_id, 'inspect_companies') &&
            !user_can($inspector_id, 'manage_options')) {

            // Check if user has inspector role - 'agency' (Disnaker) and 'pengawas' roles are considered inspectors
            $roles = $inspector->roles;
            $inspector_roles = ['agency', 'pengawas'];

            $has_inspector_role = false;
            foreach ($inspector_roles as $role) {
                if (in_array($role, $roles)) {
                    $has_inspector_role = true;
                    break;
                }
            }

            if (!$has_inspector_role) {
                return [
                    'valid' => false,
                    'message' => __('Selected user does not have inspector privileges', 'wp-agency')
                ];
            }
        }

        // Optional: Check if inspector already has too many assignments
        $existing_assignments = $this->companyModel->getInspectorAssignments($inspector_id);
        $max_assignments = apply_filters('wp_agency_max_inspector_assignments', 50);
        
        if (count($existing_assignments) >= $max_assignments) {
            return [
                'valid' => false,
                'message' => sprintf(
                    __('Inspector already has %d assignments (maximum: %d)', 'wp-agency'),
                    count($existing_assignments),
                    $max_assignments
                )
            ];
        }

        return [
            'valid' => true,
            'message' => __('Inspector can be assigned', 'wp-agency')
        ];
    }

    /**
     * Validate agency access
     * 
     * @param int $agency_id Agency ID
     * @return array Access information
     */
    public function validateAccess(int $agency_id): array {
        $current_user_id = get_current_user_id();
        
        // Default access array
        $access = [
            'has_access' => false,
            'access_type' => 'none',
            'can_view' => false,
            'can_assign' => false
        ];

        // Not logged in
        if (!$current_user_id) {
            return $access;
        }

        // Super admin has full access
        if (current_user_can('manage_options')) {
            return [
                'has_access' => true,
                'access_type' => 'admin',
                'can_view' => true,
                'can_assign' => true
            ];
        }

        // Check agency owner
        $agency = $this->agencyModel->find($agency_id);
        if ($agency && $agency->user_id == $current_user_id) {
            return [
                'has_access' => true,
                'access_type' => 'owner',
                'can_view' => true,
                'can_assign' => true
            ];
        }

        // Check agency employee
        global $wpdb;
        $employees_table = $wpdb->prefix . 'app_agency_employees';
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$employees_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $current_user_id, $agency_id
        ));

        if ($employee) {
            $user = get_userdata($current_user_id);
            $can_assign = false;
            
            if ($user) {
                $roles = $user->roles;
                $assign_roles = ['agency_manager', 'agency_supervisor'];
                
                foreach ($assign_roles as $role) {
                    if (in_array($role, $roles)) {
                        $can_assign = true;
                        break;
                    }
                }
            }
            
            return [
                'has_access' => true,
                'access_type' => 'employee',
                'can_view' => true,
                'can_assign' => $can_assign
            ];
        }

        // Check division admin
        $divisions_table = $wpdb->prefix . 'app_agency_divisions';
        
        $is_division_admin = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$divisions_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $current_user_id, $agency_id
        ));

        if ($is_division_admin > 0) {
            return [
                'has_access' => true,
                'access_type' => 'division_admin',
                'can_view' => true,
                'can_assign' => false
            ];
        }

        return $access;
    }

    /**
     * Get user relation with agency
     * 
     * @param int $agency_id Agency ID
     * @param int|null $user_id User ID (current user if null)
     * @return array Relationship information
     */
    public function getUserRelation(int $agency_id, ?int $user_id = null): array {
        $user_id = $user_id ?? get_current_user_id();
        
        $relation = [
            'is_admin' => false,
            'is_owner' => false,
            'is_employee' => false,
            'is_division_admin' => false,
            'agency_id' => $agency_id
        ];

        if (!$user_id) {
            return $relation;
        }

        // Check admin
        if (user_can($user_id, 'manage_options')) {
            $relation['is_admin'] = true;
        }

        // Check owner
        $agency = $this->agencyModel->find($agency_id);
        if ($agency && $agency->user_id == $user_id) {
            $relation['is_owner'] = true;
        }

        global $wpdb;
        
        // Check employee
        $employees_table = $wpdb->prefix . 'app_agency_employees';
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$employees_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $user_id, $agency_id
        ));
        
        if ($is_employee > 0) {
            $relation['is_employee'] = true;
        }

        // Check division admin
        $divisions_table = $wpdb->prefix . 'app_agency_divisions';
        $is_division_admin = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$divisions_table} 
             WHERE user_id = %d AND agency_id = %d AND status = 'active'",
            $user_id, $agency_id
        ));
        
        if ($is_division_admin > 0) {
            $relation['is_division_admin'] = true;
        }

        return $relation;
    }
}
