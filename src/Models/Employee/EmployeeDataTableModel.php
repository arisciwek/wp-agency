<?php
/**
 * Employee DataTable Model
 *
 * Handles server-side DataTable processing for agency employees.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Employee/EmployeeDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              employees dari sebuah agency dengan lazy loading pattern.
 *              Filter otomatis berdasarkan agency_id.
 *
 * Changelog:
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
     * Get additional WHERE conditions
     *
     * Filters by agency_id from POST data
     *
     * @return array WHERE conditions
     */
    public function get_where(): array {
        global $wpdb;
        $where = parent::get_where();

        // Filter by agency_id (required)
        if (isset($_POST['agency_id'])) {
            $agency_id = (int) $_POST['agency_id'];
            $where[] = $wpdb->prepare('e.agency_id = %d', $agency_id);
        }

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
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }
}
