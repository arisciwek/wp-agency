<?php
/**
 * Permission Management Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola hak akses plugin WP Agency
 *              Menampilkan matrix permission untuk setiap role
 *
 * Changelog:
 * v1.0.0 - 2024-01-07
 * - Initial version
 * - Add permission matrix
 * - Add role management
 * - Add tooltips for permissions
 */

if (!defined('ABSPATH')) {
    die;
}

function get_capability_description($capability) {
    $descriptions = [
        // Agency capabilities
        'view_agency_list' => __('Memungkinkan melihat daftar semua agency dalam format tabel', 'wp-agency'),
        'view_agency_detail' => __('Memungkinkan melihat detail informasi agency', 'wp-agency'),
        'view_own_agency' => __('Memungkinkan melihat agency yang ditugaskan ke pengguna', 'wp-agency'),
        'add_agency' => __('Memungkinkan menambahkan data agency baru', 'wp-agency'),
        'edit_all_agencies' => __('Memungkinkan mengedit semua data agency', 'wp-agency'),
        'edit_own_agency' => __('Memungkinkan mengedit hanya agency yang ditugaskan', 'wp-agency'),
        'delete_agency' => __('Memungkinkan menghapus data agency', 'wp-agency'),
        
        // Division capabilities
        'view_division_list' => __('Memungkinkan melihat daftar semua cabang', 'wp-agency'),
        'view_division_detail' => __('Memungkinkan melihat detail informasi cabang', 'wp-agency'),
        'view_own_division' => __('Memungkinkan melihat cabang yang ditugaskan', 'wp-agency'),
        'add_division' => __('Memungkinkan menambahkan data cabang baru', 'wp-agency'),
        'edit_all_divisiones' => __('Memungkinkan mengedit semua data cabang', 'wp-agency'),
        'edit_own_division' => __('Memungkinkan mengedit hanya cabang yang ditugaskan', 'wp-agency'),
        'delete_division' => __('Memungkinkan menghapus data cabang', 'wp-agency'),

        // Employee capabilities
        'view_employee_list' => __('Memungkinkan melihat daftar semua karyawan', 'wp-agency'),
        'view_employee_detail' => __('Memungkinkan melihat detail informasi karyawan', 'wp-agency'),
        'view_own_employee' => __('Memungkinkan melihat karyawan yang ditugaskan', 'wp-agency'),
        'add_employee' => __('Memungkinkan menambahkan data karyawan baru', 'wp-agency'),
        'edit_all_employees' => __('Memungkinkan mengedit semua data karyawan', 'wp-agency'),
        'edit_own_employee' => __('Memungkinkan mengedit hanya karyawan yang ditugaskan', 'wp-agency'),
        'delete_employee' => __('Memungkinkan menghapus data karyawan', 'wp-agency')
    ];

    return isset($descriptions[$capability]) ? $descriptions[$capability] : '';
}

// Get permission model instance 
$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();
$all_roles = get_editable_roles();

// Get current active tab
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'agency';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    if (!check_admin_referer('wp_agency_permissions')) {
        wp_die(__('Security check failed.', 'wp-agency'));
    }

    $current_tab = sanitize_key($_POST['current_tab']);
    $capability_groups = $permission_model->getCapabilityGroups();
    
    // Only get capabilities for current tab
    $current_tab_caps = isset($capability_groups[$current_tab]['caps']) ? 
                       $capability_groups[$current_tab]['caps'] : 
                       [];

    $updated = false;
    foreach ($all_roles as $role_name => $role_info) {
        if ($role_name === 'administrator') {
            continue;
        }

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
                $capability_groups[$current_tab]['title']
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
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="permissions-section">
        <!-- Add reset button section before the form -->
        <div class="reset-permissions-section">
            <form id="wp-agency-permissions-form" method="post" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>" id="reset-permissions-form">
                <?php wp_nonce_field('wp_agency_reset_permissions', 'reset_permissions_nonce'); ?>
                <input type="hidden" name="action" value="reset_permissions">
                <button type="button" id="reset-permissions-btn" class="button button-secondary">
                    <i class="dashicons dashicons-image-rotate"></i>
                    <?php _e('Reset to Default', 'wp-agency'); ?>
                </button>
            </form>
            <p class="description">
                <?php _e('Reset permissions to plugin defaults. This will restore the original capability settings for all roles.', 'wp-agency'); ?>
            </p>
        </div>

        <form id="wp-agency-permissions-form" method="post" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>">
            <?php wp_nonce_field('wp_agency_permissions'); ?>
            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <input type="hidden" name="action" value="update_role_permissions">

            <p class="description">
                <?php _e('Configure role permissions for managing agency data. Administrators automatically have full access.', 'wp-agency'); ?>
            </p>

            <table class="widefat fixed striped permissions-matrix">
                <thead>
                    <tr>
                        <th class="column-role"><?php _e('Role', 'wp-agency'); ?></th>
                        <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                            <th class="column-permission">
                                <?php echo esc_html($permission_labels[$cap]); ?>
                                <span class="dashicons dashicons-info tooltip-icon" 
                                      title="<?php echo esc_attr(get_capability_description($cap)); ?>">
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($all_roles as $role_name => $role_info):
                        if ($role_name === 'administrator') continue;
                        $role = get_role($role_name);
                    ?>
                        <tr>
                            <td class="column-role">
                                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                            </td>
                            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                                <td class="column-permission">
                                    <input type="checkbox" 
                                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]" 
                                           value="1"
                                           <?php checked($role->has_cap($cap)); ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(__('Save Changes', 'wp-agency')); ?>
        </form>
    </div>
</div>

<!-- Modal Templates -->
<?php
if (function_exists('wp_agency_render_confirmation_modal')) {
    wp_agency_render_confirmation_modal();
}
