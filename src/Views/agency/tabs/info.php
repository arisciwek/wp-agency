<?php
/**
 * Agency Info Tab
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/info.php
 *
 * Description: Detail information tab untuk agency.
 *              Menampilkan informasi lengkap agency.
 *
 * Changelog:
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 */

defined('ABSPATH') || exit;

// $agency variable is passed from controller
if (!isset($agency)) {
    echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
    return;
}
?>

<div class="wpapp-info-container">
    <div class="wpapp-info-section">
        <h3><?php _e('Informasi Umum', 'wp-agency'); ?></h3>
        <div class="wpapp-info-grid">
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Kode', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value"><?php echo esc_html($agency->code ?? '-'); ?></span>
            </div>
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Nama', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value"><?php echo esc_html($agency->name ?? '-'); ?></span>
            </div>
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Status', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value">
                    <?php
                    $status = $agency->status ?? 'inactive';
                    $badge_class = $status === 'active' ? 'success' : 'secondary';
                    $status_label = $status === 'active' ? __('Aktif', 'wp-agency') : __('Tidak Aktif', 'wp-agency');
                    ?>
                    <span class="wpapp-badge wpapp-badge-<?php echo esc_attr($badge_class); ?>">
                        <?php echo esc_html($status_label); ?>
                    </span>
                </span>
            </div>
        </div>
    </div>

    <div class="wpapp-info-section">
        <h3><?php _e('Lokasi', 'wp-agency'); ?></h3>
        <div class="wpapp-info-grid">
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Provinsi', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value"><?php echo esc_html($agency->provinsi_name ?? '-'); ?></span>
            </div>
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Kabupaten/Kota', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value"><?php echo esc_html($agency->regency_name ?? '-'); ?></span>
            </div>
        </div>
    </div>

    <div class="wpapp-info-section">
        <h3><?php _e('Metadata', 'wp-agency'); ?></h3>
        <div class="wpapp-info-grid">
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Dibuat', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value">
                    <?php
                    if (isset($agency->created_at)) {
                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($agency->created_at)));
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>
            <div class="wpapp-info-item">
                <span class="wpapp-info-label"><?php _e('Diubah', 'wp-agency'); ?>:</span>
                <span class="wpapp-info-value">
                    <?php
                    if (isset($agency->updated_at)) {
                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($agency->updated_at)));
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>
<!-- /wpapp-info-container -->
