<?php
/**
 * Agency New Companies Tab - Pure View Pattern (Inner Content Only)
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/new-companies.php
 *
 * Description: Pure HTML inner content for lazy-loaded new companies tab.
 *              Outer wrapper provided by TabSystemTemplate.
 *              This template only provides INNER content (no outer div).
 *              Classes/attributes added directly to outer div via JS.
 *
 * Pattern: Inner Content Only (Following TODO-3092 Pattern)
 * - Outer wrapper: Created by TabSystemTemplate (wp-app-core)
 * - This file: Only inner HTML content
 * - Classes: Added via JS after content inject
 * - Lazy-load: Triggered by wpdt-tab-manager.js
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-3097)
 * - Initial implementation
 * - Following divisions/employees tab pattern
 * - Lazy-load DataTable for branches without inspector
 * - Pure HTML view, no controller logic
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
// Outer <div id="new-companies" class="wpdt-tab-content"> is created by TabSystemTemplate
// Classes and data attributes are added via JavaScript after content injection
?>
<!-- Inner content for new companies tab (TODO-3097) -->
<div class="wpdt-new-companies-tab wpdt-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_new_companies_tab"
     data-content-target=".wpdt-new-companies-content"
     data-error-message="<?php esc_attr_e('Failed to load new companies', 'wp-agency'); ?>">

    <div class="wpdt-tab-header">
        <h3><?php esc_html_e('Perusahaan Baru', 'wp-agency'); ?></h3>
        <p class="description"><?php esc_html_e('Daftar perusahaan yang belum memiliki pengawas', 'wp-agency'); ?></p>
    </div>

    <div class="wpdt-tab-loading">
        <p><?php esc_html_e('Memuat data perusahaan...', 'wp-agency'); ?></p>
    </div>

    <div class="wpdt-new-companies-content wpdt-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpdt-tab-manager.js -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
