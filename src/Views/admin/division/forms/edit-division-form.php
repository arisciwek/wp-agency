<?PHP
/**
 * Edit Division Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Division/Forms
 * @version     1.0.7
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

// Ensure $division object exists (loaded by controller)
if (!isset($division) || !is_object($division)) {
    echo '<p class="error">' . __('Division data not found', 'wp-agency') . '</p>';
    return;
}
?>

<form id="edit-division-form" method="post" class="wpapp-modal-form">
            <input type="hidden" name="action" value="save_division">
            <input type="hidden" name="mode" value="edit">
            <input type="hidden" name="id" value="<?php echo esc_attr($division->id); ?>">
            <input type="hidden" name="agency_id" value="<?php echo esc_attr($division->agency_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

            <div class="modal-content">

                <div class="row left-side">
                    <!-- Informasi Dasar -->
                    <div class="division-form-section">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-name" class="required-field">
                                <?php _e('Nama Division', 'wp-agency'); ?>
                            </label>
                            <input type="text" id="edit-division-name" name="name" value="<?php echo esc_attr($division->name); ?>" maxlength="100" required>
                            <span class="field-hint"><?php _e('Masukkan nama lengkap cabang', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-type" class="required-field">
                                <?php _e('Tipe', 'wp-agency'); ?>
                            </label>
                            <select id="edit-division-type" name="type" required>
                                <option value=""><?php _e('Pilih Tipe', 'wp-agency'); ?></option>
                                <option value="cabang" <?php selected($division->type, 'cabang'); ?>><?php _e('Cabang', 'wp-agency'); ?></option>
                                <option value="pusat" <?php selected($division->type, 'pusat'); ?>><?php _e('Pusat', 'wp-agency'); ?></option>
                            </select>
                            <span class="field-hint"><?php _e('Pilih tipe cabang: Kantor Pusat atau Kantor Division, Kantor Operasional, Perwakilan selain Kantor Pusat', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-nitku">NITKU</label>
                            <input type="text" id="edit-division-nitku" name="nitku" value="<?php echo esc_attr($division->nitku ?? ''); ?>" maxlength="20">
                            <span class="field-hint"><?php _e('Nomor Identitas Tempat Kegiatan Usaha, jika ada', 'wp-agency'); ?></span>
                        </div>

                    </div>

                    <!-- Kontak -->
                    <div class="division-form-section">
                        <h4><?php _e('Kontak', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-phone">Telepon</label>
                            <input type="text" id="edit-division-phone" name="phone" value="<?php echo esc_attr($division->phone ?? ''); ?>" maxlength="20">
                            <span class="field-hint"><?php _e('Format: +62xxx atau 08xxx', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-email" class="required-field">Email</label>
                            <input type="email" id="edit-division-email" name="email" value="<?php echo esc_attr($division->email ?? ''); ?>" maxlength="100">
                            <span class="field-hint"><?php _e('Email aktif cabang', 'wp-agency'); ?></span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="division-form-section">
                        <h4><?php _e('Status', 'wp-agency'); ?></h4>
                        
                        <div class="division-form-group">
                            <label for="edit-division-status">Status</label>
                            <select id="edit-division-status" name="status">
                                <option value="active" <?php selected($division->status, 'active'); ?>><?php _e('Aktif', 'wp-agency'); ?></option>
                                <option value="inactive" <?php selected($division->status, 'inactive'); ?>><?php _e('Non-Aktif', 'wp-agency'); ?></option>
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
                            <textarea id="edit-division-address" name="address" rows="3"><?php echo esc_textarea($division->address ?? ''); ?></textarea>
                            <span class="field-hint"><?php _e('Alamat cabang tanpa kabupaten/kota, provinsi dan kode pos', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-provinsi" class="required-field">
                                <?php _e('Provinsi', 'wp-agency'); ?>
                            </label>
                            <select name="provinsi_code"
                                    id="edit-division-provinsi"
                                    class="regular-text wilayah-province-select"
                                    required="required">
                                <option value=""><?php _e('Pilih Provinsi', 'wp-agency'); ?></option>
                                <?php
                                global $wpdb;
                                $provinces = $wpdb->get_results("SELECT id, code, name FROM {$wpdb->prefix}wi_provinces ORDER BY name ASC");
                                $selected_province = $division->provinsi_code ?? '';
                                foreach ($provinces as $prov) {
                                    $selected = ($prov->code === $selected_province) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($prov->code) . '" ' . $selected . '>' . esc_html($prov->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-regency" class="required-field">
                                <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                            </label>
                            <select name="regency_code"
                                    id="edit-division-regency"
                                    class="regular-text wilayah-regency-select"
                                    data-dependent="edit-division-provinsi"
                                    required="required">
                                <option value=""><?php _e('Pilih Kabupaten/Kota', 'wp-agency'); ?></option>
                                <?php
                                // Pre-populate regencies if province is known
                                if (!empty($division->provinsi_code)) {
                                    $regencies = $wpdb->get_results($wpdb->prepare(
                                        "SELECT r.id, r.code, r.name
                                         FROM {$wpdb->prefix}wi_regencies r
                                         JOIN {$wpdb->prefix}wi_provinces p ON p.id = r.province_id
                                         WHERE p.code = %s
                                         ORDER BY r.name ASC",
                                        $division->provinsi_code
                                    ));
                                    $selected_regency = $division->regency_code ?? '';
                                    foreach ($regencies as $reg) {
                                        $selected = ($reg->code === $selected_regency) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($reg->code) . '" ' . $selected . '>' . esc_html($reg->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="division-form-group">
                            <label for="edit-division-postal" class="required-field">Kode Pos</label>
                            <input type="text" id="edit-division-postal" name="postal_code" value="<?php echo esc_attr($division->postal_code ?? ''); ?>" maxlength="5">
                            <span class="field-hint"><?php _e('5 digit kode pos', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-form-group">
                            <label>Wilayah Kerja (Kabupaten/Kota)</label>
                            <div id="edit-division-jurisdictions" class="jurisdiction-checkboxes">
                                <?php
                                if (!empty($all_jurisdictions)) {
                                    foreach ($all_jurisdictions as $jur) {
                                        $is_checked = $jur['is_assigned'] ? 'checked' : '';
                                        $is_disabled = $jur['is_primary'] ? 'disabled' : '';
                                        $primary_label = $jur['is_primary'] ? ' <em>(Utama)</em>' : '';
                                        ?>
                                        <label class="jurisdiction-checkbox">
                                            <input type="checkbox"
                                                   name="jurisdictions[]"
                                                   value="<?php echo esc_attr($jur['code']); ?>"
                                                   <?php echo $is_checked; ?>
                                                   <?php echo $is_disabled; ?>>
                                            <span class="checkmark"></span>
                                            <?php echo esc_html($jur['name']) . $primary_label; ?>
                                            <?php if ($jur['is_primary']): ?>
                                                <!-- Hidden input to ensure primary jurisdiction is always submitted -->
                                                <input type="hidden" name="jurisdictions[]" value="<?php echo esc_attr($jur['code']); ?>">
                                            <?php endif; ?>
                                        </label>
                                        <?php
                                    }
                                } else {
                                    echo '<p class="no-data">Tidak ada data wilayah kerja untuk provinsi ini.</p>';
                                }
                                ?>
                            </div>
                            <span class="field-hint"><?php _e('Pilih kabupaten/kota yang menjadi wilayah kerja cabang ini. Wilayah utama tidak dapat dihapus.', 'wp-agency'); ?></span>
                        </div>

                        <div class="division-coordinates">
                            <h4><?php _e('Lokasi', 'wp-agency'); ?></h4>
                            
                            <!-- Tambahkan div untuk map di sini -->
                            <div class="division-coordinates-map" style="height: 300px; margin-bottom: 15px;"></div>

                            <div class="division-form-group">
                                <label for="create-division-latitude" class="required-field">Latitude</label>
                                <input type="text" id="create-division-latitude" name="latitude" value="<?php echo esc_attr($division->latitude ?? ''); ?>" required>
                                <span class="field-hint"><?php _e('Contoh: -6.123456', 'wp-agency'); ?></span>
                            </div>

                            <div class="division-form-group">
                                <label for="create-division-longitude" class="required-field">Longitude</label>
                                <input type="text" id="create-division-longitude" name="longitude" value="<?php echo esc_attr($division->longitude ?? ''); ?>" required>
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

            <!-- Form footer removed - WP Modal provides its own footer -->
        </form>

        <script>
        (function($) {
            console.group('[Edit Division Form] Data Received from Server');
            console.log('Division ID:', <?php echo json_encode($division->id ?? null); ?>);
            console.log('Division Name:', <?php echo json_encode($division->name ?? null); ?>);
            console.log('Province ID:', <?php echo json_encode($division->province_id ?? null); ?>);
            console.log('Province Code (provinsi_code):', <?php echo json_encode($division->provinsi_code ?? null); ?>);
            console.log('Regency ID:', <?php echo json_encode($division->regency_id ?? null); ?>);
            console.log('Regency Code (regency_code):', <?php echo json_encode($division->regency_code ?? null); ?>);
            console.log('All Jurisdictions Count:', <?php echo count($all_jurisdictions ?? []); ?>);
            console.groupEnd();

            // Log select field values after render
            $(document).ready(function() {
                setTimeout(function() {
                    console.group('[Edit Division Form] Select Fields Status');
                    console.log('Province select exists:', $('#edit-division-provinsi').length > 0);
                    console.log('Province select value:', $('#edit-division-provinsi').val());
                    console.log('Province select HTML:', $('#edit-division-provinsi').html()?.substring(0, 200));
                    console.log('Regency select exists:', $('#edit-division-regency').length > 0);
                    console.log('Regency select value:', $('#edit-division-regency').val());
                    console.log('Regency select options count:', $('#edit-division-regency option').length);
                    console.groupEnd();
                }, 500);
            });
        })(jQuery);
        </script>
