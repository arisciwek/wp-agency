<?php
/**
 * Division DataTable Model
 *
 * Handles server-side DataTable processing for agency divisions.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.3.0
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

use WPAppCore\Models\DataTable\DataTableModel;

defined('ABSPATH') || exit;

class DivisionDataTableModel extends DataTableModel {

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
        $this->base_joins = $this->get_joins();

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
     * @return array JOIN clauses
     */
    protected function get_joins(): array {
        global $wpdb;

        return [
            "LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id",
            "LEFT JOIN {$wpdb->prefix}wi_regencies wr ON j.jurisdiction_code = wr.code"
        ];
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        error_log('[DivisionDataTableModel] get_columns() called');

        // Use MAX() for non-aggregated columns when using GROUP BY
        $columns = [
            'd.code as code',
            'd.name as name',
            "GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as wilayah_kerja",
            'd.status as status',  // Keep for filtering, but not displayed
            'd.id as id'
        ];

        error_log('[DivisionDataTableModel] Columns: ' . print_r($columns, true));
        return $columns;
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        return [
            'DT_RowId' => 'division-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'status' => $row->status ?? 'active'  // Keep status in data for row styling
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'wilayah_kerja' => esc_html($row->wilayah_kerja ?? '-')
        ];
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

        error_log('[DivisionDataTableModel] Final WHERE conditions: ' . print_r($where_conditions, true));
        return $where_conditions;
    }

}
