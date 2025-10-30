<?php
/**
 * Permission Management Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola hak akses plugin WP Agency
 *              Menampilkan matrix permission untuk setiap role
 *              Hanya menampilkan agency roles (bukan semua WordPress roles)
 *
 * Changelog:
 * v1.1.0 - 2025-10-29 (TODO-3090)
 * - BREAKING: Show only agency roles (not all WordPress roles)
 * - Added: Header section with description
 * - Added: Icon indicator for agency roles
 * - Improved: Section styling following wp-app-core pattern
 * - Changed: Better descriptions and info messages
 *
 * v1.0.0 - 2024-01-07
 * - Initial version
 * - Add permission matrix
 * - Add role management
 * - Add tooltips for permissions
 */

if (!defined('ABSPATH')) {
    die;
}

// Get permission model instance
$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();
$capability_descriptions = $permission_model->getCapabilityDescriptions();

// Load RoleManager
require_once WP_AGENCY_PATH . 'includes/class-role-manager.php';

// Get agency roles
$agency_roles = WP_Agency_Role_Manager::getRoleSlugs();

$existing_agency_roles = [];
foreach ($agency_roles as $role_slug) {
    if (WP_Agency_Role_Manager::roleExists($role_slug)) {
        $existing_agency_roles[] = $role_slug;
    }
}
$agency_roles_exist = !empty($existing_agency_roles);

// Get all editable roles
$all_roles = get_editable_roles();

// Display ONLY agency roles (exclude other plugin roles and standard WP roles)
// Agency permissions are specifically for agency management
// Exclude base role 'agency' to avoid confusion (it only has 'read' capability)
$displayed_roles = [];
if ($agency_roles_exist) {
    // Show only agency roles with the dashicons-building icon indicator
    // Skip base role 'agency' - it's for dual-role pattern, not for direct assignment
    foreach ($existing_agency_roles as $role_slug) {
        // Skip base role 'agency'
        if ($role_slug === 'agency') {
            continue;
        }

        if (isset($all_roles[$role_slug])) {
            $displayed_roles[$role_slug] = $all_roles[$role_slug];
        }
    }
}

// Get current active tab with validation
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'agency';

// Validate that the tab exists in capability_groups
if (!isset($capability_groups[$current_tab])) {
    $current_tab = 'agency';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    if (!check_admin_referer('wp_agency_permissions')) {
        wp_die(__('Security check failed.', 'wp-agency'));
    }

    $current_tab = sanitize_key($_POST['current_tab']);

    // Need to get capability groups for form processing
    $temp_capability_groups = $permission_model->getCapabilityGroups();

    // Only get capabilities for current tab
    $current_tab_caps = isset($temp_capability_groups[$current_tab]['caps']) ?
                       $temp_capability_groups[$current_tab]['caps'] :
                       [];

    $updated = false;

    // Only process agency roles (consistent with display filter)
    $temp_agency_roles = WP_Agency_Role_Manager::getRoleSlugs();
    foreach ($temp_agency_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            // Only process capabilities from current tab
            foreach ($current_tab_caps as $cap) {
                $has_cap = isset($_POST['permissions'][$role_name][$cap]);
                if ($role->has_cap($cap) !== $has_cap) {
                    if ($has_cap) {
                        $role->add_cap($cap);
                    } else {
                        $role->remove_cap($cap);
                    }
                    $updated = true;
                }
            }
        }
    }

    if ($updated) {
        add_settings_error(
            'wp_agency_messages',
            'permissions_updated',
            sprintf(
                __('Hak akses %s berhasil diperbarui.', 'wp-agency'),
                $temp_capability_groups[$current_tab]['title']
            ),
            'success'
        );
    }
}
?>

<div class="wrap">
    <div>
        <?php settings_errors('wp_agency_messages'); ?>
    </div>

    <h2 class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($capability_groups as $tab_key => $group): ?>
            <a href="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $tab_key]); ?>"
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
               title="<?php echo esc_attr($group['description'] ?? $group['title']); ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <!-- Header Section -->
    <div class="settings-header-section" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px 20px; margin-top: 20px; border-radius: 4px;">
        <h3 style="margin: 0; color: #1d2327; font-size: 16px;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 20px; vertical-align: middle; margin-right: 8px;"></span>
            <?php
            printf(
                __('Managing %s Permissions', 'wp-agency'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
            <span style="background: #2271b1; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 3px; margin-left: 10px; font-weight: normal;">v1.1.0</span>
        </h3>
        <p style="margin: 8px 0 0 0; color: #646970; font-size: 13px; line-height: 1.6;">
            <?php _e('Configure which disnaker roles <span class="dashicons dashicons-building" style="font-size: 14px; vertical-align: middle; color: #0073aa;"></span> have access to these capabilities. Only disnaker staff roles are shown here.', 'wp-agency'); ?>
        </p>
    </div>

    <!-- Reset Section -->
    <div class="settings-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <button type="button" id="reset-permissions-btn" class="button button-secondary button-reset-permissions">
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e('Reset to Default', 'wp-agency'); ?>
        </button>
        <p class="description">
            <?php
            printf(
                __('Reset <strong>%s</strong> permissions to plugin defaults. This will restore the original capability settings for all roles in this group.', 'wp-agency'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </p>
    </div>

    <!-- Permission Matrix Section -->
    <div class="permissions-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #dcdcde;">
            <?php
            printf(
                __('Disnaker Settings - %s', 'wp-agency'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </h2>

        <form method="post" id="wp-agency-permissions-form" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>">
            <?php wp_nonce_field('wp_agency_permissions'); ?>
            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <input type="hidden" name="action" value="update_role_permissions">

            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Check capabilities for each disnaker role. WordPress Administrators automatically have full access to all disnaker capabilities.', 'wp-agency'); ?>
            </p>

            <table class="widefat fixed striped permission-matrix-table">
                <thead>
                    <tr>
                        <th class="column-role"><?php _e('Role', 'wp-agency'); ?></th>
                        <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                            <th class="column-permission">
                                <?php echo esc_html($permission_labels[$cap]); ?>
                                <span class="dashicons dashicons-info"
                                      title="<?php echo esc_attr($capability_descriptions[$cap] ?? ''); ?>">
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($displayed_roles)) {
                        echo '<tr><td colspan="' . (count($capability_groups[$current_tab]['caps']) + 1) . '" style="text-align:center;">';
                        _e('Tidak ada disnaker roles yang tersedia. Silakan buat disnaker roles terlebih dahulu.', 'wp-agency');
                        echo '</td></tr>';
                    } else {
                        foreach ($displayed_roles as $role_name => $role_info):
                            $role = get_role($role_name);
                            if (!$role) continue;
                    ?>
                        <tr>
                            <td class="column-role">
                                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                                <span class="dashicons dashicons-building" style="color: #0073aa; font-size: 14px; vertical-align: middle;" title="<?php _e('Disnaker Role', 'wp-agency'); ?>"></span>
                            </td>
                            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                                <td class="column-permission">
                                    <input type="checkbox"
                                           class="permission-checkbox"
                                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]"
                                           value="1"
                                           data-role="<?php echo esc_attr($role_name); ?>"
                                           data-capability="<?php echo esc_attr($cap); ?>"
                                           <?php checked($role->has_cap($cap)); ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php
                        endforeach;
                    }
                    ?>
                </tbody>
            </table>
        </form>

        <!-- Sticky Footer with Action Buttons -->
        <div class="settings-footer">
            <p class="submit">
                <?php submit_button(__('Save Permission Changes', 'wp-agency'), 'primary', 'submit', false, ['form' => 'wp-agency-permissions-form']); ?>
            </p>
        </div>
    </div><!-- .permissions-section -->
</div><!-- .wrap -->

<!-- Modal Templates -->
<?php
if (function_exists('wp_agency_render_confirmation_modal')) {
    wp_agency_render_confirmation_modal();
}
