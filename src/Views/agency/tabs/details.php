<?php
/**
 * Agency Details Tab
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/details.php
 *
 * Description: Tab 1 - Agency details (immediate load).
 *              Displays agency information in right panel.
 *              Data loaded via get_agency_details AJAX action.
 *
 * Changelog:
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 4, Task 4.3)
 * - Immediate load pattern
 * - Display agency information fields
 */

defined('ABSPATH') || exit;

// This variable is passed by base panel system
// $data contains agency information from AJAX response
?>

<div class="wpapp-tab-content agency-details-content">
    <?php if (isset($data['agency'])):
        $agency = $data['agency'];
    ?>
        <div class="wpapp-details-grid">
            <!-- Basic Information -->
            <div class="wpapp-detail-section">
                <h3><?php esc_html_e('Basic Information', 'wp-agency'); ?></h3>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Code', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->code ?? '-'); ?></span>
                </div>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Name', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->name ?? '-'); ?></span>
                </div>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Status', 'wp-agency'); ?>:</label>
                    <span>
                        <?php
                        $status_class = ($agency->status ?? '') === 'active' ? 'success' : 'error';
                        $status_text = ($agency->status ?? '') === 'active'
                            ? __('Active', 'wp-agency')
                            : __('Inactive', 'wp-agency');
                        ?>
                        <span class="wpapp-badge wpapp-badge-<?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Location Information -->
            <div class="wpapp-detail-section">
                <h3><?php esc_html_e('Location', 'wp-agency'); ?></h3>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Province', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->province_name ?? '-'); ?></span>
                </div>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Regency/City', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->regency_name ?? '-'); ?></span>
                </div>

                <?php if (!empty($agency->pusat_address)): ?>
                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Address', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->pusat_address); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($agency->pusat_postal_code)): ?>
                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Postal Code', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->pusat_postal_code); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="wpapp-detail-section">
                <h3><?php esc_html_e('Statistics', 'wp-agency'); ?></h3>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Total Divisions', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->division_count ?? '0'); ?></span>
                </div>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Total Employees', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->employee_count ?? '0'); ?></span>
                </div>
            </div>

            <!-- Registration Information -->
            <div class="wpapp-detail-section">
                <h3><?php esc_html_e('Registration', 'wp-agency'); ?></h3>

                <?php if (!empty($agency->owner_name)): ?>
                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Owner', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->owner_name); ?></span>
                </div>
                <?php endif; ?>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Created By', 'wp-agency'); ?>:</label>
                    <span><?php echo esc_html($agency->created_by_name ?? '-'); ?></span>
                </div>

                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Created At', 'wp-agency'); ?>:</label>
                    <span>
                        <?php
                        if (!empty($agency->created_at)) {
                            echo esc_html(date_i18n(
                                get_option('date_format') . ' ' . get_option('time_format'),
                                strtotime($agency->created_at)
                            ));
                        } else {
                            echo '-';
                        }
                        ?>
                    </span>
                </div>

                <?php if (!empty($agency->updated_at) && $agency->updated_at !== $agency->created_at): ?>
                <div class="wpapp-detail-row">
                    <label><?php esc_html_e('Last Updated', 'wp-agency'); ?>:</label>
                    <span>
                        <?php
                        echo esc_html(date_i18n(
                            get_option('date_format') . ' ' . get_option('time_format'),
                            strtotime($agency->updated_at)
                        ));
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="wpapp-no-data">
            <p><?php esc_html_e('No agency data available', 'wp-agency'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.wpapp-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.wpapp-detail-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.wpapp-detail-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    color: #0073aa;
}

.wpapp-detail-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.wpapp-detail-row:last-child {
    border-bottom: none;
}

.wpapp-detail-row label {
    font-weight: 600;
    color: #555;
}

.wpapp-detail-row span {
    color: #333;
}

.wpapp-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}
</style>
