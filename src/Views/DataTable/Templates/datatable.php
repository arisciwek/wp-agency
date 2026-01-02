<?php
/**
 * Agency DataTable View
 *
 * @package     WP_Agency
 * @subpackage  Views/DataTable/Templates
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/DataTable/Templates/datatable.php
 *
 * Description: DataTable HTML structure untuk agencies list.
 *              Rendered in left panel via wpapp_left_panel_content hook.
 *              Uses server-side processing with AgencyDataTableModel.
 *
 * Changelog:
 * 1.0.3 - 2025-10-25
 * - Removed inline JavaScript (moved to agency-datatable.js)
 * - Template now contains HTML only (best practice)
 * - Separation of concerns: PHP template vs JavaScript
 * 1.0.2 - 2025-10-25
 * - Changed wpdt-datatable-wrapper to agency-datatable-wrapper (local scope)
 * - Following wp-customer pattern (wpdt-companies-list-container)
 * - Provides own wrapper styling instead of relying on global wpdt-panel-content
 * 1.0.1 - 2025-10-25
 * - Moved from /Views/agency/ to /Views/DataTable/Templates/
 * - Updated path to match DataTable structure
 * - Updated subpackage to Views/DataTable/Templates
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 4, Task 4.2)
 * - DataTable with 6 columns
 * - Server-side processing via get_agencies_datatable
 * - Integrated with base panel system
 */

defined('ABSPATH') || exit;
?>

<div class="agency-datatable-wrapper">
    <table id="agency-datatable" class="wpdt-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Code', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Nama Disnaker', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Provinsi', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Kabupaten/Kota', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Actions', 'wp-agency'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate via AJAX -->
        </tbody>
    </table>
</div>
<!-- JavaScript handled by agency-datatable.js -->
