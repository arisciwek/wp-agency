<?php
/**
 * AJAX Divisions DataTable - Lazy-loaded DataTable HTML
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/ajax-divisions-datatable.php
 *
 * Description: Generates DataTable HTML for divisions lazy-load.
 *              Returned via AJAX when divisions tab is clicked.
 *              Called by: AgencyDashboardController::handle_load_divisions_tab()
 *              Includes status filter for users with edit permissions.
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
 * 1.2.0 - 2025-10-31 (TODO-3092 Review-02)
 * - ADDED: Status filter dropdown with permission check
 * - UPDATED: Columns: Kode, Nama Unit Kerja, Wilayah Kerja
 * - PERMISSION: Filter visible if edit_all_divisions OR edit_own_division
 *
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

// Check permission for status filter - users with edit permissions can see filter
$can_filter = current_user_can('edit_all_divisions') || current_user_can('edit_own_division');
?>

<?php if ($can_filter): ?>
<div class="agency-division-filter-group" style="margin-bottom: 15px;">
    <label for="division-status-filter" class="agency-filter-label" style="margin-right: 10px; font-weight: 500;">
        <?php esc_html_e('Filter Status:', 'wp-agency'); ?>
    </label>
    <select id="division-status-filter" class="agency-filter-select" style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 3px;">
        <option value="">
            <?php esc_html_e('Semua Status', 'wp-agency'); ?>
        </option>
        <option value="active" selected>
            <?php esc_html_e('Aktif', 'wp-agency'); ?>
        </option>
        <option value="inactive">
            <?php esc_html_e('Tidak Aktif', 'wp-agency'); ?>
        </option>
    </select>
</div>
<?php endif; ?>

<table id="divisions-datatable"
       class="wpapp-datatable agency-lazy-datatable"
       style="width:100%"
       data-entity="division"
       data-agency-id="<?php echo esc_attr($agency_id); ?>"
       data-ajax-action="get_divisions_datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Kode', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Nama Unit Kerja', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Wilayah Kerja', 'wp-agency'); ?></th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTable will populate via AJAX -->
    </tbody>
</table>
