<?php
/**
 * Division Access Filter
 *
 * @package     WP_Agency
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Integrations/DivisionAccessFilter.php
 *
 * Description: Specific filtering untuk Division DataTable.
 *              Filters divisions based on user role and assignment.
 *              Pattern inspired by wp-customer BranchAccessFilter.
 *
 * Hook: wpdt_datatable_divisions_where
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

class DivisionAccessFilter {
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
            'wpdt_datatable_divisions_where',
            [$this, 'filter_divisions_by_role'],
            10,
            3
        );
    }

    /**
     * Filter divisions by user role
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_divisions_by_role($where_conditions, $request_data, $model): array {
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

        // Check if agency employee
        if (!$this->is_agency_employee($user_id)) {
            return $where_conditions; // Not agency user - no filtering
        }

        // Get accessible division IDs (all roles are filtered)
        $accessible_ids = $this->relation_model->get_accessible_entity_ids('division', $user_id);

        // Empty = see all
        if (empty($accessible_ids)) {
            return $where_conditions;
        }

        // Add WHERE clause
        $ids_string = implode(',', array_map('intval', $accessible_ids));
        $where_conditions[] = "d.id IN ({$ids_string})";

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
}
