<?php
/**
 * Create Division Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Division/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/division/forms/create-division-form.php
 *
 * Description: Form modal untuk menambah cabang baru.
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

<div id="create-division-modal" class="modal-overlay wp-agency-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Division', 'wp-agency'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="create-division-form" method="post">
            <?php wp_nonce_field('wp_agency_nonce'); ?>
            <input type="hidden" name="agency_id" id="agency_id">

            <div class="modal-content">
                <div class="row left-side">
                    <div class="division-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="create-division-name" class="required-field">Nama Division</label>
                            <input type="text" id="create-division-name" name="name" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap cabang', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-type" class="required-field">Tipe</label>
                            <select id="create-division-type" name="type" required>
                                <option value="">Pilih Tipe</option>
                                <option value="cabang">Cabang</option>
                                <option value="pusat">Pusat</option>
                            </select>
                            <span class="field-hint"><?php _e('Pilih tipe cabang', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-nitku">NITKU</label>
                            <input type="text" id="create-division-nitku" name="nitku" maxlength="20">
                            <span class="field-hint"><?php _e('Nomor Identitas Tempat Kegiatan Usaha', 'wp-agency'); ?></span>
                        </div>
                    </div>
                                        
                    <div class="division-form-section">
                        <h4><?php _e('Admin Division', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="create-division-admin-username" class="required-field">Username</label>
                            <input type="text" id="create-division-admin-username" name="admin_username" required>
                            <span class="field-hint">Username untuk login admin division</span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-admin-email" class="required-field">Email</label>
                            <input type="email" id="create-division-admin-email" name="admin_email" required>
                            <span class="field-hint">Email untuk login admin division</span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-admin-firstname" class="required-field">Nama Depan</label>
                            <input type="text" id="create-division-admin-firstname" name="admin_firstname" required>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-admin-lastname">Nama Belakang</label>
                            <input type="text" id="create-division-admin-lastname" name="admin_lastname">
                        </div>
                    </div>

                    <div class="division-form-section">
                        <h4><?php _e('Kontak Admin', 'wp-agency'); ?></h4>
                        <div class="division-form-group">
                            <label for="create-division-phone">Telepon</label>
                            <input type="text" id="create-division-phone" name="phone" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-email">Email Division</label>
                            <input type="email" id="create-division-email" name="email" maxlength="100">
                            <span class="field-hint"><?php _e('Email operasional cabang', 'wp-agency'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row right-side">
                    <div class="division-form-section">
                        <h4><?php _e('Alamat & Lokasi', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="create-division-address" class="required-field">Alamat</label>
                            <textarea id="create-division-address" name="address" rows="3" required></textarea>
                            <span class="field-hint"><?php _e('Alamat lengkap cabang', 'wp-agency'); ?></span>
                        </div>
                        
                        <div class="division-form-group">
                            <label for="create-division-provinsi" class="required-field">Provinsi</label>
                            <?php
                            do_action('wilayah_indonesia_province_select', [
                                'name' => 'provinsi_code',
                                'id' => 'create-division-provinsi',
                                'class' => 'regular-text wilayah-province-select',
                                'required' => 'required'
                            ]);
                            ?>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-regency" class="required-field">Kabupaten/Kota</label>
                            <?php
                            do_action('wilayah_indonesia_regency_select', [
                                'name' => 'regency_code',
                                'id' => 'create-division-regency',
                                'class' => 'regular-text wilayah-regency-select',
                                'required' => 'required',
                                'data-dependent' => 'create-division-provinsi'
                            ]);
                            ?>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-postal" class="required-field">Kode Pos</label>
                            <input type="text" id="create-division-postal" name="postal_code" maxlength="5" required>
                            <span class="field-hint"><?php _e('5 digit kode pos', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="create-division-jurisdictions">Wilayah Kerja (Kabupaten/Kota)</label>
                            <select id="create-division-jurisdictions" name="jurisdictions[]" multiple class="jurisdiction-select">
                                <!-- Options will be loaded via AJAX -->
                            </select>
                            <span class="field-hint"><?php _e('Pilih kabupaten/kota yang menjadi wilayah kerja cabang ini', 'wp-agency'); ?></span>
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
                <button type="button" class="button cancel-create"><?php _e('Batal', 'wp-agency'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Simpan', 'wp-agency'); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
