<?php
/**
 * New Company List Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/company/partials/_new_company_list.php
 *
 * Description: Template untuk menampilkan daftar company yang belum memiliki inspector.
 *              Includes DataTable, loading states, empty states,
 *              dan action buttons dengan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial release
 * - Added loading states
 * - Added empty state messages
 * - Added proper DataTable structure
 * - Added inspector assignment modal
 */

defined('ABSPATH') || exit;

?>

<div id="new-company" class="tab-content">

    <div class="wp-agency-new-company-header">
        <div class="new-company-header-title">
            <h3><?php _e('Perusahaan Baru', 'wp-agency'); ?></h3>
            <p class="description"><?php _e('Daftar perusahaan yang belum memiliki pengawas', 'wp-agency'); ?></p>
        </div>
    </div>

    <div class="wp-agency-new-company-content">
        <!-- Loading State -->
        <div class="new-company-loading-state" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php _e('Memuat data...', 'wp-agency'); ?></p>
        </div>

        <!-- Empty State -->
        <div class="new-company-empty-state" style="display: none;">
            <div class="empty-state-content">
                <span class="dashicons dashicons-building"></span>
                <h4><?php _e('Tidak Ada Data', 'wp-agency'); ?></h4>
                <p><?php _e('Semua perusahaan sudah memiliki pengawas atau tidak ada perusahaan yang terdaftar.', 'wp-agency'); ?></p>
            </div>
        </div>

        <!-- Data Table -->
        <div class="wi-table-container">
            <table id="new-company-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th><?php _e('Kode', 'wp-agency'); ?></th>
                        <th><?php _e('Perusahaan', 'wp-agency'); ?></th>
                        <th><?php _e('Unit', 'wp-agency'); ?></th>
                        <th><?php _e('Yuridiksi', 'wp-agency'); ?></th>
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
                        <th><?php _e('Kode', 'wp-agency'); ?></th>
                        <th><?php _e('Perusahaan', 'wp-agency'); ?></th>
                        <th><?php _e('Unit', 'wp-agency'); ?></th>
                        <th><?php _e('Yuridiksi', 'wp-agency'); ?></th>
                        <th><?php _e('Aksi', 'wp-agency'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Error State -->
        <div class="new-company-error-state" style="display: none;">
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

    <!-- Inspector Assignment Modal -->
    <div id="assign-inspector-modal" class="wp-agency-modal" style="display: none;">
        <div class="wp-agency-modal-content">
            <div class="wp-agency-modal-header">
                <h3><?php _e('Assign Inspector', 'wp-agency'); ?></h3>
                <button type="button" class="wp-agency-modal-close">&times;</button>
            </div>
            
            <div class="wp-agency-modal-body">
                <form id="assign-inspector-form">
                    <input type="hidden" id="assign-branch-id" name="branch_id" />
                    
                    <div class="form-group">
                        <label for="company-name-display"><?php _e('Perusahaan:', 'wp-agency'); ?></label>
                        <input type="text" id="company-name-display" readonly class="regular-text" />
                    </div>
                    
                    <div class="form-group">
                        <label for="inspector-select"><?php _e('Pilih Pengawas:', 'wp-agency'); ?></label>
                        <select id="inspector-select" name="inspector_id" class="regular-text" required>
                            <option value=""><?php _e('-- Pilih Pengawas --', 'wp-agency'); ?></option>
                        </select>
                        <p class="description"><?php _e('Pilih karyawan yang akan menjadi pengawas untuk perusahaan ini.', 'wp-agency'); ?></p>
                    </div>
                    
                    <div class="form-group" id="inspector-info" style="display: none;">
                        <div class="notice notice-info inline">
                            <p id="inspector-assignments-count"></p>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="wp-agency-modal-footer">
                <button type="button" class="button button-primary" id="confirm-assign-inspector">
                    <?php _e('Assign', 'wp-agency'); ?>
                </button>
                <button type="button" class="button wp-agency-modal-cancel">
                    <?php _e('Batal', 'wp-agency'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- View Company Details Modal -->
    <div id="view-company-modal" class="wp-agency-modal" style="display: none;">
        <div class="wp-agency-modal-content">
            <div class="wp-agency-modal-header">
                <h3><?php _e('Detail Perusahaan', 'wp-agency'); ?></h3>
                <button type="button" class="wp-agency-modal-close">&times;</button>
            </div>
            
            <div class="wp-agency-modal-body">
                <div id="company-details-content">
                    <!-- Company details will be loaded here via AJAX -->
                </div>
            </div>
            
            <div class="wp-agency-modal-footer">
                <button type="button" class="button wp-agency-modal-close">
                    <?php _e('Tutup', 'wp-agency'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.wp-agency-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: none;
}

.wp-agency-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border: 1px solid #888;
    width: 500px;
    max-width: 90%;
    border-radius: 4px;
}

.wp-agency-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wp-agency-modal-header h3 {
    margin: 0;
}

.wp-agency-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    font-weight: bold;
    line-height: 20px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}

.wp-agency-modal-close:hover,
.wp-agency-modal-close:focus {
    color: #000;
}

.wp-agency-modal-body {
    padding: 20px;
}

.wp-agency-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e5e5;
    text-align: right;
}

.wp-agency-modal-footer .button {
    margin-left: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group .regular-text {
    width: 100%;
}

.form-group .description {
    margin-top: 5px;
    color: #666;
    font-style: italic;
}

/* Empty State Styles */
.empty-state-content,
.error-state-content {
    text-align: center;
    padding: 40px 20px;
}

.empty-state-content .dashicons,
.error-state-content .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #999;
    margin-bottom: 20px;
}

.error-state-content .dashicons-warning {
    color: #d63638;
}

/* Loading State */
.new-company-loading-state {
    text-align: center;
    padding: 40px 20px;
}

.new-company-loading-state .spinner {
    float: none;
    margin: 0 auto 20px;
}
</style>
