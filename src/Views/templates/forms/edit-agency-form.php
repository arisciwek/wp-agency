<?php
/**
 * Edit Agency Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Forms
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: /wp-agency/src/Views/templates/forms/edit-agency-form.php
 * 
 * Description: Modal form template untuk edit disnaker.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan AgencyForm component.
 * 
 * Changelog:
 * 1.0.1 - 2024-12-05
 * - Restructured to match create-agency-form.php layout
 * - Added additional fields from AgencysDB schema
 * - Improved form sections and organization
 * - Enhanced validation markup
 */

defined('ABSPATH') || exit;

// Tambahkan ini sementara di awal render form untuk debug
error_log('Debug wilayah hooks:');
error_log('Province select hook exists: ' . (has_action('wilayah_indonesia_province_select') ? 'yes' : 'no'));
error_log('Regency select hook exists: ' . (has_action('wilayah_indonesia_regency_select') ? 'yes' : 'no'));

?>

<div id="edit-agency-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <form id="edit-agency-form" method="post">
            <div class="modal-header">
                <h3>Edit Disnaker</h3>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            
            <div class="modal-content">
                <?php wp_nonce_field('wp_agency_nonce'); ?>
                <input type="hidden" id="agency-id" name="id" value="">
                <input type="hidden" name="action" value="update_agency">
                
                <div class="row left-side">
                    <div class="agency-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                        
                        <div class="wp-agency-form-group">
                            <label for="edit-name" class="required-field">
                                <?php _e('Nama Disnaker', 'wp-agency'); ?>
                            </label>
                            <input type="text" 
                                   id="edit-name" 
                                   name="name" 
                                   class="regular-text"
                                   maxlength="100" 
                                   required>
                        </div>


                        <div class="wp-agency-form-group">
                            <label for="edit-status" class="required-field">
                                <?php _e('Status', 'wp-agency'); ?>
                            </label>
                            <select id="edit-status" name="status" required>
                                <option value="active" <?php selected($agency->status ?? 'active', 'active'); ?>>
                                    <?php _e('Aktif', 'wp-agency'); ?>
                                </option>
                                <option value="inactive" <?php selected($agency->status ?? 'active', 'inactive'); ?>>
                                    <?php _e('Tidak Aktif', 'wp-agency'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <div class="agency-form-section">
                        <h4><?php _e('Lokasi', 'wp-agency'); ?></h4>

                        <div class="wp-agency-form-group">
                            <label for="edit-provinsi" class="required-field">
                                <?php _e('Provinsi', 'wp-agency'); ?>
                            </label>
                            <div class="input-group">
                                <select id="edit-provinsi"
                                        name="provinsi_code"
                                        class="regular-text"
                                        required
                                        aria-label="<?php _e('Pilih Provinsi', 'wp-agency'); ?>">
                                    <option value=""><?php _e('Pilih Provinsi', 'wp-agency'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="wp-agency-form-group">
                            <label for="edit-regency" class="required-field">
                                <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                            </label>
                            <div class="input-group">
                                <select id="edit-regency"
                                        name="regency_code"
                                        class="regular-text"
                                        required
                                        aria-label="<?php _e('Pilih Kabupaten/Kota', 'wp-agency'); ?>">
                                    <option value=""><?php _e('Pilih Kabupaten/Kota', 'wp-agency'); ?></option>
                                </select>
                            </div>
                        </div>

                        <?php if (current_user_can('edit_all_agencies')): ?>
                        <div class="wp-agency-form-group">
                            <label for="edit-user">
                                <?php _e('Admin', 'wp-agency'); ?>
                            </label>
                            <select id="edit-user" name="user_id" class="regular-text">
                                <option value=""><?php _e('Pilih Admin', 'wp-agency'); ?></option>
                                <?php
                                $users = get_users(['role__in' => ['Disnaker']]);
                                foreach ($users as $user) {
                                    printf(
                                        '<option value="%d">%s</option>',
                                        $user->ID,
                                        esc_html($user->display_name)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="button button-primary">
                    <?php _e('Update', 'wp-agency'); ?>
                </button>
                <button type="button" class="button cancel-edit">
                    <?php _e('Batal', 'wp-agency'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
