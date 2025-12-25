<?php
/**
 * Agency Role Filter
 *
 * @package     WP_Agency
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Integrations/AgencyRoleFilter.php
 *
 * Description: Specific filtering untuk Agency DataTable.
 *              Filters agencies based on user role and assignment.
 *              Pattern inspired by wp-customer CustomerRoleFilter.
 *
 * Hook: wpdt_datatable_agencies_where
 *
 * Role-Based Access:
 * - All agency roles (admin_dinas, admin_unit, employee): Filtered by relations
 * - Only WordPress administrator bypasses filtering
 * - Consistent with wp-customer pattern
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 * - Based on wp-customer pattern
 * - Uses wpdt_ prefix for wp-datatable
 */

namespace WPAgency\Integrations;

use WPAgency\Models\Relation\EntityRelationModel;

defined('ABSPATH') || exit;

class AgencyRoleFilter {
    /**
     * @var EntityRelationModel
     */
    private $relation_model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->relation_model = new EntityRelationModel();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        add_filter(
            'wpapp_datatable_agencies_where',
            [$this, 'filter_agencies_by_role'],
            10,
            3
        );
    }

    /**
     * Filter agencies by user role
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_agencies_by_role($where_conditions, $request_data, $model): array {
        $user_id = get_current_user_id();

        // Not logged in - block all
        if (!$user_id) {
            $where_conditions[] = '1=0';
            return $where_conditions;
        }

        // WordPress admin - no filter
        if (current_user_can('manage_options')) {
            return $where_conditions;
        }

        // Check if agency employee or owner
        if (!$this->is_agency_employee($user_id) && !$this->is_agency_owner($user_id)) {
            return $where_conditions; // Not agency user - no filtering
        }

        // Get accessible agency IDs (all roles are filtered)
        // This handles both employees (from employee table) and owners (from agency.user_id)
        $accessible_ids = $this->relation_model->get_accessible_entity_ids('agency', $user_id);

        // Empty = see all (shouldn't happen for non-admin, but safety check)
        if (empty($accessible_ids)) {
            return $where_conditions;
        }

        // Add WHERE clause
        $ids_string = implode(',', array_map('intval', $accessible_ids));
        $where_conditions[] = "a.id IN ({$ids_string})";

        return $where_conditions;
    }

    /**
     * Check if user is agency employee
     *
     * @param int $user_id User ID
     * @return bool True if agency employee
     */
    private function is_agency_employee(int $user_id): bool {
        return $this->relation_model->is_agency_employee($user_id);
    }

    /**
     * Check if user owns any agency
     *
     * @param int $user_id User ID
     * @return bool True if user owns agency
     */
    private function is_agency_owner(int $user_id): bool {
        return $this->relation_model->is_agency_owner($user_id);
    }
}
