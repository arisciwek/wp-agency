<?php
/**
 * WP Agency - Company Filters
 *
 * @package     WP_Agency
 * @subpackage  Filters
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Filters/AgencyCompanyFilters.php
 *
 * Description: Menambahkan filter untuk company access control
 *              berdasarkan agency, division, dan inspector.
 *
 * Changelog:
 * 1.0.0 - 2024-02-14
 * - Initial release
 * - Added agency_owner check
 * - Added division_admin check
 * - Added inspector check
 */

namespace WPAgency\Filters;

class CompanyFilters {
    
    public function __construct() {
        // Hook ke filter WP Customer
        add_filter('wp_customer_can_access_company_page', [$this, 'checkAgencyAccessCompanyPage'], 10, 2);
        add_filter('wp_customer_branch_user_relation', [$this, 'addAgencyBranchUserRelations'], 10, 3);
        add_filter('wp_customer_can_view_branch', [$this, 'checkAgencyBranchView'], 10, 2);
    }

    /**
     * Check if user has agency access to company page
     */
    public function checkAgencyAccessCompanyPage($has_access, $user_id) {
        // Jika sudah ada akses, return true
        if ($has_access) {
            return true;
        }

        global $wpdb;

        // Check if user is agency owner
        $agency_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agencies WHERE user_id = %d",
            $user_id
        ));

        if ($agency_count > 0) {
            return true;
        }

        // Check if user is agency employee
        $employee_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees 
             WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        if ($employee_count > 0) {
            return true;
        }

        return false;
    }

    /**
     * Add agency-specific relations
     */
    public function addAgencyBranchUserRelations($relation, $branch_id, $user_id) {
        global $wpdb;

        $branch_data = $relation['branch_data'] ?? null;
        if (!$branch_data) {
            return $relation;
        }

        // Check agency owner (provinsi_id sama)
        $is_agency_owner = false;
        if (!empty($branch_data->provinsi_id)) {
            // Get province code from branch
            $province_code = $wpdb->get_var($wpdb->prepare(
                "SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = %d",
                $branch_data->provinsi_id
            ));

            if ($province_code) {
                $agency_owner_check = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}app_agencies 
                     WHERE user_id = %d AND provinsi_code = %s",
                    $user_id,
                    $province_code
                ));
                $is_agency_owner = ($agency_owner_check > 0);
            }
        }

        // Check division admin (regency_id sama)
        $is_division_admin = false;
        if (!empty($branch_data->regency_id)) {
            // Check via jurisdiction using jurisdiction_regency_id directly
            $division_admin_check = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT d.id)
                 FROM {$wpdb->prefix}app_agency_divisions d
                 INNER JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id
                 WHERE d.user_id = %d AND j.jurisdiction_regency_id = %d",
                $user_id,
                $branch_data->regency_id
            ));
            $is_division_admin = ($division_admin_check > 0);
        }

        // Check inspector (inspector_id sama dengan user_id)
        $is_inspector = false;
        if (!empty($branch_data->inspector_id)) {
            $is_inspector = ($branch_data->inspector_id == $user_id);
        }

        // Add to relation array
        $relation['is_agency_owner'] = $is_agency_owner;
        $relation['is_division_admin'] = $is_division_admin;
        $relation['is_inspector'] = $is_inspector;

        // Update access_type jika ada agency relation
        if ($relation['access_type'] === 'none') {
            if ($is_agency_owner) {
                $relation['access_type'] = 'agency_owner';
            } elseif ($is_division_admin) {
                $relation['access_type'] = 'division_admin';
            } elseif ($is_inspector) {
                $relation['access_type'] = 'inspector';
            }
        }

        return $relation;
    }

    /**
     * Check if user can view based on agency relations
     */
    public function checkAgencyBranchView($can_view, $relation) {
        // Jika sudah bisa view, return true
        if ($can_view) {
            return true;
        }

        // Agency owner can view companies in their province
        if (!empty($relation['is_agency_owner']) && $relation['is_agency_owner']) {
            return true;
        }

        // Division admin can view companies in their jurisdiction
        if (!empty($relation['is_division_admin']) && $relation['is_division_admin']) {
            return true;
        }

        // Inspector can view companies assigned to them
        if (!empty($relation['is_inspector']) && $relation['is_inspector']) {
            return true;
        }

        return false;
    }
}
