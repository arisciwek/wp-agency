<?php
/**
 * Audit Log DataTable Model
 *
 * @package     WP_Agency
 * @subpackage  Models/AuditLog
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/AuditLog/AuditLogDataTableModel.php
 *
 * Description: Server-side DataTable model untuk audit log.
 *              Shows complete timeline untuk customer + related entities.
 *              Extends DataTableModel dari wp-app-core.
 *
 * Features:
 * - Complete timeline (agency + divisions + employees)
 * - Role-based filtering (user sees authorized data only)
 * - Server-side processing for scalability
 * - Tracks ALL changes including demo data (complete audit trail)
 *
 * Usage:
 * Tab "History" di agency detail page
 * Shows: auditable_type, event, changes, user, timestamp
 *
 * Changelog:
 * 1.0.1 - 2025-12-28
 * - Fixed: Removed demo data exclusion filter (audit log should track ALL changes)
 * - Reason: Filter was causing SQL error in other DataTables due to global hook
 *
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - Complete timeline support (agency + related entities)
 * - Role-based access control
 */

namespace WPAgency\Models\AuditLog;

use WPDataTable\Core\AbstractDataTable;

defined('ABSPATH') || exit;

class AuditLogDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_agency_audit_logs al';
        $this->index_column = 'al.id';

        // Define searchable columns
        $this->searchable_columns = [
            'al.auditable_type',
            'al.event',
            'u.display_name'
        ];

        // Base joins
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}users u ON al.user_id = u.ID"
        ];

        // Base WHERE (exclude demo data by default)
        $this->base_where = [];

        // Register filter hook
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 10, 3);
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
            'al.id',
            'al.auditable_type',
            'al.auditable_id',
            'al.event',
            'al.old_values',
            'al.new_values',
            'al.user_id',
            'al.ip_address',
            'al.created_at',
            'u.display_name as user_name'
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    public function format_row($row): array {
        // Decode JSON values
        $old_values = !empty($row->old_values) ? json_decode($row->old_values, true) : null;
        $new_values = !empty($row->new_values) ? json_decode($row->new_values, true) : null;

        // Generate changes summary
        $changes_summary = $this->generate_changes_summary($old_values, $new_values, $row->event);

        // Format entity label
        $entity_label = $this->format_entity_label($row->auditable_type, $row->auditable_id);

        return [
            'DT_RowId' => 'audit-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'old_values' => $old_values,
                'new_values' => $new_values
            ],
            'created_at' => $this->format_datetime($row->created_at ?? ''),
            'entity' => $entity_label,
            'event' => $this->format_event($row->event ?? ''),
            'changes' => $changes_summary,
            'user' => esc_html($row->user_name ?? __('Unknown', 'wp-agency')),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Filter WHERE conditions
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        global $wpdb;

        // 1. Filter by agency_id (main + related entities)
        if (!empty($request_data['agency_id'])) {
            $agency_id = (int) $request_data['agency_id'];

            // Get division IDs for this agency
            $division_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d",
                $agency_id
            ));

            // Build condition: agency OR divisions OR employees
            $conditions = [];

            // Agency logs
            $conditions[] = $wpdb->prepare(
                "(al.auditable_type = 'agency' AND al.auditable_id = %d)",
                $agency_id
            );

            // Division logs
            if (!empty($division_ids)) {
                $division_placeholders = implode(',', array_fill(0, count($division_ids), '%d'));
                $conditions[] = $wpdb->prepare(
                    "(al.auditable_type = 'division' AND al.auditable_id IN ($division_placeholders))",
                    ...$division_ids
                );
            }

            // Employee logs (employees belong to divisions of this agency)
            if (!empty($division_ids)) {
                $employee_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_employees
                     WHERE division_id IN (" . implode(',', array_fill(0, count($division_ids), '%d')) . ")",
                    ...$division_ids
                ));

                if (!empty($employee_ids)) {
                    $employee_placeholders = implode(',', array_fill(0, count($employee_ids), '%d'));
                    $conditions[] = $wpdb->prepare(
                        "(al.auditable_type = 'agency_employee' AND al.auditable_id IN ($employee_placeholders))",
                        ...$employee_ids
                    );
                }
            }

            $where_conditions[] = '(' . implode(' OR ', $conditions) . ')';
        }

        // 2. Role-based filtering handled by RoleBasedFilter hook
        // (automatically applied by wp-app-core)

        // NOTE: We do NOT exclude demo data in audit logs
        // Audit logs should track ALL changes including demo data for complete audit trail

        return $where_conditions;
    }

    /**
     * Generate changes summary from old/new values
     *
     * @param array|null $old_values Old values
     * @param array|null $new_values New values
     * @param string $event Event type
     * @return string HTML summary
     */
    protected function generate_changes_summary($old_values, $new_values, $event): string {
        if ($event === 'created') {
            return '<span class="audit-event-created">' . __('Record created', 'wp-agency') . '</span>';
        }

        if ($event === 'deleted') {
            return '<span class="audit-event-deleted">' . __('Record deleted', 'wp-agency') . '</span>';
        }

        // For updated events, show changed fields
        if (empty($old_values) || empty($new_values)) {
            return '-';
        }

        $changed_fields = [];
        foreach ($new_values as $field => $new_value) {
            if (isset($old_values[$field]) && $old_values[$field] !== $new_value) {
                $changed_fields[] = esc_html($field);
            }
        }

        if (empty($changed_fields)) {
            return '-';
        }

        $count = count($changed_fields);
        $first_three = array_slice($changed_fields, 0, 3);
        $summary = implode(', ', $first_three);

        if ($count > 3) {
            $summary .= sprintf(' <span class="audit-more-changes">+%d more</span>', $count - 3);
        }

        return $summary;
    }

    /**
     * Format entity label
     *
     * @param string $type Entity type
     * @param int $id Entity ID
     * @return string Formatted label
     */
    protected function format_entity_label($type, $id): string {
        $labels = [
            'agency' => __('Agency', 'wp-agency'),
            'division' => __('Division', 'wp-agency'),
            'agency_employee' => __('Employee', 'wp-agency')
        ];

        $label = isset($labels[$type]) ? $labels[$type] : ucfirst($type);
        return sprintf('%s #%d', esc_html($label), $id);
    }

    /**
     * Format event type
     *
     * @param string $event Event type
     * @return string Formatted HTML
     */
    protected function format_event($event): string {
        $classes = [
            'created' => 'audit-badge-created',
            'updated' => 'audit-badge-updated',
            'deleted' => 'audit-badge-deleted'
        ];

        $labels = [
            'created' => __('Created', 'wp-agency'),
            'updated' => __('Updated', 'wp-agency'),
            'deleted' => __('Deleted', 'wp-agency')
        ];

        $class = isset($classes[$event]) ? $classes[$event] : 'audit-badge-default';
        $label = isset($labels[$event]) ? $labels[$event] : ucfirst($event);

        return sprintf('<span class="audit-badge %s">%s</span>', $class, esc_html($label));
    }

    /**
     * Format datetime
     *
     * @param string $datetime MySQL datetime
     * @return string Formatted datetime
     */
    protected function format_datetime($datetime): string {
        if (empty($datetime)) {
            return '-';
        }

        return date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($datetime)
        );
    }

    /**
     * Generate action buttons
     *
     * @param object $row Database row
     * @return string HTML for action buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $audit_id = $row->id ?? 0;

        // View details button (opens modal with old/new comparison)
        $actions = sprintf(
            '<button type="button" class="button button-small view-audit-detail"
                data-id="%d"
                title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            $audit_id,
            __('View Details', 'wp-agency')
        );

        return $actions;
    }
}
