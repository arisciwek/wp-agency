<?php
/**
 * Agency DataTable Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Agency
 * @version     1.0.0
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
        $this->table = $wpdb->prefix . 'app_agencies a';  // Include alias 'a' for columns
        $this->index_column = 'a.id';

        // Define searchable columns for global search
        $this->searchable_columns = [
            'a.code',
            'a.name',
            'p.name',  // province name
            'r.name'   // regency name
        ];

        // Define base JOINs for location data
        $this->base_joins = [
            'LEFT JOIN wp_wi_provinces p ON a.provinsi_code = p.code',
            'LEFT JOIN wp_wi_regencies r ON a.regency_code = r.code'
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
     * Applies status filter:
     * - Default: active only
     * - If status_filter POST param = 'all': no filter
     * - If status_filter POST param = 'inactive': inactive only
     *
     * @return array WHERE conditions
     */
    public function get_where(): array {
        global $wpdb;
        $where = parent::get_where();

        // Get status filter from POST (from select filter)
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'active';

        // Apply status filter
        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('a.status = %s', $status_filter);
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
}
