<?php
/**
 * Agency Data Tab - Pure View Pattern (Merged info.php + details.php)
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Tabs
 * @version     4.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/tabs/info.php
 *
 * Description: Pure HTML view for comprehensive agency information tab.
 *              Direct template - no controller logic, no hooks, no partials.
 *              Follows true MVC View pattern.
 *              Merged from info.php (basic) and details.php (comprehensive).
 *
 * Pattern: Simple and Direct
 * - This file: Pure HTML template
 * - Variables: $agency passed directly from controller
 * - Scope: LOCAL (agency-* classes)
 *
 * Changelog:
 * 4.0.0 - 2025-10-28 (TODO-3084 Final)
 * - MERGED: Combined info.php and details.php into single tab
 * - UNIFIED: Variable handling to use direct $agency
 * - LOCALIZED: All labels to Indonesian
 * - COMPREHENSIVE: Includes all fields from both files
 *
 * 3.0.0 - 2025-10-28 (TODO-3084 Review-02)
 * - SIMPLIFIED: Merged with tab-details-content.php
 * - REMOVED: TabViewTemplate wrapper (controller-like logic)
 * - REMOVED: Hook-based content injection
 * - PATTERN: Pure MVC View - direct HTML template
 * - Single file per tab (no partials needed)
 *
 * 2.0.0 - 2025-10-27
 * - ARCHITECTURE: Migrated to TabViewTemplate pattern (hook-based)
 *
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 4, Task 4.3)
 */

defined('ABSPATH') || exit;

// $agency variable is passed from controller
if (!isset($agency)) {
    echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
    return;
}
?>

<div class="agency-details-grid">
    <!-- Informasi Umum -->
    <div class="agency-detail-section">
        <h3><?php esc_html_e('Informasi Umum', 'wp-agency'); ?></h3>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Kode', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->code ?? '-'); ?></span>
        </div>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Nama', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->name ?? '-'); ?></span>
        </div>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Status', 'wp-agency'); ?>:</label>
            <span>
                <?php
                $status_class = ($agency->status ?? '') === 'active' ? 'success' : 'error';
                $status_text = ($agency->status ?? '') === 'active'
                    ? __('Aktif', 'wp-agency')
                    : __('Tidak Aktif', 'wp-agency');
                ?>
                <span class="wpapp-badge wpapp-badge-<?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($status_text); ?>
                </span>
            </span>
        </div>
    </div>

    <!-- Lokasi -->
    <div class="agency-detail-section">
        <h3><?php esc_html_e('Lokasi', 'wp-agency'); ?></h3>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Provinsi', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->provinsi_name ?? '-'); ?></span>
        </div>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Kabupaten/Kota', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->regency_name ?? '-'); ?></span>
        </div>

        <?php if (!empty($agency->pusat_address)): ?>
        <div class="agency-detail-row">
            <label><?php esc_html_e('Alamat', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->pusat_address); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($agency->pusat_postal_code)): ?>
        <div class="agency-detail-row">
            <label><?php esc_html_e('Kode Pos', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->pusat_postal_code); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Statistik -->
    <div class="agency-detail-section">
        <h3><?php esc_html_e('Statistik', 'wp-agency'); ?></h3>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Total Unit Kerja', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->division_count ?? '0'); ?></span>
        </div>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Total Staff', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->employee_count ?? '0'); ?></span>
        </div>
    </div>

    <!-- Metadata -->
    <div class="agency-detail-section">
        <h3><?php esc_html_e('Metadata', 'wp-agency'); ?></h3>

        <?php if (!empty($agency->owner_name)): ?>
        <div class="agency-detail-row">
            <label><?php esc_html_e('Admin', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->owner_name); ?></span>
        </div>
        <?php endif; ?>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Dibuat Oleh', 'wp-agency'); ?>:</label>
            <span><?php echo esc_html($agency->created_by_name ?? '-'); ?></span>
        </div>

        <div class="agency-detail-row">
            <label><?php esc_html_e('Dibuat', 'wp-agency'); ?>:</label>
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
        <div class="agency-detail-row">
            <label><?php esc_html_e('Diubah', 'wp-agency'); ?>:</label>
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
