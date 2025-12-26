<?php
/**
 * Company Assignment Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/company/assignment-form.php
 *
 * Description: Modal form template untuk assign agency, division, dan inspector.
 *              Digunakan oleh NewCompanyController via AJAX.
 *              Implements cascade dropdowns.
 *
 * Variables available:
 * - $company_name: Nama perusahaan/branch
 * - $branch_id: ID branch
 * - $current_agency_id: ID agency dari context (auto-detected)
 * - $agencies: Array of agencies untuk dropdown
 * - $divisions: Array of divisions untuk dropdown (if agency selected)
 *
 * Changelog:
 * 1.0.0 - 2025-12-26
 * - Initial implementation
 * - Migrated from JavaScript HTML building to PHP template
 * - Cascade dropdown: Agency → Division → Inspector
 */

defined('ABSPATH') || exit;
?>

<form id="assign-agency-form">
    <!-- Hidden fields -->
    <input type="hidden" id="assign-branch-id" value="<?php echo esc_attr($branch_id); ?>" />
    <input type="hidden" id="assign-agency-id" value="<?php echo esc_attr($current_agency_id); ?>" />

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
               style="width: 100%;" />
    </div>

    <!-- Agency Select (Auto-selected and disabled) -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="agency-select-display">
            <?php esc_html_e('Disnaker:', 'wp-agency'); ?>
            <span style="color: red;">*</span>
        </label>
        <select id="agency-select-display"
                class="regular-text"
                style="width: 100%;"
                disabled>
            <option value=""><?php esc_html_e('-- Pilih Disnaker --', 'wp-agency'); ?></option>
            <?php foreach ($agencies as $agency): ?>
                <option value="<?php echo esc_attr($agency->id); ?>"
                        <?php selected($agency->id, $current_agency_id); ?>>
                    <?php echo esc_html($agency->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Disnaker yang akan mengawasi perusahaan ini (otomatis terdeteksi).', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Division Select (Will be auto-populated) -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="division-select">
            <?php esc_html_e('Pilih Unit Kerja:', 'wp-agency'); ?>
            <span style="color: red;">*</span>
        </label>
        <select id="division-select"
                name="division_id"
                class="regular-text"
                required
                style="width: 100%;"
                disabled>
            <option value=""><?php esc_html_e('Memuat unit kerja...', 'wp-agency'); ?></option>
            <?php if (!empty($divisions)): ?>
                <?php foreach ($divisions as $division): ?>
                    <option value="<?php echo esc_attr($division->id); ?>">
                        <?php echo esc_html($division->name); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Unit kerja/yuridiksi dalam disnaker.', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Inspector Select (Will be populated after division selection) -->
    <div class="form-group" style="margin-bottom: 15px;">
        <label for="inspector-select">
            <?php esc_html_e('Pilih Pengawas:', 'wp-agency'); ?>
            <span style="color: red;">*</span>
        </label>
        <select id="inspector-select"
                name="inspector_id"
                class="regular-text"
                required
                style="width: 100%;"
                disabled>
            <option value=""><?php esc_html_e('-- Pilih Unit Kerja Dulu --', 'wp-agency'); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e('Karyawan yang akan menjadi pengawas untuk perusahaan ini.', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Inspector Info (Assignment Count) -->
    <div class="form-group" id="inspector-info" style="display: none; margin-bottom: 15px;">
        <div class="notice notice-info inline" style="padding: 10px;">
            <p id="inspector-assignments-count"></p>
        </div>
    </div>
</form>
