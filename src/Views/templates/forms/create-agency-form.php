<?php
/**
 * Create Agency Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Forms
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/forms/create-agency-form.php
 *
 * Description: Modal form template untuk tambah disnaker.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan CreateAgencyForm component.
 *
 * Changelog:
 * 1.1.0 - 2025-01-22 (Task-2065 Form Sync)
 * - Refactored to use shared component agency-form-fields.php
 * - Ensures field consistency with register and edit forms
 * - Single source of truth for form structure
 *
 * 1.0.0 - 2024-12-03
 * - Initial implementation
 * - Added form structure
 * - Added validation markup
 * - Added AJAX integration
 */

defined('ABSPATH') || exit;
?>

<div id="create-agency-modal" class="modal-overlay wp-agency-modal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Tambah Agency', 'wp-agency'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form id="create-agency-form" method="post">
            <?php wp_nonce_field('wp_agency_nonce'); ?>
            <input type="hidden" name="action" value="create_agency">

            <div class="modal-content">
                <?php
                // Set args for shared component
                $args = [
                    'mode' => 'admin-create',
                    'layout' => 'two-column',
                    'field_classes' => 'regular-text',
                    'wrapper_classes' => 'wp-agency-form-group'
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
            </div>

            <div class="modal-footer">
                <button type="button" class="button cancel-create"><?php _e('Batal', 'wp-agency'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Simpan', 'wp-agency'); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
