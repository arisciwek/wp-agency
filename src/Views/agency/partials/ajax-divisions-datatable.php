<?php
/**
 * AJAX Divisions DataTable - Lazy-loaded DataTable HTML
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/ajax-divisions-datatable.php
 *
 * Description: Generates DataTable HTML for divisions lazy-load.
 *              Returned via AJAX when divisions tab is clicked.
 *              Called by: AgencyDashboardController::handle_load_divisions_tab()
 *
 * Context: AJAX response (lazy-load)
 * Scope: MIXED (wpapp-* for DataTable structure)
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
 * 1.1.0 - 2025-10-27
 * - REMOVED: Inline <script> tag (26 lines)
 * - ADDED: data-* attributes for configuration
 * - MOVED: Initialization logic to agency-datatable.js
 * - Pure HTML template (no JS)
 *
 * 1.0.0 - 2025-10-27
 * - Initial creation
 * - Extracted from AgencyDashboardController
 * - Follows {context}-{identifier} naming convention
 */

defined('ABSPATH') || exit;

// Ensure $agency_id exists
if (!isset($agency_id)) {
    echo '<p>' . __('Agency ID not available', 'wp-agency') . '</p>';
    return;
}
?>

<table id="divisions-datatable"
       class="wpapp-datatable agency-lazy-datatable"
       style="width:100%"
       data-entity="division"
       data-agency-id="<?php echo esc_attr($agency_id); ?>"
       data-ajax-action="get_divisions_datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Code', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Name', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Type', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Status', 'wp-agency'); ?></th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTable will populate via AJAX -->
    </tbody>
</table>
