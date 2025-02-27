<?PHP
/**
 * Edit Division Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Division/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/division/forms/edit-division-form.php
 *
 * Description: Form modal untuk mengedit data cabang.
 *              Includes input validation, error handling,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan komponen toast notification.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial release
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */
 defined('ABSPATH') || exit;
 ?>

<div id="edit-division-modal" class="modal-overlay wp-agency-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Edit Division', 'wp-agency'); ?></h3>
            <button type="button" class="modal-close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <form id="edit-division-form" method="post">
            <?php wp_nonce_field('wp_agency_nonce'); ?>
            <input type="hidden" name="id" id="division-id">

            <div class="modal-content">

                <div class="row left-side">
                    <!-- Informasi Dasar -->
                    <div class="division-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-name" class="required-field">
                                <?php _e('Nama Division', 'wp-agency'); ?>
                            </label>
                            <input type="text" id="edit-division-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap cabang', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-type" class="required-field">
                                <?php _e('Tipe', 'wp-agency'); ?>
                            </label>
                            <select id="edit-division-type" name="type" required>
                                <option value=""><?php _e('Pilih Tipe', 'wp-agency'); ?></option>
                                <option value="cabang"><?php _e('Cabang', 'wp-agency'); ?></option>
                                <option value="pusat"><?php _e('Pusat', 'wp-agency'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Pilih tipe cabang: Kantor Pusat atau Kantor Division, Kantor Operasional, Perwakilan selain Kantor Pusat', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-nitku">NITKU</label>
                            <input type="text" id="edit-division-nitku" name="nitku" maxlength="20">
                            <span class="field-hint"><?php _e('Nomor Identitas Tempat Kegiatan Usaha, jika ada', 'wp-agency'); ?></span>
                        </div>

                    </div>

                    <!-- Kontak -->
                    <div class="division-form-section">
                        <h4><?php _e('Kontak', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-phone">Telepon</label>
                            <input type="text" id="edit-division-phone" name="phone" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-email" class="required-field">Email</label>
                            <input type="email" id="edit-division-email" name="email" maxlength="100">
                            <span class="field-hint"><?php _e('Email aktif cabang', 'wp-agency'); ?></span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="division-form-section">
                        <h4><?php _e('Status', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-status">Status</label>
                            <select id="edit-division-status" name="status">
                                <option value="active"><?php _e('Aktif', 'wp-agency'); ?></option>
                                <option value="inactive"><?php _e('Non-Aktif', 'wp-agency'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Status aktif cabang', 'wp-agency'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <!-- Alamat & Lokasi -->
                    <div class="division-form-section">
                        <h4><?php _e('Aamat & Lokasi', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-address" class="required-field">Alamat</label>
                            <textarea id="edit-division-address" name="address" rows="3"></textarea>
                            <span class="field-hint"><?php _e('Alamat cabang tanpa kabupaten/kota, provinsi dan kode pos', 'wp-agency'); ?></span>
                        </div>
                        
                        <div class="division-form-group">
                            <label for="edit-division-provinsi" class="required-field">
                                <?php _e('Provinsi', 'wp-agency'); ?>
                            </label>
                            <?php 
                            do_action('wilayah_indonesia_province_select', [
                                'name' => 'provinsi_id',
                                'id' => 'edit-division-provinsi',
                                'class' => 'regular-text wilayah-province-select',
                                'data-placeholder' => __('Pilih Provinsi', 'wp-agency'),
                                'required' => 'required'
                            ]);
                            ?>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-regency" class="required-field">
                                <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                            </label>
                            <?php 
                            do_action('wilayah_indonesia_regency_select', [
                                'name' => 'regency_id',
                                'id' => 'edit-division-regency',
                                'class' => 'regular-text wilayah-regency-select',
                                'data-loading-text' => __('Memuat...', 'wp-agency'),
                                'required' => 'required',
                                'data-dependent' => 'edit-division-provinsi'
                            ]);
                            ?>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-postal" class="required-field">Kode Pos</label>
                            <input type="text" id="edit-division-postal" name="postal_code" maxlength="5">
                            <span class="field-hint"><?php _e('5 digit kode pos', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-coordinates">
                            <h4><?php _e('Lokasi', 'wp-agency'); ?></h4>
                            
                            <!-- Tambahkan div untuk map di sini -->
                            <div class="division-coordinates-map" style="height: 300px; margin-bottom: 15px;"></div>

                            <div class="division-form-group">
                                <label for="create-division-latitude" class="required-field">Latitude</label>
                                <input type="text" id="create-division-latitude" name="latitude" required>
                                <span class="field-hint"><?php _e('Contoh: -6.123456', 'wp-agency'); ?></span>
                            </div>

                            <div class="division-form-group">
                                <label for="create-division-longitude" class="required-field">Longitude</label>
                                <input type="text" id="create-division-longitude" name="longitude" required>
                                <span class="field-hint"><?php _e('Contoh: 106.123456', 'wp-agency'); ?></span>
                            </div>

                            <div class="division-form-group google-maps-wrapper">
                                <a href="#" 
                                   class="google-maps-link" 
                                   target="_blank" 
                                   style="display: none;">
                                    <span class="dashicons dashicons-location"></span>
                                    Lihat di Google Maps
                                </a>
                            </div>
                                                    
                        </div>

                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="button cancel-edit">
                    <?php _e('Batal', 'wp-agency'); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <?php _e('Perbarui', 'wp-agency'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
