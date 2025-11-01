<?php
/**
 * Agency DataTable Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Agency
 * @version     1.0.2
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
        $current_user_id = get_current_user_id();

        $this->table = $wpdb->prefix . 'app_agencies a';  // Include alias 'a' for columns
        $this->index_column = 'a.id';

        // Define searchable columns for global search
        $this->searchable_columns = [
            'a.code',
            'a.name',
            'p.name',  // province name
            'r.name'   // regency name
        ];

        // Define base JOINs for location data + permission filtering
        $this->base_joins = [
            'LEFT JOIN wp_wi_provinces p ON a.provinsi_code = p.code',
            'LEFT JOIN wp_wi_regencies r ON a.regency_code = r.code',
            // Employee JOIN for permission filtering (used in get_where)
            'LEFT JOIN ' . $wpdb->prefix . 'app_agency_employees ae ON a.id = ae.agency_id AND ae.user_id = ' . $current_user_id . ' AND ae.status = "active"'
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
            'a.provinsi_code',
            'a.regency_code',
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
    public function get_where(): array {
        global $wpdb;
        $where = []; // Initialize (parent class doesn't have get_where method)
        $current_user_id = get_current_user_id();

        // 1. Status filter (soft delete aware)
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

        // Force active filter if user doesn't have delete_agency permission
        if (!current_user_can('delete_agency')) {
            $status_filter = 'active';
        }

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('a.status = %s', $status_filter);
        }

        // 2. Permission-based filtering (core security logic)
        error_log('[AgencyDataTableModel] Building permission WHERE clause for user: ' . $current_user_id);

        if (current_user_can('edit_all_agencies')) {
            // Admin: No additional restrictions
            error_log('[AgencyDataTableModel] User has edit_all_agencies - no restrictions');
        } else {
            // Check user relationship with agencies
            $has_agency = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_agencies WHERE user_id = %d",
                $current_user_id
            ));

            $employee_table = $wpdb->prefix . 'app_agency_employees';
            $is_employee = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$employee_table} WHERE user_id = %d AND status = 'active'",
                $current_user_id
            ));

            error_log('[AgencyDataTableModel] User has_agency: ' . $has_agency . ', is_employee: ' . $is_employee);

            if (($has_agency > 0 || $is_employee > 0) && current_user_can('view_own_agency')) {
                // User owns agency OR is employee: filter by owner OR employee
                // Add LEFT JOIN to employee table - IMPORTANT: Add to base_joins instead!
                // This will be handled in __construct by adding to $this->base_joins
                $where[] = $wpdb->prepare("(a.user_id = %d OR ae.user_id IS NOT NULL)", $current_user_id);
                error_log('[AgencyDataTableModel] Added owner OR employee restriction');
            } elseif (!current_user_can('view_agency_list')) {
                // No access at all
                $where[] = "1=0";
                error_log('[AgencyDataTableModel] User has no access - blocking all results');
            } else {
                // Has view_agency_list but no direct relationship
                // Allow hook-based filtering (wp-customer integration)
                error_log('[AgencyDataTableModel] User has view_agency_list - allowing hook-based filtering');
            }
        }

        return $where;
    }

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
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
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
            '<button type="button" class="button button-small wpapp-panel-trigger" data-id="%d" data-entity="agency" title="%s">
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
                '<button type="button" class="button button-small wpapp-edit-agency" data-id="%d" title="%s">
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
                '<button type="button" class="button button-small wpapp-delete-agency" data-id="%d" title="%s">
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
    public function get_total_count(string $status_filter = 'active'): int {
        global $wpdb;

        // Prepare minimal request data for counting
        $request_data = [
            'start' => 0,
            'length' => 1,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'status_filter' => $status_filter
        ];

        // Temporarily set POST for get_where() method
        $original_post = $_POST;
        $_POST['status_filter'] = $status_filter;

        // Build WHERE conditions (includes permission filtering)
        $where_conditions = $this->get_where();

        /**
         * CRITICAL: Apply cross-plugin filter (wp-customer integration)
         *
         * This filter allows wp-customer's AgencyAccessFilter to add
         * additional WHERE conditions for customer_admin users.
         *
         * Without this, customer_admin would see ALL agencies instead of
         * only agencies related to their branches.
         */
        $where_conditions = apply_filters(
            'wpapp_datatable_app_agencies_where',
            $where_conditions,
            $request_data,
            $this
        );

        // Restore original POST
        $_POST = $original_post;

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        // Use DISTINCT COUNT
        $count_sql = "SELECT COUNT(DISTINCT a.id) as total
                      FROM {$this->table}
                      " . implode(' ', $this->base_joins) . "
                      {$where_sql}";

        error_log('[AgencyDataTableModel::get_total_count] Query: ' . $count_sql);

        return (int) $wpdb->get_var($count_sql);
    }
}
