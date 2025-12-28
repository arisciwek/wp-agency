<?php
/**
 * Audit Log History Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/AuditLog
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/audit-log/history-tab.php
 *
 * Description: History tab untuk agency detail page.
 *              Shows complete timeline: agency + divisions + employees.
 *              Uses DataTable for server-side processing.
 *
 * Variables Available:
 * - $agency_id: Agency ID untuk filter
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - DataTable with server-side processing
 * - Complete timeline (agency + related entities)
 */

if (!defined('ABSPATH')) {
    exit;
}

error_log('[History Tab Template] Template loaded');
error_log('[History Tab Template] $agency: ' . print_r($agency ?? null, true));

// Get agency_id from $agency object (extracted by controller)
$agency_id = isset($agency->id) ? intval($agency->id) : 0;

error_log('[History Tab Template] Agency ID: ' . $agency_id);

if (empty($agency_id)) {
    error_log('[History Tab Template] ERROR - Empty agency_id');
    echo '<p>' . esc_html__('Invalid agency ID', 'wp-agency') . '</p>';
    return;
}
?>

<div class="wrap">
    <div id="audit-log-container" class="wpapp-datatable-container">
        <table id="audit-log-datatable" class="wp-list-table widefat fixed striped" data-agency-id="<?php echo esc_attr($agency_id); ?>">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'wp-agency'); ?></th>
                    <th><?php _e('Entity', 'wp-agency'); ?></th>
                    <th><?php _e('Event', 'wp-agency'); ?></th>
                    <th><?php _e('Changes', 'wp-agency'); ?></th>
                    <th><?php _e('User', 'wp-agency'); ?></th>
                    <th><?php _e('Actions', 'wp-agency'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="dataTables_empty"><?php _e('Loading...', 'wp-agency'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
