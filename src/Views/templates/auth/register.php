<?php
/**
 * Agency Registration Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Auth
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/auth/register.php
 *
 * Description: Template untuk form registrasi agency baru.
 *              Menangani pendaftaran user WordPress sekaligus data agency.
 *              Form mencakup field username, email, password dan data agency
 *              seperti nama perusahaan, NIB, dan NPWP.
 *
 * Dependencies:
 * - jQuery
 * - wp-agency-toast
 * - WordPress AJAX
 * 
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added registration form with validation
 * - Added AJAX submission handling
 * - Added NPWP formatter
 */

defined('ABSPATH') || exit;
?>

<h2><?php _e('Daftar Agency Baru', 'wp-agency'); ?></h2>

<form id="agency-register-form" class="wp-agency-form" method="post">
    <?php wp_nonce_field('wp_agency_register', 'register_nonce'); ?>

    <!-- Card untuk Informasi Login -->
    <div class="wp-agency-card">
        <div class="wp-agency-card-header">
            <h3><?php _e('Informasi Login', 'wp-agency'); ?></h3>
        </div>
        <div class="wp-agency-card-body">
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="regular-text" 
                       required>
                <p class="description"><?php _e('Username untuk login', 'wp-agency'); ?></p>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="regular-text" 
                       required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="regular-text" 
                       required>
            </div>
        </div>
    </div>

    <!-- Card untuk Informasi Perusahaan -->
    <div class="wp-agency-card">
        <div class="wp-agency-card-header">
            <h3><?php _e('Informasi Perusahaan', 'wp-agency'); ?></h3>
        </div>
        <div class="wp-agency-card-body">
            <!-- Nama Lengkap/Perusahaan -->
            <div class="form-group">
                <label for="name">Nama Lengkap/Perusahaan <span class="required">*</span></label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="regular-text" 
                       required>
                <p class="description"><?php _e('Nama ini akan digunakan sebagai identitas agency', 'wp-agency'); ?></p>
            </div>


        </div>
    </div>
	<div class="wp-agency-submit clearfix">
	    <div class="form-submit">
	        <button type="submit" class="button button-primary">
	            <?php _e('Daftar', 'wp-agency'); ?>
	        </button>
	    </div>
	</div>
</form>

