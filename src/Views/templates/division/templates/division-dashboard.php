<?php
/**
 * Agency Dashboard Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency-division/src/Views/templates/division/templates/division-dashboard.php
 *
 * Description: Main dashboard template untuk manajemen division.
 *              Includes statistics overview, DataTable listing,
 *              right panel details, dan modal forms.
 *              Mengatur layout dan component integration.
 *
 * Changelog:
 * 1.0.1 - 2024-12-05
 * - Added edit form modal integration
 * - Updated form templates loading
 * - Improved modal management
 *
 * 1.0.0 - 2024-12-03
 * - Initial dashboard implementation
 * - Added statistics display
 * - Added agency listing
 * - Added panel navigation
 */

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Dashboard Section -->
    <div class="wp-agency-division-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik WP</h2>
                    <div class="wi-stats-container">
                        <div class="wi-stat-box agency-stats">
                            <h3>Total Agency</h3>
                            <p class="wi-stat-number"><span id="total-agencies">0</span></p>
                        </div>
                        <div class="wi-stat-box">
                            <h3>Total Division</h3>
                            <p class="wi-stat-number" id="total-divisiones">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-agency-division-content-area">
        <div id="wp-agency-division-main-container" class="wp-agency-division-container">
            <!-- Left Panel -->
            <?php require_once WP_AGENCY_PATH . 'src/Views/templates/division/templates/division-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-agency-division-right-panel" class="wp-agency-division-right-panel hidden">
                <?php require_once WP_AGENCY_PATH . 'src/Views/templates/division/templates/division-right-panel.php'; ?>
            </div>
        </div>
    </div>

    <!-- Modal Forms -->
    <?php
    require_once WP_AGENCY_PATH . 'src/Views/components/confirmation-modal.php';
    ?>
    <!-- Modal Templates -->
    <?php
    if (function_exists('wp_agency_render_confirmation_modal')) {
        wp_agency_render_confirmation_modal();
    }
    ?>
