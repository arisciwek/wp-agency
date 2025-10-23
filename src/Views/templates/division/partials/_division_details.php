<?php
/**
 * Division Details Partial Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Division/Partials
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/division/partials/_division_details.php
 *
 * Description: Partial template displaying detailed division information including basic info, location, additional details, and timeline.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial version
 */

defined('ABSPATH') || exit;
?>

<div id="agency-details" class="tab-content">
    <div class="export-actions">
        <button type="button" class="button wp-mpdf-agency-detail-export-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Generate PDF', 'wp-agency'); ?>
        </button>
        <button type="button" class="button wp-docgen-agency-detail-expot-document">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Export DOCX', 'wp-agency'); ?>
        </button>
        <button type="button" class="button wp-docgen-agency-detail-expot-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Export PDF', 'wp-agency'); ?>
        </button>
    </div>

    <!-- Main Content Grid -->
    <div class="meta-info agency-details-grid">
        <!-- Basic Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-building"></span>
                <?php _e('Informasi Dasar', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Nama Agency', 'wp-agency'); ?></th>
                        <td><span id="agency-name"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode Agency', 'wp-agency'); ?></th>
                        <td><span id="agency-code"></span></td>
                    </tr>

                    <tr>
                        <th><?php _e('Status', 'wp-agency'); ?></th>
                        <td><span id="agency-status" class="status-badge"></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Location Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Lokasi Kantor Pusat', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Alamat', 'wp-agency'); ?></th>
                        <td><span id="agency-pusat-address"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode Pos', 'wp-agency'); ?></th>
                        <td><span id="agency-pusat-postal-code"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Provinsi', 'wp-agency'); ?></th>
                        <td><span id="agency-province"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kabupaten/Kota', 'wp-agency'); ?></th>
                        <td><span id="agency-regency"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Koordinat', 'wp-agency'); ?></th>
                        <td>
                            <span id="agency-coordinates"></span>
                            <a href="#" id="agency-google-maps-link" target="_blank" class="button button-small" style="margin-left: 10px;">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e('Lihat di Google Maps', 'wp-agency'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-businessperson"></span>
                <?php _e('Informasi Tambahan', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Admin', 'wp-agency'); ?></th>
                        <td><span id="agency-owner"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Jumlah Division', 'wp-agency'); ?></th>
                        <td><span id="agency-division-count">0</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Jumlah Karyawan', 'wp-agency'); ?></th>
                        <td><span id="agency-employee-count">0</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Timeline Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Timeline', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Dibuat Oleh', 'wp-agency'); ?></th>
                        <td><span id="agency-created-by"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Tanggal Dibuat', 'wp-agency'); ?></th>
                        <td><span id="agency-created-at"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Terakhir Diupdate', 'wp-agency'); ?></th>
                        <td><span id="agency-updated-at"></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
