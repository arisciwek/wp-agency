<?php
/**
 * Division DataTable Model
 *
 * Handles server-side DataTable processing for agency divisions.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Agency
 * @subpackage  Models/Division
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Division/DivisionDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              divisions dari sebuah agency dengan lazy loading pattern.
 *              Filter otomatis berdasarkan agency_id.
 *
 * Changelog:
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
            'd.code as code',
            'd.name as name',
            'd.type as type',
            'd.status as status',
            'd.id as id'
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
            'DT_RowId' => 'division-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'type' => $this->format_type_badge($row->type ?? ''),
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
            $where[] = $wpdb->prepare('d.agency_id = %d', $agency_id);
        }

        return $where;
    }

    /**
     * Format type badge
     *
     * @param string $type Division type
     * @return string HTML badge
     */
    private function format_type_badge(string $type): string {
        $badges = [
            'pusat' => '<span class="wpapp-badge wpapp-badge-primary">' . esc_html__('Pusat', 'wp-agency') . '</span>',
            'cabang' => '<span class="wpapp-badge wpapp-badge-info">' . esc_html__('Cabang', 'wp-agency') . '</span>'
        ];

        return $badges[$type] ?? '<span class="wpapp-badge">' . esc_html($type) . '</span>';
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
