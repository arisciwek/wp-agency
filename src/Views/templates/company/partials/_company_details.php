<?php
/**
 * Company Details Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/company/partials/_company_details.php
 */

defined('ABSPATH') || exit;
?>

<div id="company-details" class="tab-content">
    <!-- Main Content Grid -->
    <div class="meta-info company-details-grid">
        <!-- Basic Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-building"></span>
                <?php _e('Informasi Dasar', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Nama Perusahaan', 'wp-agency'); ?></th>
                        <td><span id="company-name"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode', 'wp-agency'); ?></th>
                        <td><span id="company-code"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Tipe', 'wp-agency'); ?></th>
                        <td><span id="company-type"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Agency', 'wp-agency'); ?></th>
                        <td><span id="company-agency-name"></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Location Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Lokasi', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Alamat', 'wp-agency'); ?></th>
                        <td><span id="company-address"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode Pos', 'wp-agency'); ?></th>
                        <td><span id="company-postal-code"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Koordinat', 'wp-agency'); ?></th>
                        <td>
                            <span id="company-coordinates"></span>
                            <a href="#" id="company-google-maps-link" target="_blank" class="button button-small" style="margin-left: 10px;">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e('Lihat di Google Maps', 'wp-agency'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-phone"></span>
                <?php _e('Kontak', 'wp-agency'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Telepon', 'wp-agency'); ?></th>
                        <td><span id="company-phone"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email', 'wp-agency'); ?></th>
                        <td><span id="company-email"></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
