<?php
/**
 * Agency Employees Tab
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/employees.php
 *
 * Description: Tab untuk menampilkan daftar pegawai dari agency.
 *              Data di-load via AJAX untuk lazy loading.
 *              Uses event-driven approach - no inline scripts.
 *
 * Changelog:
 * 1.1.0 - 2025-10-27
 * - REMOVED: Inline CSS styles
 * - REMOVED: Inline <script> tag
 * - ADDED: Data attributes for external JS handling
 * - Uses wpapp-tab-autoload class for automatic loading
 *
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 */

defined('ABSPATH') || exit;

// $agency_id is passed from controller
if (!isset($agency_id)) {
    echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
    return;
}
?>

<div class="wpapp-tab-content wpapp-employees-tab wpapp-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_employees_tab"
     data-content-target=".wpapp-employees-content"
     data-error-message="<?php echo esc_attr(__('Failed to load employees', 'wp-agency')); ?>">

    <div class="wpapp-tab-header">
        <h3><?php _e('Daftar Pegawai', 'wp-agency'); ?></h3>
    </div>

    <div class="wpapp-tab-loading">
        <p><?php _e('Memuat data pegawai...', 'wp-agency'); ?></p>
    </div>

    <div class="wpapp-employees-content wpapp-tab-loaded-content">
        <!-- Content will be loaded via AJAX by wpapp-tab-manager.js -->
    </div>

    <div class="wpapp-tab-error">
        <p class="wpapp-error-message"></p>
    </div>
</div>
