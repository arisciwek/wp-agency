<?php
/**
 * Edit Agency Form Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Forms
 * @version     1.0.7
 * @author      arisciwek
 * 
 * Path: /wp-agency/src/Views/templates/forms/edit-agency-form.php
 * 
 * Description: Modal form template untuk edit disnaker.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan AgencyForm component.
 * 
 * Changelog:
 * 1.1.0 - 2025-01-22 (Task-2065 Form Sync)
 * - Refactored to use shared component agency-form-fields.php
 * - Ensures field consistency with register and create forms
 * - Single source of truth for form structure
 * - Removed debug logging
 *
 * 1.0.1 - 2024-12-05
 * - Restructured to match create-agency-form.php layout
 * - Added additional fields from AgencysDB schema
 * - Improved form sections and organization
 * - Enhanced validation markup
 */

defined('ABSPATH') || exit;
?>

<div id="edit-agency-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <form id="edit-agency-form" method="post">
            <div class="modal-header">
                <h3>Edit Disnaker</h3>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>

            <div class="modal-content">
                <?php wp_nonce_field('wp_agency_nonce'); ?>
                <input type="hidden" id="agency-id" name="id" value="">
                <input type="hidden" name="action" value="update_agency">

                <?php
                // Set args for shared component
                $args = [
                    'mode' => 'edit',
                    'layout' => 'two-column',
                    'field_classes' => 'regular-text',
                    'wrapper_classes' => 'wp-agency-form-group',
                    'agency' => null // Data will be populated by JavaScript
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
                <button type="submit" class="button button-primary">
                    <?php _e('Update', 'wp-agency'); ?>
                </button>
                <button type="button" class="button cancel-edit">
                    <?php _e('Batal', 'wp-agency'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>
</div>
