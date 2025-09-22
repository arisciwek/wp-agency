<?php
/**
 * Create Agency Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/forms/create-agency-form.php
 *
 * Description: Modal form template untuk tambah disnaker.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan CreateAgencyForm component.
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Initial implementation
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */

defined('ABSPATH') || exit;
?>

<div id="create-agency-modal" class="modal-overlay wp-agency-modal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Agency', 'wp-agency'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="create-agency-form" method="post">
            <?php wp_nonce_field('wp_agency_nonce'); ?>
            <input type="hidden" name="action" value="create_agency">

            <div class="modal-content">
                <div class="row left-side">
                    <div class="agency-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>

                        <div class="wp-agency-form-group">
                            <label for="agency-name" class="required-field">
                                <?php _e('Nama Disnaker', 'wp-agency'); ?>
                            </label>
                            <input type="text" id="agency-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap disnaker', 'wp-agency'); ?></span>
                        </div>

                        <div class="wp-agency-form-group">
                            <label for="agency-npwp">
                                <?php _e('NPWP', 'wp-agency'); ?>
                            </label>
                            <input type="text" id="agency-npwp" name="npwp" placeholder="00.000.000.0-000.000">
                            <span class="field-hint"><?php _e('Format: 00.000.000.0-000.000', 'wp-agency'); ?></span>
                        </div>

                        <div class="wp-agency-form-group">
                            <label for="agency-nib">
                                <?php _e('NIB', 'wp-agency'); ?>
                            </label>
                            <input type="text" id="agency-nib" name="nib" maxlength="13">
                            <span class="field-hint"><?php _e('Nomor Induk Berusaha (13 digit)', 'wp-agency'); ?></span>
                        </div>

                        <div class="wp-agency-form-group">
                            <label for="agency-status" class="required-field">
                                <?php _e('Status', 'wp-agency'); ?>
                            </label>
                            <select id="agency-status" name="status" required>
                                <option value="active"><?php _e('Aktif', 'wp-agency'); ?></option>
                                <option value="inactive"><?php _e('Tidak Aktif', 'wp-agency'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Status aktif disnaker', 'wp-agency'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <div class="agency-form-section">
                        <h4><?php _e('Lokasi', 'wp-agency'); ?></h4>

                        <div class="wp-agency-form-group">
                           <label for="agency-provinsi" class="required-field">
                               <?php _e('Provinsi', 'wp-agency'); ?>
                           </label>
                           <div class="input-group">
                               <select id="agency-provinsi"
                                       name="provinsi_code"
                                       class="regular-text"
                                       required
                                       aria-label="<?php _e('Pilih Provinsi', 'wp-agency'); ?>">
                                   <option value=""><?php _e('Pilih Provinsi', 'wp-agency'); ?></option>
                               </select>
                           </div>
                       </div>

                        <div class="wp-agency-form-group">
                            <label for="agency-regency" class="required-field">
                                <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                            </label>
                            <div class="input-group">
                                <select id="agency-regency"
                                        name="regency_code"
                                        class="regular-text"
                                        required
                                        aria-label="<?php _e('Pilih Kabupaten/Kota', 'wp-agency'); ?>">
                                    <option value=""><?php _e('Pilih Kabupaten/Kota', 'wp-agency'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="button cancel-create"><?php _e('Batal', 'wp-agency'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Simpan', 'wp-agency'); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
=======
                       <div class="wp-agency-form-group">
                           <label for="agency-provinsi" class="required-field">
                               <?php _e('Provinsi', 'wp-agency'); ?>
                           </label>
                           <div class="input-group">
                               <select id="agency-provinsi"
                                       name="provinsi_code"
                                       class="regular-text"
                                       required
                                       aria-label="<?php _e('Pilih Provinsi', 'wp-agency'); ?>">
                                   <option value=""><?php _e('Pilih Provinsi', 'wp-agency'); ?></option>
                               </select>
                           </div>
                       </div>
