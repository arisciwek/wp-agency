<?php
/**
 * Permission Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin
 *
 * Changelog:
 * 1.1.0 - 2025-10-29 (TODO-3090)
 * - REFACTOR: Adopted clean pattern from wp-app-core
 * - Added: getDefaultCapabilitiesForRole() method for role-specific defaults
 * - Improved: addCapabilities() using RoleManager pattern
 * - Improved: resetToDefault() with isPluginRole() check
 * - Removed: Hard-coded agency_roles array
 * - Changed: Use WP_Agency_Role_Manager for all role operations
 * - CORRECTION: Restored 'wp_customer' tab (needed for cross-plugin integration)
 *
 * 1.0.7 - 2024-12-08
 * - Added view_own_agency capability
 * - Updated default role capabilities for editor and author roles
 * - Added documentation for view_own_agency permission
 *
 * 1.0.0 - 2024-11-28
 * - Initial release
 * - Basic permission management
 * - Default capabilities setup
 */

namespace WPAgency\Models\Settings;

class PermissionModel {
    private $available_capabilities = [
        // Disnaker capabilities
        'view_agency_list' => 'Lihat Daftar Disnaker',
        'view_agency_detail' => 'Lihat Detail Disnaker',
        'view_own_agency' => 'Lihat Disnaker Sendiri',
        'add_agency' => 'Tambah Disnaker',
        'edit_all_agencies' => 'Edit Semua Disnaker',
        'edit_own_agency' => 'Edit Disnaker Sendiri',
        'delete_agency' => 'Hapus Disnaker',

        // Unit Kerja capabilities
        'view_division_list' => 'Lihat Daftar Unit Kerja',
        'view_division_detail' => 'Lihat Detail Unit Kerja',
        'view_own_division' => 'Lihat Unit Kerja Sendiri',
        'add_division' => 'Tambah Unit Kerja',
        'edit_all_divisions' => 'Edit Semua Unit Kerja',
        'edit_own_division' => 'Edit Unit Kerja Sendiri',
        'delete_division' => 'Hapus Unit Kerja',

        // Staff capabilities
        'view_employee_list' => 'Lihat Daftar Karyawan',
        'view_employee_detail' => 'Lihat Detail Karyawan', 
        'view_own_employee' => 'Lihat Karyawan Sendiri',
        'add_employee' => 'Tambah Karyawan',
        'edit_all_employees' => 'Edit Karyawan',
        'edit_own_employee' => 'Edit Karyawan Sendiri',
        'delete_employee' => 'Hapus Karyawan',

        // Customer Plugin - Customer (wp-agency TODO-2064)
        'view_customer_list' => 'Lihat Daftar Customer',
        'view_customer_detail' => 'Lihat Detail Customer',

        // Customer Plugin - Branch (wp-agency TODO-2064)
        'view_customer_branch_list' => 'Lihat Daftar Cabang Customer',
        'view_customer_branch_detail' => 'Lihat Detail Cabang Customer',

        // Customer Plugin - Staff (wp-agency TODO-2064)
        'view_customer_employee_list' => 'Lihat Daftar Karyawan Customer',
        'view_customer_employee_detail' => 'Lihat Detail Karyawan Customer',     

    ];

    // Define base capabilities untuk setiap role beserta nilai default-nya
    private $displayed_capabilities_in_tabs = [
        'agency' => [
            'title' => 'Disnaker',
            'description' => 'Disnaker Permissions',
            'caps' => [
                // Disnaker capabilities
                'view_agency_list',
                'view_own_agency',
                'add_agency',
                'edit_own_agency',
                'edit_all_agencies'
            ]
        ],
        'division' => [
            'title' => 'Unit Kerja',
            'description' => 'Unit Kerja Permissions',
            'caps' => [
                'view_division_list',
                'view_division_detail',
                'view_own_division',
                'add_division',
                'edit_all_divisions',
                'edit_own_division',
                'delete_division'
            ]
        ],
        'employee' => [
            'title' => 'Staff',
            'description' => 'Staff Permissions',
            'caps' => [
                'view_employee_list',
                'view_employee_detail',
                'view_own_employee',
                'add_employee',
                'edit_all_employees',
                'edit_own_employee',
                'delete_employee'
            ]
        ],
        'wp_customer' => [
            'title' => 'Customer',
            'description' => 'Customer Permissions',
            'caps' => [
                // Customer
                'view_customer_list',
                'view_customer_detail',
                // Branch
                'view_customer_branch_list',
                'view_customer_branch_detail',
                // Staff
                'view_customer_employee_list',
                'view_customer_employee_detail'
            ]
        ]
    ];

    private function getDisplayedCapabiities(): array{
       return array_merge(
            $this->displayed_capabilities_in_tabs['agency']['caps'],
            $this->displayed_capabilities_in_tabs['division']['caps'],
            $this->displayed_capabilities_in_tabs['employee']['caps'],
            $this->displayed_capabilities_in_tabs['wp_customer']['caps']
        );
    } 


    public function getAllCapabilities(): array {
        return $this->available_capabilities;
    }

    /**
     * Get capability descriptions for tooltips/help text
     *
     * @return array Associative array of capability => description
     */
    public function getCapabilityDescriptions(): array {
        return [
            // Disnaker capabilities
            'view_agency_list' => __('Memungkinkan melihat daftar semua disnaker dalam format tabel', 'wp-agency'),
            'view_agency_detail' => __('Memungkinkan melihat detail informasi disnaker', 'wp-agency'),
            'view_own_agency' => __('Memungkinkan melihat disnaker yang ditugaskan ke pengguna', 'wp-agency'),
            'add_agency' => __('Memungkinkan menambahkan data disnaker baru', 'wp-agency'),
            'edit_all_agencies' => __('Memungkinkan mengedit semua data disnaker', 'wp-agency'),
            'edit_own_agency' => __('Memungkinkan mengedit hanya disnaker yang ditugaskan', 'wp-agency'),
            'delete_agency' => __('Memungkinkan menghapus data disnaker', 'wp-agency'),

            // Division capabilities
            'view_division_list' => __('Memungkinkan melihat daftar semua cabang', 'wp-agency'),
            'view_division_detail' => __('Memungkinkan melihat detail informasi cabang', 'wp-agency'),
            'view_own_division' => __('Memungkinkan melihat cabang yang ditugaskan', 'wp-agency'),
            'add_division' => __('Memungkinkan menambahkan data cabang baru', 'wp-agency'),
            'edit_all_divisions' => __('Memungkinkan mengedit semua data cabang', 'wp-agency'),
            'edit_own_division' => __('Memungkinkan mengedit hanya cabang yang ditugaskan', 'wp-agency'),
            'delete_division' => __('Memungkinkan menghapus data cabang', 'wp-agency'),

            // Employee capabilities
            'view_employee_list' => __('Memungkinkan melihat daftar semua karyawan', 'wp-agency'),
            'view_employee_detail' => __('Memungkinkan melihat detail informasi karyawan', 'wp-agency'),
            'view_own_employee' => __('Memungkinkan melihat karyawan yang ditugaskan', 'wp-agency'),
            'add_employee' => __('Memungkinkan menambahkan data karyawan baru', 'wp-agency'),
            'edit_all_employees' => __('Memungkinkan mengedit semua data karyawan', 'wp-agency'),
            'edit_own_employee' => __('Memungkinkan mengedit hanya karyawan yang ditugaskan', 'wp-agency'),
            'delete_employee' => __('Memungkinkan menghapus data karyawan', 'wp-agency'),
        ];
    }

    public function getCapabilityGroups(): array {
        return $this->displayed_capabilities_in_tabs;
    }

    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            error_log("Role not found: $role_name");
            return false;
        }
        return $role->has_cap($capability);
    }


    public function addCapabilities(): void {
        // Require Role Manager
        require_once WP_AGENCY_PATH . 'includes/class-role-manager.php';

        // Add all capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Add default capabilities to agency roles
        $agency_roles = \WP_Agency_Role_Manager::getRoleSlugs();
        foreach ($agency_roles as $role_slug) {
            $role = get_role($role_slug);
            if ($role) {
                // Add 'read' capability explicitly - required for wp-admin access
                $role->add_cap('read');

                $default_caps = $this->getDefaultCapabilitiesForRole($role_slug);
                foreach ($default_caps as $cap => $enabled) {
                    // Skip 'read' as it's already added above
                    if ($cap === 'read') {
                        continue;
                    }

                    if ($enabled && isset($this->available_capabilities[$cap])) {
                        $role->add_cap($cap);
                    } else if (!$enabled) {
                        $role->remove_cap($cap);
                    }
                }
            }
        }
    }

    public function resetToDefault(): bool {
        global $wpdb;

        try {
            error_log('[AgencyPermissionModel] resetToDefault() START - Using direct DB manipulation');

            // CRITICAL: Increase execution limits
            $old_time_limit = ini_get('max_execution_time');
            @set_time_limit(120);
            error_log('[AgencyPermissionModel] Time limit set to 120 seconds');

            // Require Role Manager
            require_once WP_AGENCY_PATH . 'includes/class-role-manager.php';

            // Get WordPress roles option from database
            $wp_user_roles = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '{$wpdb->prefix}user_roles'");
            $roles = maybe_unserialize($wp_user_roles);
            error_log('[AgencyPermissionModel] Retrieved ' . count($roles) . ' roles from database');

            $modified = false;

            foreach ($roles as $role_name => $role_data) {
                error_log('[AgencyPermissionModel] Processing role: ' . $role_name);

                // Only process agency roles + administrator
                $is_agency_role = \WP_Agency_Role_Manager::isPluginRole($role_name);
                $is_admin = $role_name === 'administrator';

                if (!$is_agency_role && !$is_admin) {
                    error_log('[AgencyPermissionModel] Skipping ' . $role_name);
                    continue;
                }

                // Remove all agency capabilities
                error_log('[AgencyPermissionModel] Removing agency capabilities from ' . $role_name);
                foreach (array_keys($this->available_capabilities) as $cap) {
                    if (isset($roles[$role_name]['capabilities'][$cap])) {
                        unset($roles[$role_name]['capabilities'][$cap]);
                        $modified = true;
                    }
                }

                // Add capabilities back
                if ($role_name === 'administrator') {
                    error_log('[AgencyPermissionModel] Adding all capabilities to administrator');
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $roles[$role_name]['capabilities'][$cap] = true;
                        $modified = true;
                    }
                } else if ($is_agency_role) {
                    error_log('[AgencyPermissionModel] Adding default capabilities to ' . $role_name);
                    // Add read capability
                    $roles[$role_name]['capabilities']['read'] = true;

                    // Add default capabilities
                    $default_caps = $this->getDefaultCapabilitiesForRole($role_name);
                    foreach ($default_caps as $cap => $enabled) {
                        if ($enabled && isset($this->available_capabilities[$cap])) {
                            $roles[$role_name]['capabilities'][$cap] = true;
                            $modified = true;
                        }
                    }
                }
                error_log('[AgencyPermissionModel] Completed processing ' . $role_name);
            }

            // Save back to database if modified
            if ($modified) {
                error_log('[AgencyPermissionModel] Saving modified roles to database');
                $updated = update_option($wpdb->prefix . 'user_roles', $roles);
                error_log('[AgencyPermissionModel] Database update result: ' . ($updated ? 'SUCCESS' : 'NO CHANGE'));

                // CRITICAL: Clear WordPress capability caches
                // Without this, users will still have old cached capabilities
                wp_cache_flush();
                error_log('[AgencyPermissionModel] Cleared WordPress cache');

                // Clear capability cache for all users with agency roles
                $agency_role_slugs = \WP_Agency_Role_Manager::getRoleSlugs();
                foreach ($agency_role_slugs as $role_slug) {
                    $users = get_users(['role' => $role_slug, 'fields' => 'ID']);
                    foreach ($users as $user_id) {
                        clean_user_cache($user_id);
                    }
                }
                error_log('[AgencyPermissionModel] Cleared user capability caches for agency roles');
            }

            error_log('[AgencyPermissionModel] All roles processed successfully');
            error_log('[AgencyPermissionModel] resetToDefault() END - returning TRUE');

            // Restore time limit
            @set_time_limit($old_time_limit);

            return true;
        } catch (\Exception $e) {
            error_log('[AgencyPermissionModel] EXCEPTION in resetToDefault(): ' . $e->getMessage());
            error_log('[AgencyPermissionModel] Stack trace: ' . $e->getTraceAsString());

            // Restore time limit
            if (isset($old_time_limit)) {
                @set_time_limit($old_time_limit);
            }

            error_log('[AgencyPermissionModel] resetToDefault() END - returning FALSE');
            return false;
        }
    }

    public function updateRoleCapabilities(string $role_name, array $capabilities): bool {
        if ($role_name === 'administrator') {
            return false;
        }

        $role = get_role($role_name);
        if (!$role) {
            return false;
        }

        // Get default caps for agency role
        $default_agency_caps = [];
        if ($role_name === 'agency') {
            $default_agency_caps = $this->displayed_capabilities_in_tabs['agency']['caps'];
        }

        // Reset existing capabilities while respecting defaults for agency
        foreach (array_keys($this->available_capabilities) as $cap) {
            if ($role_name === 'agency' && isset($default_agency_caps[$cap])) {
                // For agency role, keep default value
                if ($default_agency_caps[$cap]) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
                continue;
            }
            $role->remove_cap($cap);
        }

        // Add new capabilities (only for non-agency roles or non-default capabilities)
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->available_capabilities[$cap])) {
                if ($role_name !== 'agency' || !isset($default_agency_caps[$cap])) {
                    $role->add_cap($cap);
                }
            }
        }

        return true;
    }

    /**
     * Get default capabilities for a specific agency role
     *
     * @param string $role_slug Role slug
     * @return array Array of capability => bool pairs
     */
    private function getDefaultCapabilitiesForRole(string $role_slug): array {
        $defaults = [
            'agency' => [
                'read' => true
                // Disnaker base role has no agency management capabilities by default
                // Only has 'read' for wp-admin access
            ],
            'agency_admin_dinas' => [
                // Disnaker capabilities - Full access
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,
                'add_agency' => true,
                'edit_own_agency' => true,
                'delete_agency' => false,  // Restricted

                // Unit Kerja capabilities - Full access
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,
                'add_division' => true,
                'edit_all_divisions' => true,
                'edit_own_division' => true,
                'delete_division' => false,  // Restricted

                // Staff capabilities - Full access
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,
                'add_employee' => true,
                'edit_all_employees' => true,
                'edit_own_employee' => true,
                'delete_employee' => false,  // Restricted

                // Customer Plugin - View Access (cross-plugin integration)
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_admin_unit' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - Full access for their unit
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,
                'add_division' => true,
                'edit_all_divisions' => false,
                'edit_own_division' => true,
                'delete_division' => false,

                // Staff capabilities - Full access for their unit
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,
                'add_employee' => true,
                'edit_all_employees' => false,
                'edit_own_employee' => true,
                'delete_employee' => false,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_kepala_dinas' => [
                // Disnaker capabilities - View and manage
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,
                'edit_own_agency' => true,

                // Unit Kerja capabilities - View all
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View all
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_kepala_bidang' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - View their division
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View their division
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_kepala_seksi' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - View only
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View only
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_kepala_unit' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - View only
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View only
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_pengawas' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - View only
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View only
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
            'agency_pengawas_spesialis' => [
                // Disnaker capabilities - View only
                'view_agency_list' => true,
                'view_agency_detail' => true,
                'view_own_agency' => true,

                // Unit Kerja capabilities - View only
                'view_division_list' => true,
                'view_division_detail' => true,
                'view_own_division' => true,

                // Staff capabilities - View only
                'view_employee_list' => true,
                'view_employee_detail' => true,
                'view_own_employee' => true,

                // Customer Plugin - View Access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
            ],
        ];

        return $defaults[$role_slug] ?? [];
    }
}
