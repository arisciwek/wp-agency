<?php
/**
 * Company Agency Integration
 *
 * @package     WP_Agency
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Integrations/CompanyAgencyIntegration.php
 *
 * Description: Integration dengan wp-customer plugin untuk menambahkan
 *              tombol "Ganti Unit Kerja & Pengawas" di company detail panel.
 *              Menggunakan hook wp_customer_company_agency_actions.
 *
 * Changelog:
 * 1.0.0 - 2025-12-26
 * - Initial implementation
 * - Hook ke wp_customer_company_agency_actions
 * - Tambahkan tombol "Ganti Unit Kerja & Pengawas"
 * - Reuse cascade dropdown dari NewCompanyController
 */

namespace WPAgency\Integrations;

defined('ABSPATH') || exit;

class CompanyAgencyIntegration {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Hook ke wp-customer company detail panel
        add_action('wp_customer_company_agency_actions', [$this, 'render_action_buttons'], 10, 1);

        // Enqueue scripts untuk modal
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 20);

        // AJAX handler untuk load form dan update assignment
        add_action('wp_ajax_get_reassign_form', [$this, 'handle_get_reassign_form']);
        add_action('wp_ajax_update_company_assignment', [$this, 'handle_update_assignment']);
    }

    /**
     * Render action buttons
     *
     * @param object $company Company data object
     */
    public function render_action_buttons($company): void {
        error_log('[CompanyAgencyIntegration] render_action_buttons called');
        error_log('[CompanyAgencyIntegration] Company data: ' . print_r($company, true));
        error_log('[CompanyAgencyIntegration] Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
        error_log('[CompanyAgencyIntegration] Current user can assign_inspector: ' . (current_user_can('assign_inspector_to_branch') ? 'YES' : 'NO'));

        // Check permission - administrator atau user dengan capability assign_inspector_to_branch
        if (!current_user_can('manage_options') && !current_user_can('assign_inspector_to_branch')) {
            error_log('[CompanyAgencyIntegration] Permission DENIED - returning');
            return;
        }

        // Hanya tampilkan jika ada division_id (sudah di-assign)
        if (empty($company->division_id)) {
            error_log('[CompanyAgencyIntegration] division_id is EMPTY - returning');
            return;
        }

        error_log('[CompanyAgencyIntegration] Rendering button...');

        ?>
        <button type="button"
                class="button button-small wp-agency-reassign-btn"
                data-branch-id="<?php echo esc_attr($company->id); ?>"
                data-agency-id="<?php echo esc_attr($company->agency_id ?? ''); ?>"
                data-current-division="<?php echo esc_attr($company->division_id ?? ''); ?>"
                data-current-inspector="<?php echo esc_attr($company->inspector_user_id ?? ''); ?>"
                data-company-name="<?php echo esc_attr($company->name); ?>"
                style="margin-left: 10px;">
            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
            <?php esc_html_e('Ganti Unit Kerja & Pengawas', 'wp-agency'); ?>
        </button>
        <?php
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook): void {
        // Hanya load di halaman company (perusahaan)
        if ($hook !== 'toplevel_page_perusahaan') {
            return;
        }

        // Check permission - administrator atau user dengan capability assign_inspector_to_branch
        if (!current_user_can('manage_options') && !current_user_can('assign_inspector_to_branch')) {
            return;
        }

        wp_enqueue_script(
            'wp-agency-company-reassign',
            WP_AGENCY_URL . 'assets/js/company/company-reassign.js',
            ['jquery', 'wp-modal'],
            WP_AGENCY_VERSION,
            true
        );
    }

    /**
     * Handle get reassign form
     */
    public function handle_get_reassign_form(): void {
        error_log('[CompanyAgencyIntegration] handle_get_reassign_form called');

        // Nonce verification
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('[CompanyAgencyIntegration] Nonce check failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Permission check
        if (!current_user_can('manage_options') && !current_user_can('assign_inspector_to_branch')) {
            error_log('[CompanyAgencyIntegration] Permission denied');
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        // Get parameters
        $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
        $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
        $current_division_id = isset($_POST['current_division_id']) ? intval($_POST['current_division_id']) : 0;
        $current_inspector_id = isset($_POST['current_inspector_id']) ? intval($_POST['current_inspector_id']) : 0;

        error_log('[CompanyAgencyIntegration] Parameters: agency_id=' . $agency_id . ', division_id=' . $current_division_id);

        if (!$agency_id) {
            error_log('[CompanyAgencyIntegration] Agency ID missing');
            wp_send_json_error(['message' => __('Agency ID required', 'wp-agency')]);
            return;
        }

        try {
            global $wpdb;

            // Get divisions for this agency
            $divisions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}app_agency_divisions
                 WHERE agency_id = %d AND status = 'active'
                 ORDER BY name ASC",
                $agency_id
            ));

            error_log('[CompanyAgencyIntegration] Divisions count: ' . count($divisions));

            // Check if template exists
            $template_path = WP_AGENCY_PATH . 'src/Views/company/reassign-form.php';
            if (!file_exists($template_path)) {
                error_log('[CompanyAgencyIntegration] Template not found: ' . $template_path);
                wp_send_json_error(['message' => __('Template not found', 'wp-agency')]);
                return;
            }

            // Render form template
            ob_start();
            include $template_path;
            $form_html = ob_get_clean();

            error_log('[CompanyAgencyIntegration] Form HTML length: ' . strlen($form_html));

            wp_send_json_success([
                'html' => $form_html
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyAgencyIntegration] Exception: ' . $e->getMessage());
            error_log('[CompanyAgencyIntegration] Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle update assignment
     */
    public function handle_update_assignment(): void {
        // Nonce verification
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-agency')]);
            return;
        }

        // Permission check - administrator atau user dengan capability assign_inspector_to_branch
        if (!current_user_can('manage_options') && !current_user_can('assign_inspector_to_branch')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-agency')]);
            return;
        }

        // Get parameters
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $division_id = isset($_POST['division_id']) ? intval($_POST['division_id']) : 0;
        $inspector_user_id = isset($_POST['inspector_id']) ? intval($_POST['inspector_id']) : 0;

        if (!$branch_id || !$division_id) {
            wp_send_json_error(['message' => __('Missing required parameters', 'wp-agency')]);
            return;
        }

        try {
            global $wpdb;

            // Get agency_id from division
            $division = $wpdb->get_row($wpdb->prepare(
                "SELECT agency_id FROM {$wpdb->prefix}app_agency_divisions WHERE id = %d",
                $division_id
            ));

            if (!$division) {
                throw new \Exception(__('Division not found', 'wp-agency'));
            }

            $agency_id = $division->agency_id;

            // Get employee_id from user_id (if inspector selected)
            $employee_id = null;
            if ($inspector_user_id) {
                $employee = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_employees
                     WHERE user_id = %d AND agency_id = %d AND division_id = %d",
                    $inspector_user_id,
                    $agency_id,
                    $division_id
                ));

                if (!$employee) {
                    throw new \Exception(__('Inspector not found in selected division', 'wp-agency'));
                }

                $employee_id = $employee->id;
            }

            // Update branch
            $updated = $wpdb->update(
                $wpdb->prefix . 'app_customer_branches',
                [
                    'division_id' => $division_id,
                    'inspector_id' => $employee_id,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $branch_id],
                ['%d', '%d', '%s'],
                ['%d']
            );

            if ($updated === false) {
                throw new \Exception(__('Failed to update assignment', 'wp-agency'));
            }

            // Clear cache
            wp_cache_delete('customer_branch_membership_' . $branch_id, 'wp_customer');

            wp_send_json_success([
                'message' => __('Assignment updated successfully', 'wp-agency'),
                'branch_id' => $branch_id
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyAgencyIntegration] Error updating assignment: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
