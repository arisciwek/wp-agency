<?php
/**
 * Agency Divisions Tab - Pure View Pattern (Inner Content Only)
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     3.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/divisions.php
 *
 * Description: Pure HTML inner content for lazy-loaded divisions tab.
 *              Outer wrapper provided by TabSystemTemplate.
 *              This template only provides INNER content (no outer div).
 *              Classes/attributes added directly to outer div via JS.
 *
 * Pattern: Inner Content Only (TODO-3092 Fix)
 * - Outer wrapper: Created by TabSystemTemplate (wp-app-core)
 * - This file: Only inner HTML content
 * - Classes: Added via JS after content inject
 * - Lazy-load: Triggered by wpdt-tab-manager.js
 *
 * Changelog:
 * 3.1.0 - 2025-10-31 (TODO-3092)
 * - FIX: Removed outer div wrapper
 * - REASON: Prevents class duplication when .html() replaces inner content
 * - PATTERN: Outer div provided by TabSystemTemplate, classes added via JS
 * - Classes moved to JS: wpdt-tab-autoload, data-attributes
 *
 * 3.0.0 - 2025-10-28 (TODO-3084 Review-02)
 * - SIMPLIFIED: Merged with tab-divisions-content.php
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

// Note: This template provides INNER content only
// Outer <div id="divisions" class="wpdt-tab-content"> is created by TabSystemTemplate
// Classes and data attributes are added via JavaScript after content injection
?>
<!-- Inner content for divisions tab (TODO-3092) -->
<div class="wpdt-divisions-tab wpdt-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_divisions_tab"
     data-content-target=".wpdt-divisions-content"
     data-error-message="<?php esc_attr_e('Failed to load divisions', 'wp-agency'); ?>">

    <div class="wpdt-tab-header">
        <h3><?php esc_html_e('Daftar Divisi', 'wp-agency'); ?></h3>
    </div>

    <div class="wpdt-tab-loading">
        <p><?php esc_html_e('Memuat data divisi...', 'wp-agency'); ?></p>
    </div>

    <div class="wpdt-divisions-content wpdt-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpdt-tab-manager.js -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
