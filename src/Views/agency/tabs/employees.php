<?php
/**
 * Agency Employees Tab - Pure View Pattern
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/employees.php
 *
 * Description: Pure HTML view for lazy-loaded employees tab.
 *              Direct template - no controller logic, no hooks, no partials.
 *              Actual DataTable content loaded via AJAX.
 *              Uses wpapp-* structure classes (GLOBAL) for autoload system.
 *
 * Pattern: Simple and Direct (Lazy-Load)
 * - This file: Pure HTML placeholder template
 * - Variables: $agency passed directly from controller
 * - Lazy-load: Content loaded by wpapp-tab-manager.js
 * - Scope: MIXED (wpapp-* for structure, agency-* for custom)
 *
 * Changelog:
 * 3.0.0 - 2025-10-28 (TODO-3084 Review-02)
 * - SIMPLIFIED: Merged with tab-employees-content.php
 * - REMOVED: TabViewTemplate wrapper (controller-like logic)
 * - REMOVED: Hook-based content injection
 * - PATTERN: Pure MVC View - direct HTML template
 * - Single file per tab (no partials needed)
 *
 * 2.0.0 - 2025-10-28
 * - ARCHITECTURE: Migrated to TabViewTemplate pattern (hook-based)
 *
 * 1.1.0 - 2025-10-27
 * - REMOVED: Inline CSS styles
 * - REMOVED: Inline <script> tag
 * - ADDED: Data attributes for external JS handling
 *
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 */

defined('ABSPATH') || exit;

// $agency variable is passed from controller
if (!isset($agency)) {
    echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
    return;
}

$agency_id = $agency->id ?? 0;

if (!$agency_id) {
    echo '<p>' . __('Agency ID not available', 'wp-agency') . '</p>';
    return;
}
?>

<div class="wpapp-tab-content wpapp-employees-tab wpapp-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_employees_tab"
     data-content-target=".wpapp-employees-content"
     data-error-message="<?php esc_attr_e('Failed to load employees', 'wp-agency'); ?>">

    <div class="wpapp-tab-header">
        <h3><?php esc_html_e('Daftar Pegawai', 'wp-agency'); ?></h3>
    </div>

    <div class="wpapp-tab-loading">
        <p><?php esc_html_e('Memuat data pegawai...', 'wp-agency'); ?></p>
    </div>

    <div class="wpapp-employees-content wpapp-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpapp-tab-manager.js -->
    </div>

    <div class="wpapp-tab-error">
        <p class="wpapp-error-message"></p>
    </div>
</div>
