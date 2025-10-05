<?php
/**
 * Employee List Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Employee/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/employee/partials/_employee_list.php
 *
 * Description: Template untuk menampilkan daftar karyawan.
 *              Includes DataTable, loading states, empty states,
 *              dan action buttons dengan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added loading states
 * - Added empty state messages
 * - Added proper DataTable structure
 */

defined('ABSPATH') || exit;

?>

<div id="employee-list" class="tab-content">
    <div class="wp-agency-employee-header">
        <div class="employee-header-title">
            <h3><?php _e('Daftar Karyawan', 'wp-agency'); ?></h3>
        </div>
        <div class="employee-header-actions">

            <div id="tombol-tambah-karyawan"></div>

        </div>

    </div>

    <div class="wp-agency-employee-content">
        <!-- Loading State -->
        <div class="employee-loading-state" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php _e('Memuat data...', 'wp-agency'); ?></p>
        </div>

        <!-- Empty State -->
        <div class="empty-state" style="display: none;">
            <div class="empty-state-content">
                <span class="dashicons dashicons-businessperson"></span>
                <h4><?php _e('Belum Ada Data', 'wp-agency'); ?></h4>
                <p>
                    <?php
                    if (current_user_can('add_employee')) {
                        _e('Belum ada karyawan yang ditambahkan. Klik tombol "Tambah Karyawan" untuk menambahkan data baru.', 'wp-agency');
                    } else {
                        _e('Belum ada karyawan yang ditambahkan.', 'wp-agency');
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Data Table -->
        <div class="wi-table-container">
            <table id="employee-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th><?php _e('Nama', 'wp-agency'); ?></th>
                        <th><?php _e('Jabatan', 'wp-agency'); ?></th>
                        <th><?php _e('Wewenang', 'wp-agency'); ?></th>
                        <th><?php _e('Cabang', 'wp-agency'); ?></th>
                        <th><?php _e('Status', 'wp-agency'); ?></th>
                        <th class="text-center no-sort">
                            <?php _e('Aksi', 'wp-agency'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables will populate this -->
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php _e('Nama', 'wp-agency'); ?></th>
                        <th><?php _e('Jabatan', 'wp-agency'); ?></th>
                        <th><?php _e('Wewenang', 'wp-agency'); ?></th>
                        <th><?php _e('Cabang', 'wp-agency'); ?></th>
                        <th><?php _e('Status', 'wp-agency'); ?></th>
                        <th><?php _e('Aksi', 'wp-agency'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Error State -->
        <div class="error-state" style="display: none;">
            <div class="error-state-content">
                <span class="dashicons dashicons-warning"></span>
                <h4><?php _e('Gagal Memuat Data', 'wp-agency'); ?></h4>
                <p><?php _e('Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'wp-agency'); ?></p>
                <button type="button" class="button reload-table">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Muat Ulang', 'wp-agency'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Export Buttons (Optional, can be enabled via settings) -->
    <?php if (apply_filters('wp_agency_enable_export', false)): ?>
        <div class="export-actions">
            <button type="button" class="button export-excel">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php _e('Export Excel', 'wp-agency'); ?>
            </button>
            <button type="button" class="button export-pdf">
                <span class="dashicons dashicons-pdf"></span>
                <?php _e('Export PDF', 'wp-agency'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<?php
// Include related modals
require_once WP_AGENCY_PATH . 'src/Views/templates/employee/forms/create-employee-form.php';
require_once WP_AGENCY_PATH . 'src/Views/templates/employee/forms/edit-employee-form.php';
?>
