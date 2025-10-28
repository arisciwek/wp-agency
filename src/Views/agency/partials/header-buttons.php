<?php
/**
 * Header Buttons - Agency Action Buttons
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/header-buttons.php
 *
 * Description: Displays action buttons in dashboard header (Print, Export, Add).
 *              Uses LOCAL SCOPE (agency-*) classes only.
 *              Called by: AgencyDashboardController::render_header_buttons()
 *
 * Context: Header component
 * Scope: LOCAL (agency-* prefix only)
 *
 * Variables available:
 * - None (uses current_user_can checks)
 *
 * Changelog:
 * 1.0.0 - 2025-10-27
 * - Initial creation
 * - Extracted from AgencyDashboardController
 * - Follows {context}-{identifier} naming convention
 * - Pure presentation layer with permission checks
 */

defined('ABSPATH') || exit;
?>

<div class="agency-header-buttons">
    <?php if (current_user_can('view_agency_list')): ?>
        <button type="button" class="button agency-print-btn" id="agency-print-btn">
            <span class="dashicons dashicons-printer"></span>
            <?php _e('Print', 'wp-agency'); ?>
        </button>

        <button type="button" class="button agency-export-btn" id="agency-export-btn">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Export', 'wp-agency'); ?>
        </button>
    <?php endif; ?>

    <?php if (current_user_can('add_agency')): ?>
        <a href="#" class="button button-primary agency-add-btn">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Tambah Disnaker', 'wp-agency'); ?>
        </a>
    <?php endif; ?>
</div>
