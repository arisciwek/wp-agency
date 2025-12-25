<?php
/**
 * Agency DataTable Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Agency
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Agency/AgencyDataTableModel.php
 *
 * Description: DataTable model untuk server-side processing agencies.
 *              Extends base DataTableModel dari wp-app-core.
 *              Implements columns, joins, dan row formatting.
 *              Integrates dengan base panel system (TODO-2179).
 *
 * Changelog:
 * 1.0.3 - 2025-11-04 (FIX: Use province_id/regency_id instead of codes)
 * - CRITICAL FIX: Changed JOIN conditions from code-based to ID-based
 * - Updated base_joins: a.province_id = p.id, a.regency_id = r.id
 * - Updated get_columns(): a.province_id, a.regency_id (instead of provinsi_code/regency_code)
 * - Matches current AgencysDB schema (ID-based FKs, not code-based)
 * - Fixes DataTable error "Unknown column 'a.provinsi_code' in 'field list'"
 *
 * 1.0.2 - 2025-11-01 (TODO-3094 Follow-up)
 * - Added get_total_count() method for dashboard statistics
 * - Eliminates query duplication (DRY principle)
 * - Dashboard stats now use same permission logic as DataTable
 * - Applies wpapp_datatable_app_agencies_where filter for wp-customer integration
 * - Single source of truth for counting with permission filtering
 *
 * 1.0.1 - 2025-11-01 (TODO-3094)
 * - MAJOR: Moved permission filtering logic from AgencyModel
 * - Added employee JOIN to base_joins for permission filtering
 * - Updated get_where() with complete permission logic
 * - Fully utilizes wp-app-core DataTable centralization pattern
 * - Fixes Task-2176: customer_admin can now see Disnaker list
 *
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 1, Task 1.1)
 * - Extends WPAppCore\Models\DataTable\DataTableModel
 * - Define columns: code, name, provinsi_name, regency_name, status, actions
 * - Implement get_joins() for provinces & regencies
 * - Implement format_row() with proper escaping
 * - Add format_status_badge() helper
 * - Add generate_action_buttons() helper
 */

namespace WPAgency\Models\Agency;

use WPAppCore\Models\DataTable\DataTableModel;

class AgencyDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;

        $this->table = $wpdb->prefix . 'app_agencies a';
        $this->index_column = 'a.id';

        // Set base WHERE - parent will apply wpdt_datatable_agencies_where filter to this
        $status_filter = isset($_POST['status_filter'])
            ? sanitize_text_field($_POST['status_filter'])
            : 'active';

        if (!current_user_can('delete_agency')) {
            $status_filter = 'active';
        }

        if ($status_filter !== 'all') {
            $this->base_where = [$wpdb->prepare('a.status = %s', $status_filter)];
        } else {
            $this->base_where = [];
        }

        // Define searchable columns
        $this->searchable_columns = [
            'a.code',
            'a.name',
            'p.name',
            'r.name'
        ];

        // Define base JOINs
        $this->base_joins = [
            'LEFT JOIN ' . $wpdb->prefix . 'wi_provinces p ON a.province_id = p.id',
            'LEFT JOIN ' . $wpdb->prefix . 'wi_regencies r ON a.regency_id = r.id'
        ];
    }

    /**
     * Get columns configuration for DataTable
     *
     * Defines all columns with their properties:
     * - data: Column identifier for DataTables
     * - title: Display title (translatable)
     * - searchable: Whether column is searchable
     * - orderable: Whether column is sortable
     *
     * @return array Columns configuration
     */
    protected function get_columns(): array {
        error_log('=== AGENCY DATATABLE MODEL DEBUG ===');
        error_log('get_columns() called');

        $columns = [
            'a.code as code',
            'a.name as name',
            'a.province_id',
            'a.regency_id',
            'p.name as provinsi_name',
            'r.name as regency_name',
            'a.id as id'
        ];

        error_log('Columns defined: ' . print_r($columns, true));
        return $columns;
    }


    /**
     * Format row data for DataTable output
     *
     * Applies proper escaping and formatting to each row.
     * Adds DT_RowId and DT_RowData for panel functionality.
     *
     * Security:
     * - All text output is escaped with esc_html()
     * - HTML output (badges, buttons) uses wp_kses_post()
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        return [
            'DT_RowId' => 'agency-' . $row->id,  // Required for panel open
            'DT_RowData' => [
                'id' => $row->id,                 // Required for panel AJAX
                'entity' => 'agency'              // Required for panel entity detection
            ],
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            'provinsi_name' => esc_html($row->provinsi_name ?? '-'),
            'regency_name' => esc_html($row->regency_name ?? '-'),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Applies multiple filters:
     * 1. Status filter (active/inactive/all)
     * 2. Permission-based filtering (owner, employee, or hook-based)
     * 3. Search conditions (handled by parent)
     *
     * Permission Logic (moved from AgencyModel::getDataTableData):
     * - edit_all_agencies: No restrictions
     * - Has agency OR is employee: Filter by owner OR employee
     * - view_agency_list only: Allow hook-based filtering (wp-customer integration)
     *
     * @return array WHERE conditions
     */

    /**
     * Format status badge with color coding
     *
     * Generates WordPress-style status badge with colors:
     * - active: Green badge
     * - inactive: Red badge
     *
     * @param string $status Status value ('active' or 'inactive')
     * @return string HTML badge
     */
    private function format_status_badge(string $status): string {
        $badge_class = $status === 'active' ? 'success' : 'error';
        $status_text = $status === 'active'
            ? __('Active', 'wp-agency')
            : __('Inactive', 'wp-agency');

        return sprintf(
            '<span class="wpdt-badge wpdt-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate action buttons for each row
     *
     * Creates action buttons with permission checks:
     * - View: Opens right panel (handled by base panel system)
     * - Edit: For users with edit_agency capability
     * - Delete: For users with delete_agency capability
     *
     * Permissions are filtered via:
     * - apply_filters('wp_agency_can_edit_agency', bool, int)
     * - apply_filters('wp_agency_can_delete_agency', bool, int)
     *
     * @param object $row Database row object
     * @return string HTML action buttons
     */
    private function generate_action_buttons($row): string {
        $buttons = [];

        // View button (always shown, opens panel)
        // Base panel system handles the click event via DT_RowId
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="agency" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-agency')
        );

        // Edit button (if user has permission)
        $can_edit = current_user_can('edit_agency');
        $can_edit = apply_filters('wp_agency_can_edit_agency', $can_edit, $row->id);

        if ($can_edit) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-edit-agency" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit', 'wp-agency')
            );
        }

        // Delete button (if user has permission)
        $can_delete = current_user_can('delete_agency');
        $can_delete = apply_filters('wp_agency_can_delete_agency', $can_delete, $row->id);

        if ($can_delete) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpdt-delete-agency" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete', 'wp-agency')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Get table alias for WHERE/JOIN clauses
     *
     * Used by DataTableQueryBuilder for consistent aliasing
     *
     * @return string Table alias
     */
    protected function get_table_alias(): string {
        return 'a';
    }

    /**
     * Get total count with permission filtering
     *
     * Helper method for dashboard statistics.
     * Reuses same permission logic as DataTable + applies cross-plugin filters.
     *
     * IMPORTANT: Applies wpapp_datatable_app_agencies_where filter for wp-customer integration!
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    /**
     * Get total count with permission filtering
     *
     * Pattern from wp-customer CustomerDataTableModel
     * Applies wpapp_datatable_agencies_where filter for role-based access
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(string $status_filter = 'active'): int {
        global $wpdb;

        // Build base WHERE conditions
        $where_conditions = [];

        if ($status_filter !== 'all') {
            $where_conditions[] = $wpdb->prepare('a.status = %s', $status_filter);
        }

        /**
         * CRITICAL: Apply role-based filter hook
         *
         * This hook allows:
         * - AgencyRoleFilter to filter by user's assigned agency
         * - DataTableAccessFilter for generic entity filtering
         * - AgencyAccessFilter (wp-customer) for cross-plugin integration
         *
         * Same hook used by DataTable for consistency
         */
        $where_conditions = apply_filters(
            'wpapp_datatable_agencies_where',
            $where_conditions,
            ['status_filter' => $status_filter],
            $this
        );

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(DISTINCT a.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
