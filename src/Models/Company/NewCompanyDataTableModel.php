<?php
/**
 * New Company DataTable Model
 *
 * Handles server-side DataTable processing for branches without inspector.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Company
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Company/NewCompanyDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              branches (companies) yang belum memiliki inspector.
 *              Filter otomatis berdasarkan agency_id.
 *              Follows centralization pattern from TODO-3094/3095/3096.
 *
 * Changelog:
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

use WPAppCore\Models\DataTable\DataTableModel;

defined('ABSPATH') || exit;

class NewCompanyDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches b';
        $this->index_column = 'b.id';

        // Define searchable columns
        $this->searchable_columns = [
            'b.code',
            'c.name', // company name
            'd.name', // division name
            'r.name'  // regency name
        ];

        // Base joins
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id",
            "LEFT JOIN {$wpdb->prefix}app_agencies a ON b.agency_id = a.id",
            "LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON b.division_id = d.id",
            "LEFT JOIN {$wpdb->prefix}wi_regencies r ON b.regency_id = r.id"
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
        return 'b';
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        return [
            'b.id as id',
            'b.code as code',
            'c.name as company_name',
            'd.name as division_name',
            'r.name as regency_name',
            'b.agency_id as agency_id',
            'b.division_id as division_id'
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    protected function format_row($row): array {
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
    private function generate_action_buttons($row): string {
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
        // Only users with assign_inspector_to_branch capability can see this button
        // (agency_admin_dinas and agency_admin_unit have this capability)
        if (current_user_can('assign_inspector_to_branch')) {
            $actions .= sprintf(
                '<button type="button" class="button button-small button-primary assign-inspector" data-id="%d" data-company="%s" title="%s">
                    <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </button>',
                $branch_id,
                esc_attr($row->company_name ?? ''),
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
     * 1. Role-based access control (province OR jurisdiction)
     * 2. Agency filter (branches WITHOUT agency assignment)
     * 3. Inspector filter (branches without inspector)
     * 4. Status filter (active only)
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        global $wpdb;

        // 1. Role-based access control using EntityRelationModel
        // Platform staff (WordPress admin) bypass filtering
        if (!current_user_can('manage_options')) {
            $entity_model = new \WPAgency\Models\Relation\EntityRelationModel();
            $accessible_company_ids = $entity_model->get_accessible_entity_ids('company');

            // If not empty array (empty = see all), apply filter
            if (!empty($accessible_company_ids)) {
                // If [0], no access
                if ($accessible_company_ids === [0]) {
                    $where_conditions[] = '1=0'; // Block all
                } else {
                    // Filter by accessible company IDs
                    $placeholders = implode(',', array_fill(0, count($accessible_company_ids), '%d'));
                    $where_conditions[] = $wpdb->prepare("b.id IN ($placeholders)", ...$accessible_company_ids);
                }
            }
        }

        // 2. Filter branches WITHOUT agency (new companies)
        $where_conditions[] = '(b.agency_id IS NULL OR b.agency_id = 0)';

        // 3. Filter branches without inspector
        $where_conditions[] = 'b.inspector_id IS NULL';

        // 4. Filter active branches only
        $where_conditions[] = $wpdb->prepare('b.status = %s', 'active');

        return $where_conditions;
    }

    /**
     * Get total count of branches without inspector for specific agency
     *
     * Helper method for dashboard statistics.
     * Reuses same filtering logic as DataTable.
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

        $count_sql = "SELECT COUNT(b.id) as total
                      FROM {$this->table}
                      " . implode(' ', $this->base_joins) . "
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
