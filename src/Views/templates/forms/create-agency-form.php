<?php
/**
 * Create Agency Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-agency/src/Views/templates/forms/create-agency-form.php
 * 
 * Description: Template form untuk menambah agency baru.
 *              Menggunakan modal dialog untuk tampilan form.
 *              Includes validasi client-side dan permission check.
 *              Terintegrasi dengan AJAX submission dan toast notifications.
 * 
 * Changelog:
 * 1.0.0 - 2024-12-02 18:30:00
 * - Initial release
 * - Added permission check
 * - Added nonce security
 * - Added form validation
 * - Added AJAX integration
 * 
 * Dependencies:
 * - WordPress admin styles
 * - agency-toast.js for notifications
 * - agency-form.css for styling
 * - agency-form.js for handling
 */

defined('ABSPATH') || exit;

?>

<div id="create-agency-modal" class="modal-overlay" style="display: none;">
   <div class="modal-container">
       <form id="create-agency-form" method="post">
           <div class="modal-header">
               <h3>Tambah Agency</h3>
               <button type="button" class="modal-close" aria-label="Close">&times;</button>
           </div>
           <div class="modal-content">
               <?php wp_nonce_field('wp_agency_nonce'); ?>
               <input type="hidden" name="action" value="create_agency">
               
               <div class="row left-side">
                   <div class="agency-form-section">
                       <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                       
                       <div class="wp-agency-form-group">
                           <label for="agency-name" class="required-field">
                               <?php _e('Nama Agency', 'wp-agency'); ?>
                           </label>
                           <input type="text" 
                                  id="agency-name" 
                                  name="name" 
                                  class="regular-text" 
                                  maxlength="100" 
                                  required>
                       </div>

                        <div class="wp-agency-form-group">
                            <label for="agency-npwp">
                                <?php _e('NPWP', 'wp-agency'); ?>
                            </label>
                            <div class="npwp-input-group">
                                <input type="text" maxlength="2" size="2" class="npwp-segment">
                                <span class="separator">.</span>
                                <input type="text" maxlength="3" size="3" class="npwp-segment">
                                <span class="separator">.</span>
                                <input type="text" maxlength="3" size="3" class="npwp-segment">
                                <span class="separator">.</span>
                                <input type="text" maxlength="1" size="1" class="npwp-segment">
                                <span class="separator">-</span>
                                <input type="text" maxlength="3" size="3" class="npwp-segment">
                                <span class="separator">.</span>
                                <input type="text" maxlength="3" size="3" class="npwp-segment">
                                <input type="hidden" name="npwp" id="agency-npwp">
                            </div>
                            <span class="field-description">
                                Format: 00.000.000.0-000.000
                            </span>
                        </div>

                       <div class="wp-agency-form-group">
                           <label for="agency-nib">
                               <?php _e('NIB', 'wp-agency'); ?>
                           </label>
                           <input type="text" 
                                  id="agency-nib" 
                                  name="nib" 
                                  class="regular-text" 
                                  maxlength="20">
                       </div>

                       <div class="wp-agency-form-group">
                           <label for="agency-status" class="required-field">
                               <?php _e('Status', 'wp-agency'); ?>
                           </label>
                           <select id="agency-status" name="status" required>
                               <option value="active"><?php _e('Aktif', 'wp-agency'); ?></option>
                               <option value="inactive"><?php _e('Tidak Aktif', 'wp-agency'); ?></option>
                           </select>
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
                               <?php 
                               do_action('wilayah_indonesia_province_select', [
                                   'name' => 'provinsi_id',
                                   'id' => 'agency-provinsi',
                                   'class' => 'regular-text wilayah-province-select',
                                   'data-placeholder' => __('Pilih Provinsi', 'wp-agency'),
                                   'required' => 'required',
                                   'aria-label' => __('Pilih Provinsi', 'wp-agency')
                               ]);
                               ?>
                           </div>
                       </div>

                       <div class="wp-agency-form-group">
                           <label for="agency-regency" class="required-field">
                               <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                           </label>
                           <div class="input-group">
                               <?php 
                               do_action('wilayah_indonesia_regency_select', [
                                   'name' => 'regency_id',
                                   'id' => 'agency-regency',
                                   'class' => 'regular-text wilayah-regency-select',
                                   'data-loading-text' => __('Memuat...', 'wp-agency'),
                                   'required' => 'required',
                                   'aria-label' => __('Pilih Kabupaten/Kota', 'wp-agency'),
                                   'data-dependent' => 'agency-provinsi'
                               ]);
                               ?>
                           </div>
                       </div>

                       <?php if (current_user_can('edit_all_agencies')): ?>
                       <div class="wp-agency-form-group">
                           <label for="agency-owner">
                               <?php _e('Admin', 'wp-agency'); ?>
                           </label>
                           <select id="agency-owner" name="user_id" class="regular-text">
                               <option value=""><?php _e('Pilih Admin', 'wp-agency'); ?></option>
                               <?php
                               $users = get_users(['role__in' => ['Agency']]);
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
                   <?php _e('Simpan', 'wp-agency'); ?>
               </button>
               <button type="button" class="button cancel-create">
                   <?php _e('Batal', 'wp-agency'); ?>
               </button>
               <span class="spinner"></span>
           </div>           
       </form>
   </div>
</div>
