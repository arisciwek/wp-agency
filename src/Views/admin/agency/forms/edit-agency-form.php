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
    <!-- Auto-wire system will inject action and nonce automatically -->
    <input type="hidden" name="id" value="<?php echo esc_attr($agency->id); ?>">

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
                <label for="agency-status">
                    <?php _e('Status', 'wp-agency'); ?>
                </label>
                <select id="agency-status" name="status" class="wilayah-select">
                    <option value="inactive" <?php selected($agency->status, 'inactive'); ?>>
                        <?php _e('Inactive', 'wp-agency'); ?>
                    </option>
                    <option value="active" <?php selected($agency->status, 'active'); ?>>
                        <?php _e('Active', 'wp-agency'); ?>
                    </option>
                </select>
                <span class="description">
                    <?php _e('Agency status', 'wp-agency'); ?>
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

        </div>
    </div>
</form>
