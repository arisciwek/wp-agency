<?php
/**
 * Division DataTable Model
 *
 * Handles server-side DataTable processing for agency divisions.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.4.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Division/DivisionDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              divisions dari sebuah agency dengan lazy loading pattern.
 *              Filter otomatis berdasarkan agency_id.
 *              Includes jurisdiction (wilayah kerja) data.
 *
 * Changelog:
 * 1.4.0 - 2025-11-01 (TODO-3095)
 * - Added get_total_count() method for dashboard statistics
 * - Eliminates query duplication (DRY principle)
 * - Dashboard stats now use same filtering logic as DataTable
 * - Single source of truth for counting with filtering
 *
 * 1.3.0 - 2025-10-31 (TODO-3092 Use QueryBuilder with GROUP BY)
 * - REVERT: Removed manual query override
 * - USE: Parent QueryBuilder with new GROUP BY support
 * - CLEAN: Simpler implementation using set_group_by()
 * - PATTERN: Follows Perfex CRM centralized datatable pattern
 *
 * 1.2.0 - 2025-10-31 (TODO-3092 Final Fix) - DEPRECATED
 * - Manual query override (no longer needed)
 *
 * 1.1.0 - 2025-10-31 (TODO-3092 Review-02)
 * - REMOVED: type column (replaced with wilayah_kerja)
 * - REMOVED: status column (moved to filter dropdown)
 * - ADDED: wilayah_kerja column (dari JurisdictionDB)
 * - ADDED: JOIN ke app_agency_jurisdictions
 * - ADDED: JOIN ke wi_regencies untuk nama wilayah
 * - ADDED: GROUP BY for GROUP_CONCAT
 * - Columns: code, name, wilayah_kerja
 *
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 * - Columns: code, name, type, status
 * - Filter by agency_id
 */

namespace WPAgency\Models\Division;

use WPDataTable\Core\AbstractDataTable;

defined('ABSPATH') || exit;

class DivisionDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_agency_divisions d';
        $this->index_column = 'd.id';

        // Define searchable columns
        $this->searchable_columns = [
            'd.code',
            'd.name'
        ];

        // Base joins for jurisdiction data
        $this->base_joins = $this->get_joins([]);

        // Base WHERE for agency filtering
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 10, 3);

        // Hook to set GROUP BY in QueryBuilder
        add_filter($this->get_filter_hook('query_builder'), [$this, 'set_query_builder_group_by'], 10, 3);
    }

    /**
     * Set GROUP BY clause in QueryBuilder
     *
     * Hooked to: wpapp_datatable_agency_divisions_query_builder
     *
     * @param \WPAppCore\Models\DataTable\DataTableQueryBuilder $query_builder Query builder instance
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return \WPAppCore\Models\DataTable\DataTableQueryBuilder Modified query builder
     */
    public function set_query_builder_group_by($query_builder, $request_data, $model) {
        // Set GROUP BY for jurisdiction aggregation
        $query_builder->set_group_by('d.id');
        return $query_builder;
    }

    /**
     * Get JOIN clauses for query
     *
     * @param array $request_data Request data from DataTables
     * @return array JOIN clauses
     */
    protected function get_joins(array $request_data): array {
        global $wpdb;

        return [
            "LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id",
            "LEFT JOIN {$wpdb->prefix}wi_regencies wr ON j.jurisdiction_regency_id = wr.id",
            "LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID"
        ];
    }

    /**
     * Get columns for SELECT clause
     *
     * Required by AbstractDataTable for building SELECT clause.
     *
     * @return array Column definitions
     */
    protected function get_select_columns(): array {
        error_log('[DivisionDataTableModel] get_select_columns() called');

        global $wpdb;

        // Use MAX() for non-aggregated columns when using GROUP BY
        $columns = [
            'd.code as code',
            'd.name as name',
            'd.type as type',
            'd.status as status',
            "COALESCE(u.display_name, '-') as admin_name",
            "GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as wilayah_kerja",
            "GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as jurisdictions",
            'd.id as id'
        ];

        error_log('[DivisionDataTableModel] Columns: ' . print_r($columns, true));
        return $columns;
    }

    /**
     * DEPRECATED: Use get_select_columns() instead
     * Kept for backward compatibility
     *
     * @return array Columns configuration
     */
    public function get_columns(): array {
        return $this->get_select_columns();
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    public function format_row($row): array {
        $division_id = $row->id ?? 0;

        // Generate action buttons with permission check
        $actions = $this->generate_action_buttons($row);

        // Return associative array like wp-customer BranchDataTableModel
        return [
            'DT_RowId' => 'division-' . $division_id,
            'DT_RowData' => [
                'id' => $division_id,
                'status' => $row->status ?? 'active'
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'wilayah_kerja' => esc_html($row->wilayah_kerja ?? '-'),
            'actions' => $actions
        ];
    }

    /**
     * Generate action buttons for division row
     *
     * @param object $row Database row object
     * @param array $options Optional parameters
     * @return string HTML for action buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = '';
        $division_id = $row->id ?? 0;

        // Edit button - check permission
        // Uses wpdt-edit-btn class for auto-wire modal system integration
        if (current_user_can('edit_all_divisions') || current_user_can('edit_own_division')) {
            $buttons .= sprintf(
                '<button type="button" class="button button-small wpdt-edit-btn" data-id="%d" data-entity="division" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button> ',
                $division_id,
                esc_attr__('Edit', 'wp-agency')
            );
        }

        // Delete button - check permission
        // Uses wpdt-delete-btn class for auto-wire modal system integration
        if (current_user_can('delete_division')) {
            $buttons .= sprintf(
                '<button type="button" class="button button-small wpdt-delete-btn" data-id="%d" data-entity="division" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                $division_id,
                esc_attr__('Delete', 'wp-agency')
            );
        }

        return $buttons ?: '-';
    }

    /**
     * Filter WHERE conditions
     *
     * Hooked to: wpapp_datatable_agency_divisions_where
     * Filters by agency_id and optionally status from request data
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        error_log('[DivisionDataTableModel] filter_where() called');
        error_log('[DivisionDataTableModel] Request data: ' . print_r($request_data, true));

        global $wpdb;

        // Filter by agency_id (required)
        if (isset($request_data['agency_id'])) {
            $agency_id = (int) $request_data['agency_id'];
            error_log('[DivisionDataTableModel] Filtering by agency_id: ' . $agency_id);
            $where_conditions[] = $wpdb->prepare('d.agency_id = %d', $agency_id);
        } else {
            error_log('[DivisionDataTableModel] ❌ CRITICAL: No agency_id in request data - this will return wrong results!');
        }

        // Filter by status (optional, from dropdown filter)
        if (isset($request_data['status_filter']) && !empty($request_data['status_filter'])) {
            $status = sanitize_text_field($request_data['status_filter']);
            error_log('[DivisionDataTableModel] Filtering by status: ' . $status);
            $where_conditions[] = $wpdb->prepare('d.status = %s', $status);
        } else {
            // Default to active if no filter specified
            error_log('[DivisionDataTableModel] No status_filter, defaulting to active');
            $where_conditions[] = "d.status = 'active'";
        }

        /**
         * Filter: Allow modification of WHERE conditions
         *
         * Used by:
         * - DivisionAccessFilter: Role-based filtering
         * - DataTableAccessFilter: Generic entity filtering
         *
         * @param array $where WHERE conditions
         * @param array $request_data Request data
         * @param object $model Current model instance
         * @return array Modified WHERE conditions
         */
        $where_conditions = apply_filters('wpdt_datatable_divisions_where', $where_conditions, $request_data, $model);

        error_log('[DivisionDataTableModel] Final WHERE conditions: ' . print_r($where_conditions, true));
        return $where_conditions;
    }

    /**
     * Get total count with filtering
     *
     * Helper method for dashboard statistics.
     * Reuses same filtering logic as DataTable.
     *
     * @param int $agency_id Agency ID to filter by
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(int $agency_id, string $status_filter = 'active'): int {
        global $wpdb;

        // Prepare request data for filtering
        $request_data = [
            'agency_id' => $agency_id,
            'status_filter' => $status_filter
        ];

        // Temporarily set POST for filter_where() method
        $original_post = $_POST;
        $_POST['agency_id'] = $agency_id;
        $_POST['status_filter'] = $status_filter;

        // Build WHERE conditions using same logic as DataTable
        $where_conditions = $this->filter_where([], $request_data, $this);

        // Restore original POST
        $_POST = $original_post;

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        // Use DISTINCT COUNT with GROUP BY
        $count_sql = "SELECT COUNT(DISTINCT d.id) as total
                      FROM {$this->table}
                      " . implode(' ', $this->base_joins) . "
                      {$where_sql}";

        error_log('[DivisionDataTableModel::get_total_count] Query: ' . $count_sql);

        return (int) $wpdb->get_var($count_sql);
    }

}
