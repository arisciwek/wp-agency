<?php
/**
 * Employee DataTable Model
 *
 * Handles server-side DataTable processing for agency employees.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Employee
 * @version     1.2.2
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Employee/EmployeeDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              employees dari sebuah agency dengan lazy loading pattern.
 *              Filter otomatis berdasarkan agency_id.
 *
 * Changelog:
 * 1.2.2 - 2025-11-01 (TODO-2183 Follow-up: CRITICAL FIX)
 * - FIXED: WHERE conditions not applied to DataTable queries
 * - FIXED: Wrong hook name (was wpapp_datatable_app_agency_employees_where, now wpapp_datatable_agency_employees_where)
 * - REASON: wp-app-core removes 'app_' prefix when generating hook names
 * - CHANGED: Moved filtering logic from get_where() method to apply_filters_to_where() hook
 * - REASON: Parent DataTableModel doesn't call get_where() during build_query
 * - ADDED: apply_filters_to_where() method (priority 5, before cross-plugin filters)
 * - NOW: agency_id and status filters properly applied via wpapp_datatable_agency_employees_where hook
 * - RESULT: customer_admin now sees only accessible agency employees (3 employees, not 50)
 *
 * 1.2.1 - 2025-11-01 (TODO-2183 Follow-up: Agency Employee Filtering)
 * - Added cross-plugin integration filter hook in get_where()
 * - Hook: wpapp_datatable_app_agency_employees_where
 * - Allows wp-customer to filter employees by accessible agencies
 * - Follows same pattern as AgencyDataTableModel
 *
 * 1.2.0 - 2025-11-01 (TODO-3096 Follow-up: Complete Optimization)
 * - Added get_total_count_global() for dashboard global statistics
 * - AgencyEmployeeModel now fully reuses DataTableModel (both agency-specific & global)
 * - Eliminated ALL manual counting queries (60+ lines saved)
 * - Single source of truth for ALL counting scenarios
 *
 * 1.1.0 - 2025-11-01 (TODO-3096)
 * - FIXED: get_where() bug (removed non-existent parent call)
 * - Added status filtering logic
 * - Added get_total_count() method for dashboard statistics
 * - Eliminates query duplication (DRY principle)
 *
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 * - Columns: name, position, email, phone, status
 * - Filter by agency_id
 */

namespace WPAgency\Models\Employee;

use WPAppCore\Models\DataTable\DataTableModel;

defined('ABSPATH') || exit;

class EmployeeDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_agency_employees e';
        $this->index_column = 'e.id';

        // Define searchable columns
        $this->searchable_columns = [
            'e.name',
            'e.email',
            'e.phone'
        ];

        // No base joins needed
        $this->base_joins = [];

        // Base WHERE for agency filtering
        $this->base_where = [];

        // Register WHERE filter to apply agency_id and status filters
        add_filter('wpapp_datatable_agency_employees_where', [$this, 'apply_filters_to_where'], 5, 3);
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        return [
            'e.name as name',
            'e.position as position',
            'e.email as email',
            'e.phone as phone',
            'e.status as status',
            'e.id as id'
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
            'DT_RowId' => 'employee-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0
            ],
            'name' => esc_html($row->name ?? ''),
            'position' => esc_html($row->position ?? ''),
            'email' => esc_html($row->email ?? ''),
            'phone' => esc_html($row->phone ?? ''),
            'status' => $this->format_status_badge($row->status ?? '')
        ];
    }

    /**
     * Apply WHERE filters via hook
     *
     * This method is called by wpapp_datatable_app_agency_employees_where filter (priority 5).
     * It applies agency_id and status filters BEFORE cross-plugin integrations (priority 10).
     *
     * @param array $where Existing WHERE conditions
     * @param array $request Request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function apply_filters_to_where($where, $request, $model) {
        global $wpdb;

        // Only apply if this is the right model instance
        if (!($model instanceof self)) {
            return $where;
        }

        // 1. Filter by agency_id (required)
        if (isset($request['agency_id'])) {
            $agency_id = (int) $request['agency_id'];
            $where[] = $wpdb->prepare('e.agency_id = %d', $agency_id);
            error_log('[EmployeeDataTableModel] Filtering by agency_id: ' . $agency_id);
        } else {
            error_log('[EmployeeDataTableModel] ❌ WARNING: No agency_id in request!');
        }

        // 2. Status filter (optional, from dropdown filter)
        $status_filter = isset($request['status_filter']) ? sanitize_text_field($request['status_filter']) : 'active';

        // Force active filter if user doesn't have delete_employee permission
        if (!current_user_can('delete_employee')) {
            $status_filter = 'active';
        }

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('e.status = %s', $status_filter);
            error_log('[EmployeeDataTableModel] Filtering by status: ' . $status_filter);
        }

        return $where;
    }

    /**
     * Get WHERE conditions for filtering
     *
     * DEPRECATED: This method is now only used by get_total_count() for backward compatibility.
     * The actual filtering in get_datatable_data() is done via apply_filters_to_where() hook.
     *
     * Applies multiple filters:
     * 1. Agency filter (required - from POST)
     * 2. Status filter (active/inactive/all)
     * 3. Cross-plugin integration filters (via wpapp_datatable_app_agency_employees_where)
     *
     * @return array WHERE conditions
     */
    public function get_where(): array {
        global $wpdb;
        $where = []; // Initialize (parent doesn't have get_where method)

        // 1. Filter by agency_id (required)
        if (isset($_POST['agency_id'])) {
            $agency_id = (int) $_POST['agency_id'];
            $where[] = $wpdb->prepare('e.agency_id = %d', $agency_id);
            error_log('[EmployeeDataTableModel] Filtering by agency_id: ' . $agency_id);
        } else {
            error_log('[EmployeeDataTableModel] ❌ WARNING: No agency_id in request!');
        }

        // 2. Status filter (optional, from dropdown filter)
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

        // Force active filter if user doesn't have delete_employee permission
        if (!current_user_can('delete_employee')) {
            $status_filter = 'active';
        }

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('e.status = %s', $status_filter);
            error_log('[EmployeeDataTableModel] Filtering by status: ' . $status_filter);
        }

        // 3. Apply cross-plugin integration filters (e.g., wp-customer filtering by accessible agencies)
        $where = apply_filters(
            'wpapp_datatable_agency_employees_where',
            $where,
            $_POST,
            $this
        );

        return $where;
    }

    /**
     * Format status badge
     *
     * @param string $status Status value
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
     * Get total count with filtering for specific agency
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

        // Temporarily set POST for get_where() method
        $original_post = $_POST;
        $_POST['agency_id'] = $agency_id;
        $_POST['status_filter'] = $status_filter;

        // Build WHERE conditions using same logic as DataTable
        $where_conditions = $this->get_where();

        // Restore original POST
        $_POST = $original_post;

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(e.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        error_log('[EmployeeDataTableModel::get_total_count] Query: ' . $count_sql);

        return (int) $wpdb->get_var($count_sql);
    }

    /**
     * Get total count across all accessible agencies
     *
     * For global dashboard statistics (no specific agency).
     * Applies permission-based filtering.
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count_global(string $status_filter = 'active'): int {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Build base WHERE
        $where_conditions = [];

        // Status filter
        if ($status_filter !== 'all') {
            $where_conditions[] = $wpdb->prepare('e.status = %s', $status_filter);
        }

        // Permission-based filtering
        if (current_user_can('edit_all_agencies') || current_user_can('edit_all_employees')) {
            // Admin: no additional restrictions
            error_log('[EmployeeDataTableModel] Admin - no restrictions');
        } else {
            // Check user relationship with agencies
            $agency_table = $wpdb->prefix . 'app_agencies';
            $has_agency = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$agency_table} WHERE user_id = %d",
                $current_user_id
            ));

            $is_employee = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND status = 'active'",
                $current_user_id
            ));

            if ($has_agency > 0 || $is_employee > 0) {
                // Filter by accessible agencies
                $where_conditions[] = $wpdb->prepare(
                    "EXISTS (
                        SELECT 1 FROM {$agency_table} a
                        LEFT JOIN {$this->table} emp ON a.id = emp.agency_id AND emp.status = 'active'
                        WHERE e.agency_id = a.id
                        AND (a.user_id = %d OR emp.user_id = %d)
                        AND a.status = 'active'
                    )",
                    $current_user_id,
                    $current_user_id
                );
                error_log('[EmployeeDataTableModel] Restricted to user agencies');
            } else {
                // No access
                $where_conditions[] = "1=0";
                error_log('[EmployeeDataTableModel] No access');
            }
        }

        // Build query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(DISTINCT e.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        error_log('[EmployeeDataTableModel::get_total_count_global] Query: ' . $count_sql);

        return (int) $wpdb->get_var($count_sql);
    }
}
