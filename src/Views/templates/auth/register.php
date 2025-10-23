<?php
/**
 * Agency Registration Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Auth
 * @version     1.0.7
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
 * 1.1.0 - 2025-01-22 (Task-2065 Form Sync)
 * - Refactored to use shared component agency-form-fields.php
 * - Ensures field consistency with admin-create form
 * - Single source of truth for form structure
 * - Added provinsi and regency fields
 *
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

    <?php
    // Set args for shared component
    $args = [
        'mode' => 'self-register',
        'layout' => 'single-column',
        'field_classes' => 'regular-text',
        'wrapper_classes' => 'form-group'
    ];

    // Try multiple path resolution methods
    $template_path = null;

    // Method 1: Using WP_AGENCY_PATH constant (if available)
    if (defined('WP_AGENCY_PATH')) {
        $template_path = WP_AGENCY_PATH . 'src/Views/templates/partials/agency-form-fields.php';
    }

    // Method 2: Fallback to __FILE__ relative path
    if (!$template_path || !file_exists($template_path)) {
        $template_path = dirname(dirname(__FILE__)) . '/partials/agency-form-fields.php';
    }

    // Method 3: Last resort - hardcoded absolute path
    if (!file_exists($template_path)) {
        $template_path = '/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Views/templates/partials/agency-form-fields.php';
    }

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p class="error">Template component not found after trying all methods!</p>';
        echo '<p class="error">WP_AGENCY_PATH defined: ' . (defined('WP_AGENCY_PATH') ? 'YES - ' . WP_AGENCY_PATH : 'NO') . '</p>';
        echo '<p class="error">Final path tried: ' . esc_html($template_path) . '</p>';
        echo '<p class="error">File readable: ' . (is_readable($template_path) ? 'YES' : 'NO') . '</p>';
    }
    ?>

	<div class="wp-agency-submit clearfix">
	    <div class="form-submit">
	        <button type="submit" class="button button-primary">
	            <?php _e('Daftar', 'wp-agency'); ?>
	        </button>
	    </div>
	</div>
</form>

