<?php
/**
 * Tab pengaturan umum
 *
 * @package     WPAgency
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Description:
 * - Pengaturan umum untuk plugin WP Agency
 * - Konfigurasi datatable dan cache
 * - Pengaturan realtime updates menggunakan Pusher
 * 
 * Path: /wp-agency/src/Views/templates/settings/tab-general.php
 * 
 * Changelog:
 * v1.0.0 - 2024-01-07
 * - Initial version
 * - Add datatable settings
 * - Add cache settings 
 * - Add realtime update settings
 */
if (!defined('ABSPATH')) {
    die;
}

$options = get_option('wp_agency_settings', array(
    'datatables_page_length' => 25,
    'enable_cache' => false,
    'cache_duration' => 3600,
    'enable_debug' => false,
    'enable_pusher' => false,
    'pusher_app_key' => '',
    'pusher_app_secret' => '',
    'pusher_cluster' => 'ap1'
));
?>

<form method="post" action="options.php">
    <?php settings_fields('wp_agency_settings'); ?>
    
    <h3><?php _e('Data Per Halaman', 'wp-agency'); ?></h3>
    <select name="wp_agency_settings[datatables_page_length]">
        <option value="10" <?php selected($options['datatables_page_length'], 10); ?>>10</option>
        <option value="25" <?php selected($options['datatables_page_length'], 25); ?>>25</option>
        <option value="50" <?php selected($options['datatables_page_length'], 50); ?>>50</option>
        <option value="100" <?php selected($options['datatables_page_length'], 100); ?>>100</option>
    </select>

    <h3><?php _e('Pengaturan Cache', 'wp-agency'); ?></h3>
    <p>
        <label>
            <input type="checkbox" 
                   name="wp_agency_settings[enable_cache]" 
                   value="1" 
                   <?php checked($options['enable_cache'], 1); ?>>
            <?php _e('Aktifkan caching', 'wp-agency'); ?>
        </label>
    </p>
    
    <p>
        <label>
            <?php _e('Durasi Cache (detik):', 'wp-agency'); ?>
            <input type="number" 
                   name="wp_agency_settings[cache_duration]" 
                   value="<?php echo esc_attr($options['cache_duration']); ?>" 
                   min="60" 
                   step="60">
        </label>
    </p>

    <p>
        <button type="button" 
                id="clear-all-cache" 
                class="button button-secondary">
            <?php _e('Clear All Cache', 'wp-agency'); ?>
        </button>
        <span id="cache-clear-status" class="cache-status"></span>
    </p>

    <h3><?php _e('Mode Debug', 'wp-agency'); ?></h3>
    <p>
        <label>
            <input type="checkbox" 
                   name="wp_agency_settings[enable_debug]" 
                   value="1" 
                   <?php checked($options['enable_debug'], 1); ?>>
            <?php _e('Aktifkan mode debug', 'wp-agency'); ?>
        </label>
    </p>

    <h3><?php _e('Realtime Updates Configuration', 'wp-agency'); ?></h3>
    <p>
        <label><?php _e('Enable Realtime Updates', 'wp-agency'); ?></label><br>
        <label>
            <input type="checkbox" 
                   name="wp_agency_settings[enable_pusher]" 
                   value="1" 
                   <?php checked($options['enable_pusher'], 1); ?>>
            <?php _e('Enable Pusher integration', 'wp-agency'); ?>
        </label>
    </p>

    <p>
        <label><?php _e('API Key', 'wp-agency'); ?></label><br>
        <input type="text" 
               name="wp_agency_settings[pusher_app_key]"
               value="<?php echo esc_attr($options['pusher_app_key']); ?>"
               class="regular-text">
    </p>

    <p>
        <label><?php _e('API Secret', 'wp-agency'); ?></label><br>
        <input type="password" 
               name="wp_agency_settings[pusher_app_secret]"
               value="<?php echo esc_attr($options['pusher_app_secret']); ?>"
               class="regular-text">
    </p>

    <p>
        <label><?php _e('Cluster', 'wp-agency'); ?></label><br>
        <select name="wp_agency_settings[pusher_cluster]"
                class="regular-text">
            <option value="ap1" <?php selected($options['pusher_cluster'], 'ap1'); ?>>ap1 (Asia Pacific)</option>
            <option value="ap2" <?php selected($options['pusher_cluster'], 'ap2'); ?>>ap2 (Asia Pacific 2)</option>
            <option value="us2" <?php selected($options['pusher_cluster'], 'us2'); ?>>us2 (US East Coast)</option>
            <option value="us3" <?php selected($options['pusher_cluster'], 'us3'); ?>>us3 (US West Coast)</option>
            <option value="eu" <?php selected($options['pusher_cluster'], 'eu'); ?>>eu (Europe)</option>
        </select>
    </p>

    <?php submit_button(__('Simpan Perubahan', 'wp-agency')); ?>
</form>
