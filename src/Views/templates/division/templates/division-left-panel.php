<?php
/**
 * Division Left Panel Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Division/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/division/templates/division-left-panel.php
 *
 * Description: Template for the left panel displaying division list with DataTable.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial version
 */

defined('ABSPATH') || exit;

?>
<div id="wp-agency-division-left-panel" class="wp-agency-division-left-panel">
    <div class="wi-panel-header">
        <h2>Daftar Perusahaan</h2>

    </div>
    
    <div class="wi-panel-content">
        <table id="divisions-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nama Perusahan</th>
                    <th>Cabang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
