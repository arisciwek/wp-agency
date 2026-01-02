<?php
/**
 * New Company DataTable Model
 *
 * Handles server-side DataTable processing for branches without inspector.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Company
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Company/NewCompanyDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              branches (companies) yang belum memiliki inspector.
 *              Filter otomatis berdasarkan province_id dari agency.
 *              Menampilkan hanya branches di provinsi yang sama dengan agency.
 *              Follows centralization pattern from TODO-3094/3095/3096.
 *
 * Changelog:
 * 1.2.0 - 2026-01-02 (Province-Based Filtering)
 * - BREAKING: Changed filter logic from "unassigned branches" to "province-based"
 * - CHANGED: Filter now shows branches in same province as agency (province_id match)
 * - CHANGED: Branches without inspector (inspector_id IS NULL) in agency's province
 * - REMOVED: Filter for agency_id IS NULL (old logic)
 * - ADDED: Query agency province_id and filter branches by matching province
 * - RATIONALE: Agencies are province-scoped (Disnaker Provinsi)
 * - BENEFIT: Each agency only sees branches in their jurisdiction
 * - Example: Disnaker Provinsi Aceh only sees Aceh branches
 * 1.1.0 - 2026-01-02 (Auto-Wire Migration)
 * - MIGRATED: Assign button to auto-wire modal system
 * - CHANGED: Button class from .assign-inspector to .wpdt-edit-btn
 * - ADDED: data-entity="new-company" for auto-wire detection
 * - BENEFIT: Zero JavaScript needed, config-driven modal handling
 * - CONSISTENT: Follows division/employee pattern
 * 1.0.2 - 2025-12-30
 * - CRITICAL FIX: filter_where() now checks model instance
 * - Prevents filter conflict with wp-customer's CompanyDataTableModel
 * - Added instanceof check to only apply filters to NewCompanyDataTableModel
 * - Fixes bug where Companies page showed only unassigned branches
 *
 * 1.0.1 - 2025-11-01 (TODO-3097 Fix)
 * - FIXED: Renamed get_where() to filter_where() (proper wp-app-core pattern)
 * - FIXED: Register filter hook in constructor
 * - FIXED: Agency filter now works correctly
 * - Method signature matches wp-app-core expectations
 *
 * 1.0.0 - 2025-11-01 (TODO-3097)
 * - Initial implementation following centralization pattern
 * - Extends wp-app-core DataTableModel
 * - Columns: Kode, Perusahaan, Unit, Yuridiksi, Aksi
 * - Filter: agency_id, inspector_id IS NULL, status active
 * - Added get_total_count() for dashboard statistics
 * - Added generate_action_buttons() for view and assign
 */

namespace WPAgency\Models\Company;

use WPDataTable\Core\AbstractDataTable;

defined('ABSPATH') || exit;

class NewCompanyDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches cb';
        $this->index_column = 'cb.id';

        // Define searchable columns
        $this->searchable_columns = [
            'cb.code',
            'c.name', // company name
            'd.name', // division name
            'r.name'  // regency name
        ];

        // Base joins
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}app_customers c ON cb.customer_id = c.id",
            "LEFT JOIN {$wpdb->prefix}app_agencies a ON cb.agency_id = a.id",
            "LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON cb.division_id = d.id",
            "LEFT JOIN {$wpdb->prefix}wi_regencies r ON cb.regency_id = r.id"
        ];

        // Base WHERE
        $this->base_where = [];

        // Register filter hook for WHERE conditions
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 10, 3);
    }

    /**
     * Get table alias used in queries
     * Required by BranchAccessFilter from wp-customer plugin
     *
     * @return string Table alias
     */
    public function get_table_alias(): string {
        return 'cb';
    }

    /**
     * Get columns for SELECT clause
     *
     * Required by AbstractDataTable for building SELECT clause.
     *
     * @return array Column definitions
     */
    protected function get_select_columns(): array {
        return [
            'cb.id as id',
            'cb.code as code',
            'c.name as company_name',
            'd.name as division_name',
            'r.name as regency_name',
            'cb.agency_id as agency_id',
            'cb.division_id as division_id'
        ];
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
        return [
            'DT_RowId' => 'branch-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'agency_id' => $row->agency_id ?? 0,
                'division_id' => $row->division_id ?? 0
            ],
            'code' => esc_html($row->code ?? ''),
            'company_name' => esc_html($row->company_name ?? ''),
            'division_name' => esc_html($row->division_name ?? '-'),
            'regency_name' => esc_html($row->regency_name ?? '-'),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate action buttons for DataTable row
     *
     * @param object $row Database row
     * @return string HTML for action buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $actions = '';
        $branch_id = $row->id ?? 0;
        $agency_id = $row->agency_id ?? 0;

        // View button
        $actions .= sprintf(
            '<button type="button" class="button button-small view-company" data-id="%d" title="%s" style="margin-right: 5px;">
                <span class="dashicons dashicons-visibility" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </button>',
            $branch_id,
            __('Lihat Detail', 'wp-agency')
        );

        // Assign inspector button - check permission
        // Uses wpdt-edit-btn class for auto-wire modal system integration
        // Auto-wire system will handle modal display and form submission automatically
        // (agency_admin_dinas and agency_admin_unit have assign_inspector_to_branch capability)
        if (current_user_can('assign_inspector_to_branch')) {
            $actions .= sprintf(
                '<button type="button" class="button button-small button-primary wpdt-edit-btn" data-id="%d" data-entity="new-company" title="%s">
                    <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </button>',
                $branch_id,
                __('Assign Pengawas', 'wp-agency')
            );
        }

        return $actions;
    }

    /**
     * Filter WHERE conditions
     *
     * Hook callback for wpapp_datatable_{table}_where filter.
     * Applies filters:
     * 1. Province filter (branches in same province as agency)
     * 2. Inspector filter (branches without inspector)
     * 3. Status filter (active only)
     *
     * IMPORTANT: Only apply these filters if $model is instance of NewCompanyDataTableModel.
     *            This prevents conflicts with CompanyDataTableModel from wp-customer plugin.
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        // CRITICAL: Only apply filter if model is NewCompanyDataTableModel
        // This prevents applying filter to wp-customer's CompanyDataTableModel
        if (!($model instanceof NewCompanyDataTableModel)) {
            return $where_conditions;
        }

        global $wpdb;

        // Get agency_id from request
        $agency_id = isset($request_data['agency_id']) ? intval($request_data['agency_id']) : 0;

        if (!$agency_id) {
            error_log('[NewCompanyDataTableModel] No agency_id in request - cannot filter by province');
            return $where_conditions;
        }

        // Get agency's province_id
        $agency_province_id = $wpdb->get_var($wpdb->prepare(
            "SELECT province_id FROM {$wpdb->prefix}app_agencies WHERE id = %d",
            $agency_id
        ));

        if (!$agency_province_id) {
            error_log('[NewCompanyDataTableModel] Agency province_id not found for agency_id: ' . $agency_id);
            return $where_conditions;
        }

        error_log('[NewCompanyDataTableModel] Filtering branches by province_id: ' . $agency_province_id . ' for agency_id: ' . $agency_id);

        // 1. Filter branches in same province as agency
        $where_conditions[] = $wpdb->prepare('cb.province_id = %d', $agency_province_id);

        // 2. Filter branches without inspector (available for assignment)
        $where_conditions[] = 'cb.inspector_id IS NULL';

        // 3. Filter active branches only
        $where_conditions[] = $wpdb->prepare('cb.status = %s', 'active');

        error_log('[NewCompanyDataTableModel] Final WHERE conditions: ' . print_r($where_conditions, true));

        return $where_conditions;
    }

    /**
     * Get total count of branches without inspector for specific agency
     *
     * Helper method for dashboard statistics.
     * Reuses same filtering logic as DataTable.
     * Counts branches in same province as agency that don't have inspector yet.
     *
     * @param int $agency_id Agency ID to filter by
     * @return int Total count
     */
    public function get_total_count(int $agency_id): int {
        global $wpdb;

        // Build request_data for filter_where() method
        $request_data = [
            'agency_id' => $agency_id
        ];

        // Build WHERE conditions using same logic as DataTable
        $where_conditions = $this->filter_where([], $request_data, $this);

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(cb.id) as total
                      FROM {$this->table}
                      " . implode(' ', $this->base_joins) . "
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
