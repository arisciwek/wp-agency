<?php
/**
 * Agency Left Panel Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/agency-left-panel.php
 *
 * Description: Template for the left panel displaying agency list with DataTable.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial version
 */

defined('ABSPATH') || exit;

?>
<div id="wp-agency-left-panel" class="wp-agency-left-panel">
    <div class="wi-panel-header">
        <div class="agency-header-left">
            <h2>Daftar Disnaker</h2>
            <?php if (current_user_can('delete_agency')): ?>
            <!-- Status Filter - Only visible for users with delete_agency permission -->
            <div class="agency-header-filter">
                <label for="agency-status-filter"><?php _e('Status:', 'wp-agency'); ?></label>
                <select id="agency-status-filter" class="agency-status-filter">
                    <option value="active"><?php _e('Aktif', 'wp-agency'); ?></option>
                    <option value="inactive"><?php _e('Tidak Aktif', 'wp-agency'); ?></option>
                    <option value="all"><?php _e('Semua', 'wp-agency'); ?></option>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div id="tombol-tambah-agency"></div>
    </div>

    <div class="wi-panel-content">
        <table id="agencies-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nama Disnaker</th>
                    <th>Unit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
