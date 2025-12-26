<?php
/**
 * Company Reassignment Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/company/reassign-form.php
 *
 * Description: Modal form template untuk ganti unit kerja & pengawas.
 *              Digunakan oleh CompanyAgencyIntegration via AJAX.
 *              Pattern sama dengan assignment form.
 *
 * Variables available:
 * - $company_name: Nama perusahaan
 * - $current_division_id: ID unit kerja saat ini
 * - $current_inspector_id: ID pengawas saat ini
 * - $divisions: Array of divisions untuk dropdown
 *
 * Changelog:
 * 1.0.0 - 2025-12-26
 * - Initial implementation
 * - Cascade dropdown: Unit Kerja → Pengawas
 * - Default values dari current assignment
 */

defined('ABSPATH') || exit;
?>

<form id="reassign-form">
    <!-- Company Name (Readonly) -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="company-name-display">
            <?php esc_html_e('Perusahaan:', 'wp-agency'); ?>
        </label>
        <input type="text"
               id="company-name-display"
               value="<?php echo esc_attr($company_name); ?>"
               readonly
               class="regular-text"
               style="width: 100%; background-color: #f0f0f1;" />
    </div>

    <!-- Division Select -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="reassign-division">
            <?php esc_html_e('Unit Kerja:', 'wp-agency'); ?>
            <span style="color: red;">*</span>
        </label>
        <select id="reassign-division"
                name="division_id"
                class="regular-text"
                required
                style="width: 100%;">
            <option value=""><?php esc_html_e('-- Pilih Unit Kerja --', 'wp-agency'); ?></option>
            <?php foreach ($divisions as $division): ?>
                <option value="<?php echo esc_attr($division->id); ?>"
                        <?php selected($division->id, $current_division_id); ?>>
                    <?php echo esc_html($division->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Pilih unit kerja baru untuk perusahaan ini.', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Inspector Select -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="reassign-inspector">
            <?php esc_html_e('Pengawas:', 'wp-agency'); ?>
            <span style="color: red;">*</span>
        </label>
        <select id="reassign-inspector"
                name="inspector_id"
                class="regular-text"
                required
                style="width: 100%;">
            <option value=""><?php esc_html_e('-- Pilih Unit Kerja Dulu --', 'wp-agency'); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e('Pilih pengawas baru yang akan mengawasi perusahaan ini.', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Inspector Info (Assignment Count) -->
    <div class="form-group" id="inspector-info" style="display: none; margin-bottom: 15px;">
        <div class="notice notice-info inline" style="padding: 10px;">
            <p id="inspector-assignments-count"></p>
        </div>
    </div>

    <!-- Hidden fields untuk current values -->
    <input type="hidden" id="current-division-id" value="<?php echo esc_attr($current_division_id); ?>" />
    <input type="hidden" id="current-inspector-id" value="<?php echo esc_attr($current_inspector_id); ?>" />
</form>
