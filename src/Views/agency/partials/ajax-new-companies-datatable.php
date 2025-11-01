<?php
/**
 * AJAX New Companies DataTable - Lazy-loaded DataTable HTML
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/ajax-new-companies-datatable.php
 *
 * Description: Generates DataTable HTML for new companies lazy-load.
 *              Returned via AJAX when new-companies tab is clicked.
 *              Called by: AgencyDashboardController::handle_load_new_companies_tab()
 *              Shows branches without inspector (inspector_id IS NULL).
 *
 * Context: AJAX response (lazy-load)
 * Scope: MIXED (wpapp-* for DataTable structure, agency-* for local)
 *
 * Variables available:
 * @var int $agency_id Agency ID for DataTable filtering
 *
 * Initialization:
 * - DataTable initialized by agency-datatable.js (event-driven)
 * - Uses data-* attributes for configuration
 * - No inline JavaScript (pure HTML only)
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-3097)
 * - Initial implementation
 * - Following divisions/employees DataTable pattern
 * - Columns: Kode, Perusahaan, Unit, Yuridiksi, Aksi
 * - Pure HTML template (no JS)
 * - Action buttons with permissions check
 */

defined('ABSPATH') || exit;

// Ensure $agency_id exists
if (!isset($agency_id)) {
    echo '<p>' . __('Agency ID not available', 'wp-agency') . '</p>';
    return;
}
?>

<table id="new-companies-datatable"
       class="wpapp-datatable agency-lazy-datatable"
       style="width:100%"
       data-entity="new-company"
       data-agency-id="<?php echo esc_attr($agency_id); ?>"
       data-ajax-action="get_new_companies_datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Kode', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Perusahaan', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Unit', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Yuridiksi', 'wp-agency'); ?></th>
            <th class="text-center no-sort">
                <?php esc_html_e('Aksi', 'wp-agency'); ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTable will populate via AJAX -->
    </tbody>
</table>
