<?php
/**
 * Template for unauthorized access page
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: src/Views/templates/agency/agency-no-access.php
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wp-agency-no-access">
    <!-- Header section -->
    <div class="wp-agency-header">
        <h1><?php _e('WP Agency', 'wp-agency'); ?></h1>
    </div>

    <!-- Error message card -->
    <div class="wp-agency-error-card">
        <div class="error-icon">
            <span class="dashicons dashicons-lock"></span>
        </div>
        
        <h2><?php _e('Akses Dibatasi', 'wp-agency'); ?></h2>
        
        <div class="error-message">
            <p><?php _e('Anda tidak memiliki akses ke data agency. Silahkan hubungi administrator untuk informasi lebih lanjut.', 'wp-agency'); ?></p>
        </div>

        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Kembali ke Dashboard', 'wp-agency'); ?>
            </a>
            <?php if (current_user_can('view_agency_list')): ?>
            <a href="<?php echo admin_url('admin.php?page=wp-agency'); ?>" class="button button-primary">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('Lihat Daftar Agency', 'wp-agency'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Optional stats if user has view permission -->
    <?php if (current_user_can('view_agency_list')): ?>
    <div class="wp-agency-stats">
        <div class="wp-agency-stat-card">
            <div class="stat-header">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php _e('Total Agency', 'wp-agency'); ?></h3>
            </div>
            <p class="agency-count">0</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Custom styles for no-access page */
.wp-agency-no-access {
    max-width: 960px;
    margin: 20px auto;
    padding: 0 20px;
}

.wp-agency-header {
    margin-bottom: 30px;
}

.wp-agency-error-card {
    background: #fff;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.error-icon {
    margin-bottom: 20px;
}

.error-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #dc3232;
}

.wp-agency-error-card h2 {
    font-size: 24px;
    margin: 0 0 20px;
    color: #23282d;
}

.error-message {
    color: #666;
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.action-buttons .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    height: auto;
}

.action-buttons .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.wp-agency-stats {
    margin-top: 40px;
}

.wp-agency-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-header .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.stat-header h3 {
    margin: 0;
    font-size: 16px;
    color: #23282d;
}

.agency-count {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    margin: 0;
}
</style>
