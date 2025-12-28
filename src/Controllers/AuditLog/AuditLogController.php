<?php
/**
 * Audit Log Controller
 *
 * @package     WP_Agency
 * @subpackage  Controllers/AuditLog
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/AuditLog/AuditLogController.php
 *
 * Description: Controller untuk audit log DataTable AJAX requests.
 *              Handles get_audit_logs AJAX action.
 *              Returns JSON response untuk DataTable.
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - AJAX handler for audit log DataTable
 * - Supports customer + related entities filtering
 */

namespace WPAgency\Controllers\AuditLog;

use WPAgency\Models\AuditLog\AuditLogDataTableModel;

defined('ABSPATH') || exit;

class AuditLogController {

    private $model;

    public function __construct() {
        error_log('[AuditLog] Constructor called');
        $this->model = new AuditLogDataTableModel();
        error_log('[AuditLog] Model initialized');
        // Note: AJAX handlers registered externally in wp-agency.php
        // to ensure early registration before AJAX requests
    }

    /**
     * Handle get audit logs AJAX request
     */
    public function handleGetAuditLogs(): void {
        error_log('[AuditLog] handleGetAuditLogs called');
        error_log('[AuditLog] POST data: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_agency_ajax_nonce')) {
            error_log('[AuditLog] Nonce check failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
        }

        error_log('[AuditLog] Nonce verified');

        // Check permissions (user must be logged in at minimum)
        if (!is_user_logged_in()) {
            error_log('[AuditLog] User not logged in');
            wp_send_json_error(['message' => __('Unauthorized', 'wp-agency')]);
        }

        error_log('[AuditLog] User logged in: ' . get_current_user_id());

        try {
            // Get DataTable parameters
            $request_data = [
                'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                'start' => isset($_POST['start']) ? intval($_POST['start']) : 0,
                'length' => isset($_POST['length']) ? intval($_POST['length']) : 10,
                'search' => isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '',
                'order' => isset($_POST['order']) ? $_POST['order'] : [],
                'columns' => isset($_POST['columns']) ? $_POST['columns'] : [],

                // Custom parameters
                'agency_id' => isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0,
            ];

            error_log('[AuditLog] Request data: ' . print_r($request_data, true));

            // Validate agency_id
            if (empty($request_data['agency_id'])) {
                error_log('[AuditLog] ERROR - Empty agency_id');
                wp_send_json_error(['message' => __('Agency ID required', 'wp-agency')]);
            }

            error_log('[AuditLog] Agency ID: ' . $request_data['agency_id']);

            // Permission check: User can only view audit logs for entities they have access to
            // This is handled by RoleBasedFilter in the DataTableModel
            // Admin sees all, agency_admin sees their agency only

            // Get data from model
            error_log('[AuditLog] Calling model->get_datatable_data()');
            $response = $this->model->get_datatable_data($request_data);

            error_log('[AuditLog] Response: ' . print_r($response, true));
            error_log('[AuditLog] Total records: ' . ($response['recordsTotal'] ?? 0));
            error_log('[AuditLog] Filtered records: ' . ($response['recordsFiltered'] ?? 0));
            error_log('[AuditLog] Data count: ' . count($response['data'] ?? []));

            wp_send_json($response);

        } catch (\Exception $e) {
            error_log('[AuditLog] Exception: ' . $e->getMessage());
            error_log('[AuditLog] Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-agency'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle view audit detail (for modal)
     */
    public function handleViewAuditDetail(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_agency_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'wp-agency')]);
        }

        try {
            $audit_id = isset($_POST['audit_id']) ? intval($_POST['audit_id']) : 0;

            if (empty($audit_id)) {
                wp_send_json_error(['message' => __('Invalid audit ID', 'wp-agency')]);
            }

            global $wpdb;
            $audit_log = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}app_customer_audit_logs WHERE id = %d",
                $audit_id
            ));

            if (!$audit_log) {
                wp_send_json_error(['message' => __('Audit log not found', 'wp-agency')]);
            }

            // Decode JSON values
            $old_values = !empty($audit_log->old_values) ? json_decode($audit_log->old_values, true) : [];
            $new_values = !empty($audit_log->new_values) ? json_decode($audit_log->new_values, true) : [];

            // Permission check: User can only view audit logs they have access to
            // TODO: Add permission validation based on auditable_type and auditable_id

            wp_send_json_success([
                'audit_log' => [
                    'id' => $audit_log->id,
                    'auditable_type' => $audit_log->auditable_type,
                    'auditable_id' => $audit_log->auditable_id,
                    'event' => $audit_log->event,
                    'old_values' => $old_values,
                    'new_values' => $new_values,
                    'user_id' => $audit_log->user_id,
                    'ip_address' => $audit_log->ip_address,
                    'created_at' => $audit_log->created_at
                ]
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-agency'), $e->getMessage())
            ]);
        }
    }
}
