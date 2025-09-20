<?php
/**
 * Agency Left Panel Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.0
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
        <h2>Daftar Disnaker</h2>

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
