<?php
/**
 * Agency Status Filter Partial
 *
 * @package     WP_Agency
 * @subpackage  Views/DataTable/Templates/Partials
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/DataTable/Templates/partials/status-filter.php
 *
 * Description: Partial template untuk status filter.
 *              Scope local - uses agency-* CSS classes.
 *              Requires edit_all_agencies capability.
 *
 * Changelog:
 * 1.0.3 - 2025-10-25
 * - Moved from /Views/agency/partials/ to /Views/DataTable/Templates/partials/
 * - Updated path to match DataTable structure
 * - Updated subpackage to Views/DataTable/Templates/Partials
 * 1.0.2 - 2025-10-24
 * - Removed: All inline styles (<style> tag) moved to agency-filter.css
 * - Removed: All inline scripts (<script> tag) moved to agency-filter.js
 * - Clean template: Only PHP and HTML
 * 1.0.1 - 2025-10-24
 * - Fixed: Permission check now wraps content, not early return
 * - Container navigation always renders, filter card is conditional
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 * - Select list for status filter (all, active, inactive)
 * - Default: active
 */

defined('ABSPATH') || exit;

// Get current status filter from GET parameter
$current_status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'active';

// Check permission - users with edit_all_agencies or manage_options can see filter
$can_filter = current_user_can('edit_all_agencies') || current_user_can('manage_options');
?>

<?php if ($can_filter): ?>
<div class="agency-status-filter-group">
    <label for="agency-status-filter" class="agency-filter-label">
        <?php esc_html_e('Filter Status:', 'wp-agency'); ?>
    </label>
    <select id="agency-status-filter" class="agency-filter-select" data-current="<?php echo esc_attr($current_status); ?>">
        <option value="all" <?php selected($current_status, 'all'); ?>>
            <?php esc_html_e('Semua Status', 'wp-agency'); ?>
        </option>
        <option value="active" <?php selected($current_status, 'active'); ?>>
            <?php esc_html_e('Aktif', 'wp-agency'); ?>
        </option>
        <option value="inactive" <?php selected($current_status, 'inactive'); ?>>
            <?php esc_html_e('Tidak Aktif', 'wp-agency'); ?>
        </option>
    </select>
</div>
<?php endif; ?>
