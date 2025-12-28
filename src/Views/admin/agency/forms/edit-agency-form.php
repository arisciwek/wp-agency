<?php
/**
 * Agency Edit Form - Modal Template
 *
 * @package     WPAgency
 * @subpackage  Views/Agency/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/admin/agency/forms/edit-agency-form.php
 *
 * Description: Form template for editing existing agency via modal.
 *              Loaded via AJAX when Edit Agency button clicked.
 *              Pre-fills form with existing agency data.
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial creation
 * - Integrated with WPModal system
 * - Pre-fill agency data
 *
 * @var object $agency Agency data object
 */

defined('ABSPATH') || exit;

// Ensure $agency object exists
if (!isset($agency) || !is_object($agency)) {
    echo '<p class="error">' . __('Agency data not found', 'wp-agency') . '</p>';
    return;
}
?>

<form id="agency-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_agency">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="agency_id" value="<?php echo esc_attr($agency->id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

    <!-- Two Column Layout -->
    <div class="wpapp-form-grid">
        <!-- Left Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="agency-code">
                    <?php _e('Agency Code', 'wp-agency'); ?>
                </label>
                <input type="text"
                       id="agency-code"
                       value="<?php echo esc_attr($agency->code); ?>"
                       disabled
                       class="regular-text">
                <span class="description">
                    <?php _e('Auto-generated (read-only)', 'wp-agency'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="agency-name">
                    <?php _e('Agency Name', 'wp-agency'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                       id="agency-name"
                       name="name"
                       value="<?php echo esc_attr($agency->name); ?>"
                       required
                       placeholder="<?php esc_attr_e('Enter agency name', 'wp-agency'); ?>">
                <span class="description">
                    <?php _e('Full legal name', 'wp-agency'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="agency-phone">
                    <?php _e('Phone', 'wp-agency'); ?>
                </label>
                <input type="text"
                       id="agency-phone"
                       name="phone"
                       value="<?php echo esc_attr($agency->phone ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Enter phone number', 'wp-agency'); ?>">
                <span class="description">
                    <?php _e('Contact phone number', 'wp-agency'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="agency-email">
                    <?php _e('Email', 'wp-agency'); ?>
                </label>
                <input type="email"
                       id="agency-email"
                       name="email"
                       value="<?php echo esc_attr($agency->email ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Enter email address', 'wp-agency'); ?>">
                <span class="description">
                    <?php _e('Contact email address', 'wp-agency'); ?>
                </span>
            </div>
        </div>

        <!-- Right Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="agency-province">
                    <?php _e('Province', 'wp-agency'); ?>
                    <span class="required">*</span>
                </label>
                <select id="agency-province" name="province_id" class="wilayah-select" required>
                    <option value=""><?php _e('Select Province', 'wp-agency'); ?></option>
                    <?php
                    global $wpdb;
                    $provinces = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}wi_provinces ORDER BY name");
                    foreach ($provinces as $province) {
                        $selected = ($agency->province_id == $province->id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($province->id) . '" ' . $selected . '>' . esc_html($province->name) . '</option>';
                    }
                    ?>
                </select>
                <span class="description">
                    <?php _e('Agency location province', 'wp-agency'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="agency-regency">
                    <?php _e('City/Regency', 'wp-agency'); ?>
                    <span class="required">*</span>
                </label>
                <select id="agency-regency" name="regency_id" class="wilayah-select" <?php echo empty($agency->province_id) ? 'disabled' : ''; ?> required>
                    <option value=""><?php _e('Select province first', 'wp-agency'); ?></option>
                    <?php
                    // If agency has regency_id, load regencies for that province
                    if (!empty($agency->province_id)) {
                        $regencies = $wpdb->get_results($wpdb->prepare(
                            "SELECT id, name FROM {$wpdb->prefix}wi_regencies WHERE province_id = %d ORDER BY name",
                            $agency->province_id
                        ));
                        foreach ($regencies as $regency) {
                            $selected = ($agency->regency_id == $regency->id) ? 'selected' : '';
                            echo '<option value="' . esc_attr($regency->id) . '" ' . $selected . '>' . esc_html($regency->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                <span class="description">
                    <?php _e('City/Regency', 'wp-agency'); ?>
                </span>
            </div>

            <div class="wpapp-form-field">
                <label for="agency-address">
                    <?php _e('Address', 'wp-agency'); ?>
                </label>
                <textarea id="agency-address"
                          name="address"
                          rows="4"
                          placeholder="<?php esc_attr_e('Enter agency address', 'wp-agency'); ?>"><?php echo esc_textarea($agency->address ?? ''); ?></textarea>
                <span class="description">
                    <?php _e('Full address', 'wp-agency'); ?>
                </span>
            </div>
        </div>
    </div>
</form>
