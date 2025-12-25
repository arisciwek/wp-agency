<?php
/**
 * DataTable Access Filter
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Integration
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Integration/DataTableAccessFilter.php
 *
 * Description: Generic framework untuk filtering DataTable berdasarkan role.
 *              Applies to Agency, Division, Employee DataTables.
 *              Pattern inspired by wp-customer DataTableAccessFilter.
 *
 * Hook Pattern: wpdt_datatable_{entity}_where
 * - wpdt_datatable_agencies_where
 * - wpdt_datatable_divisions_where
 * - wpdt_datatable_employees_where
 *
 * Role-Based Access:
 * - agency_admin_dinas: Bypass filter (see all)
 * - agency_admin_unit: Filtered by division
 * - agency_employee: Filtered by division
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 * - Based on wp-customer pattern
 * - Uses wpdt_ prefix for wp-datatable
 */

namespace WPAgency\Controllers\Integration;

use WPAgency\Models\Relation\EntityRelationModel;

defined('ABSPATH') || exit;

class DataTableAccessFilter {
    /**
     * @var EntityRelationModel
     */
    private $relation_model;

    /**
     * Entity configurations
     *
     * @var array
     */
    private $entity_configs = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->relation_model = new EntityRelationModel();
        $this->load_entity_configs();
        $this->register_hooks();
    }

    /**
     * Load entity configurations
     *
     * Define which entities to filter and how
     */
    private function load_entity_configs(): void {
        $this->entity_configs = [
            'agency' => [
                'hook' => 'wpapp_datatable_agencies_where',
                'entity_type' => 'agency',
                'table_alias' => 'a',
                'priority' => 20,
            ],
            'division' => [
                'hook' => 'wpapp_datatable_divisions_where',
                'entity_type' => 'division',
                'table_alias' => 'd',
                'priority' => 20,
            ],
            'employee' => [
                'hook' => 'wpapp_datatable_agency_employees_where',
                'entity_type' => 'employee',
                'table_alias' => 'e',
                'priority' => 20,
            ],
        ];

        /**
         * Filter: Allow modification of entity configurations
         *
         * @param array $configs Entity configurations
         * @return array Modified configurations
         */
        $this->entity_configs = apply_filters(
            'wpdt_agency_datatable_access_configs',
            $this->entity_configs
        );
    }

    /**
     * Register filter hooks for all entities
     */
    private function register_hooks(): void {
        foreach ($this->entity_configs as $config) {
            add_filter(
                $config['hook'],
                [$this, 'filter_datatable_where'],
                $config['priority'],
                3
            );
        }
    }

    /**
     * Filter DataTable WHERE conditions
     *
     * Generic filter method yang dipanggil oleh semua entity hooks
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_datatable_where($where_conditions, $request_data, $model): array {
        $user_id = get_current_user_id();

        // Not logged in - block all
        if (!$user_id) {
            $where_conditions[] = '1=0';
            return $where_conditions;
        }

        // Check if should bypass filter
        if ($this->should_bypass_filter($user_id)) {
            return $where_conditions;
        }

        // Determine entity type from hook
        $entity_type = $this->get_entity_type_from_hook(current_filter());

        if (!$entity_type) {
            return $where_conditions;
        }

        // Get accessible entity IDs
        $accessible_ids = $this->relation_model->get_accessible_entity_ids(
            $entity_type,
            $user_id
        );

        // Empty array = see all (platform staff/admin)
        if (empty($accessible_ids)) {
            return $where_conditions;
        }

        // Add WHERE clause for filtering
        $table_alias = $this->get_table_alias($entity_type);
        $ids_string = implode(',', array_map('intval', $accessible_ids));

        $where_conditions[] = "{$table_alias}.id IN ({$ids_string})";

        return $where_conditions;
    }

    /**
     * Check if user should bypass filtering
     *
     * Users that see all data:
     * - WordPress administrators only
     *
     * Note: Consistent with wp-customer pattern.
     * Agency roles (admin_dinas, kepala_dinas, etc.) are filtered based on relations.
     * Only WordPress admin bypasses the filter to see all agencies.
     *
     * @param int $user_id User ID
     * @return bool True if should bypass
     */
    private function should_bypass_filter(int $user_id): bool {
        // WordPress administrator only
        if (current_user_can('manage_options')) {
            return true;
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        /**
         * Filter: Allow plugins to override bypass decision
         *
         * @param bool $bypass Should bypass filter
         * @param int $user_id User ID
         * @param WP_User $user User object
         * @return bool Modified bypass decision
         */
        return apply_filters(
            'wpdt_agency_should_bypass_filter',
            false,
            $user_id,
            $user
        );
    }

    /**
     * Get entity type from current hook
     *
     * @param string $hook Hook name
     * @return string|null Entity type or null
     */
    private function get_entity_type_from_hook(string $hook): ?string {
        foreach ($this->entity_configs as $config) {
            if ($config['hook'] === $hook) {
                return $config['entity_type'];
            }
        }

        return null;
    }

    /**
     * Get table alias for entity type
     *
     * @param string $entity_type Entity type
     * @return string Table alias
     */
    private function get_table_alias(string $entity_type): string {
        foreach ($this->entity_configs as $config) {
            if ($config['entity_type'] === $entity_type) {
                return $config['table_alias'];
            }
        }

        return 'a'; // Default alias
    }
}
