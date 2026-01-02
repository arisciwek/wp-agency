<?php
/**
 * Edit Employee Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Employee/Forms
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/employee/forms/edit-employee-form.php
 *
 * Description: Form modal untuk mengedit data karyawan.
 *              Includes input validation, error handling,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan komponen toast notification.
 *
 * Changelog:
 * 1.0.1 - 2024-07-27
 * - Changed Wewenang section from department checkboxes to multiple select roles
 * - Updated to use WP_Agency_Activator::getRoles() for role options
 * - Added pre-selection of current user roles
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */
defined('ABSPATH') || exit;

// Ensure $employee object exists (loaded by controller)
if (!isset($employee) || !is_object($employee)) {
    echo '<p class="error">' . __('Employee data not found', 'wp-agency') . '</p>';
    return;
}
?>

<form id="edit-employee-form" method="post" class="wpapp-modal-form">
            <input type="hidden" name="action" value="save_agency_employee">
            <input type="hidden" name="mode" value="edit">
            <input type="hidden" name="id" value="<?php echo esc_attr($employee->id); ?>">
            <input type="hidden" name="agency_id" value="<?php echo esc_attr($employee->agency_id); ?>">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($employee->user_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

            <div class="modal-content">
              <div class="row left-side">
                <!-- Informasi Dasar -->
                <div class="employee-form-section">
                    <div class="section-header">
                        <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
                    </div>
                  <div class="employee-form-group">
                    <label for="edit-employee-name" class="required-field">
                      <?php _e('Nama Karyawan', 'wp-agency'); ?>
                    </label>
                    <input type="text"
                           id="edit-employee-name"
                           name="name"
                           value="<?php echo esc_attr($employee->name); ?>"
                           class="regular-text"
                           maxlength="100"
                           required>
                  </div>

                  <div class="employee-form-group">
                    <label for="edit-employee-position" class="required-field">
                      <?php _e('Jabatan', 'wp-agency'); ?>
                    </label>
                    <input type="text"
                           id="edit-employee-position"
                           name="position"
                           value="<?php echo esc_attr($employee->position ?? ''); ?>"
                           class="regular-text"
                           maxlength="100"
                           required>
                  </div>
                </div>

                <!-- Wewenang -->
                <div class="employee-form-section">
                    <div class="section-header">
                        <h4><?php _e('Wewenang', 'wp-agency'); ?></h4>
                    </div>
                    <div class="employee-form-group">
                        <label for="edit-employee-roles" class="required-field">
                            <?php _e('Role', 'wp-agency'); ?>
                        </label>
                        <select id="edit-employee-roles" name="roles[]" multiple required>
                            <?php
                            $available_roles = \WP_Agency_Role_Manager::getRoles();
                            // Exclude 'agency' role - reserved for agency owner/admin
                            unset($available_roles['agency']);
                            foreach ($available_roles as $role_slug => $role_name) :
                                $selected = in_array($role_slug, $current_roles ?? []) ? 'selected' : '';
                                ?>
                                <option value="<?php echo esc_attr($role_slug); ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html($role_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Pilih satu atau lebih role untuk karyawan ini. Gunakan Ctrl+klik untuk memilih multiple.', 'wp-agency'); ?>
                        </p>
                    </div>
                </div>

                 <!-- Unit Kerja -->
                 <div class="employee-form-section">
                    <div class="section-header">
                       <h4><?php _e('Unit Kerja', 'wp-agency'); ?></h4>
                    </div>

                   <div class="employee-form-group">
                     <label for="edit-employee-division" class="required-field">
                       <?php _e('Unit Kerja', 'wp-agency'); ?>
                     </label>
                     <select id="edit-employee-division" name="division_id" required>
                       <option value=""><?php _e('Pilih Unit Kerja', 'wp-agency'); ?></option>
                       <?php
                       if (!empty($divisions)) {
                           foreach ($divisions as $division) {
                               $selected = ($division->id == $employee->division_id) ? 'selected' : '';
                               echo '<option value="' . esc_attr($division->id) . '" ' . $selected . '>' . esc_html($division->name) . '</option>';
                           }
                       }
                       ?>
                     </select>
                   </div>
                 </div>

              </div>
                <div class="row right-side">
                 <!-- Kontak -->
                 <div class="employee-form-section">
                    <div class="section-header">
                       <h4><?php _e('Informasi Kontak', 'wp-agency'); ?></h4>
                    </div>

                   <div class="employee-form-group">
                     <label for="edit-employee-email" class="required-field">Email</label>
                     <input type="email"
                            id="edit-employee-email"
                            name="email"
                            value="<?php echo esc_attr($employee->email); ?>"
                            class="regular-text"
                            maxlength="100"
                            required>
                     <p class="description">
                       <?php _e('Email akan digunakan untuk login dan komunikasi', 'wp-agency'); ?>
                     </p>
                   </div>

                   <div class="employee-form-group">
                     <label for="edit-employee-phone">
                       <?php _e('Nomor Telepon', 'wp-agency'); ?>
                     </label>
                     <input type="tel"
                            id="edit-employee-phone"
                            name="phone"
                            value="<?php echo esc_attr($employee->phone ?? ''); ?>"
                            class="regular-text"
                            maxlength="20">
                     <p class="description">
                       <?php _e('Format: +62xxx atau 08xxx (opsional)', 'wp-agency'); ?>
                     </p>
                   </div>
                 </div>

                 <!-- Status -->
                 <div class="employee-form-section">
                    <div class="section-header">
                       <h4><?php _e('Status', 'wp-agency'); ?></h4>
                    </div>

                   <div class="employee-form-group">
                     <label for="edit-employee-status" class="required-field">
                       <?php _e('Status', 'wp-agency'); ?>
                     </label>
                     <select id="edit-employee-status" name="status" required>
                       <option value="active" <?php selected($employee->status, 'active'); ?>><?php _e('Aktif', 'wp-agency'); ?></option>
                       <option value="inactive" <?php selected($employee->status, 'inactive'); ?>><?php _e('Nonaktif', 'wp-agency'); ?></option>
                     </select>
                   </div>
                 </div>

                <!-- Keterangan -->
                <div class="employee-form-section">
                    <div class="section-header">
                        <h4><?php _e('Keterangan', 'wp-agency'); ?></h4>
                    </div>

                    <div class="employee-form-group">
                        <label for="edit-employee-keterangan">
                            <?php _e('Keterangan', 'wp-agency'); ?>
                        </label>
                        <textarea id="edit-employee-keterangan"
                                name="keterangan"
                                class="regular-text"
                                maxlength="200"
                                rows="3"><?php echo esc_textarea($employee->keterangan ?? ''); ?></textarea>
                        <p class="description">
                            <?php _e('Maksimal 200 karakter', 'wp-agency'); ?>
                        </p>
                    </div>
                </div>
                </div>
            </div>
            <!-- Form footer removed - WP Modal provides its own footer -->
        </form>
