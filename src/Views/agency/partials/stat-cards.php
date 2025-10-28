<?php
/**
 * Stat Cards - Agency Statistics Display
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/stat-cards.php
 *
 * Description: Displays agency statistics cards in dashboard header.
 *              Uses LOCAL SCOPE (agency-*) classes only.
 *              Called by: AgencyDashboardController::render_header_cards()
 *
 * Context: Statistics cards fragment
 * Scope: LOCAL (agency-* prefix only)
 *
 * Variables available:
 * @var int $total Total agencies count
 * @var int $active Active agencies count
 * @var int $inactive Inactive agencies count
 *
 * Changelog:
 * 1.0.0 - 2025-10-27
 * - Initial creation
 * - Extracted from AgencyDashboardController
 * - Follows {context}-{identifier} naming convention
 * - Pure presentation layer (no business logic)
 */

defined('ABSPATH') || exit;

// Ensure variables exist
if (!isset($total, $active, $inactive)) {
    echo '<p>' . __('Statistics data not available', 'wp-agency') . '</p>';
    return;
}
?>

<div class="agency-statistics-cards" id="agency-statistics">
    <!-- Total Card -->
    <div class="agency-stat-card agency-theme-blue" data-card-id="total-agencies">
        <div class="agency-stat-icon">
            <span class="dashicons dashicons-building"></span>
        </div>
        <div class="agency-stat-content">
            <div class="agency-stat-number"><?php echo esc_html($total ?: '0'); ?></div>
            <div class="agency-stat-label"><?php _e('Total Disnaker', 'wp-agency'); ?></div>
        </div>
    </div>

    <!-- Active Card -->
    <div class="agency-stat-card agency-theme-green" data-card-id="active-agencies">
        <div class="agency-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="agency-stat-content">
            <div class="agency-stat-number"><?php echo esc_html($active ?: '0'); ?></div>
            <div class="agency-stat-label"><?php _e('Active', 'wp-agency'); ?></div>
        </div>
    </div>

    <!-- Inactive Card -->
    <div class="agency-stat-card agency-theme-orange" data-card-id="inactive-agencies">
        <div class="agency-stat-icon">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="agency-stat-content">
            <div class="agency-stat-number"><?php echo esc_html($inactive ?: '0'); ?></div>
            <div class="agency-stat-label"><?php _e('Inactive', 'wp-agency'); ?></div>
        </div>
    </div>
</div>
