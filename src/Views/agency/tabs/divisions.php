<?php
/**
 * Agency Divisions Tab
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/divisions.php
 *
 * Description: Tab untuk menampilkan daftar divisi dari agency.
 *              Data di-load via AJAX untuk lazy loading.
 *
 * Changelog:
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

<div class="wpapp-tab-content wpapp-divisions-tab" data-agency-id="<?php echo esc_attr($agency_id); ?>">
    <div class="wpapp-tab-header">
        <h3><?php _e('Daftar Divisi', 'wp-agency'); ?></h3>
    </div>

    <div class="wpapp-loading" style="text-align: center; padding: 20px; color: #666;">
        <p><?php _e('Memuat data divisi...', 'wp-agency'); ?></p>
    </div>

    <div class="wpapp-divisions-content" style="display: none;">
        <!-- Content will be loaded via AJAX -->
    </div>

    <div class="wpapp-error" style="display: none;">
        <p class="wpapp-error-message"></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load divisions data via AJAX
    var $tab = $('.wpapp-divisions-tab');
    var agencyId = $tab.data('agency-id');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'load_divisions_tab',
            nonce: wpAppConfig.nonce,
            agency_id: agencyId
        },
        success: function(response) {
            $tab.find('.wpapp-loading').hide();

            if (response.success) {
                $tab.find('.wpapp-divisions-content')
                    .html(response.data.html)
                    .show();
            } else {
                $tab.find('.wpapp-error-message').text(response.data.message || 'Unknown error');
                $tab.find('.wpapp-error').show();
            }
        },
        error: function() {
            $tab.find('.wpapp-loading').hide();
            $tab.find('.wpapp-error-message').text('<?php _e('Failed to load divisions', 'wp-agency'); ?>');
            $tab.find('.wpapp-error').show();
        }
    });
});
</script>
