<?php
/**
 * Permission Model Class
 *
 * @package     WP_Agency
 * @subpackage  Models/Settings
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Models/Settings/PermissionModel.php
 *
 * Description: Model untuk mengelola hak akses plugin
 *
 * Changelog:
 * 1.1.0 - 2024-12-08
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
        // Agency capabilities
        'view_agency_list' => 'Lihat Daftar Agency',
        'view_agency_detail' => 'Lihat Detail Agency',
        'view_own_agency' => 'Lihat Agency Sendiri',
        'add_agency' => 'Tambah Agency',
        'edit_all_agencies' => 'Edit Semua Agency',
        'edit_own_agency' => 'Edit Agency Sendiri',
        'delete_agency' => 'Hapus Agency',

        // Division capabilities
        'view_division_list' => 'Lihat Daftar Division',
        'view_division_detail' => 'Lihat Detail Division',
        'view_own_division' => 'Lihat Division Sendiri',
        'add_division' => 'Tambah Division',
        'edit_all_divisions' => 'Edit Semua Division',
        'edit_own_division' => 'Edit Division Sendiri',
        'delete_division' => 'Hapus Division',

        // Employee capabilities
        'view_employee_list' => 'Lihat Daftar Karyawan',
        'view_employee_detail' => 'Lihat Detail Karyawan', 
        'view_own_employee' => 'Lihat Karyawan Sendiri',
        'add_employee' => 'Tambah Karyawan',
        'edit_all_employees' => 'Edit Karyawan',
        'edit_own_employee' => 'Edit Karyawan Sendiri',
        'delete_employee' => 'Hapus Karyawan',

        // WP Customer Plugin - Customer (wp-agency TODO-2064)
        'view_customer_list' => 'Lihat Daftar Customer',
        'view_customer_detail' => 'Lihat Detail Customer',

        // WP Customer Plugin - Branch (wp-agency TODO-2064)
        'view_customer_branch_list' => 'Lihat Daftar Cabang Customer',
        'view_customer_branch_detail' => 'Lihat Detail Cabang Customer',

        // WP Customer Plugin - Employee (wp-agency TODO-2064)
        'view_customer_employee_list' => 'Lihat Daftar Karyawan Customer',
        'view_customer_employee_detail' => 'Lihat Detail Karyawan Customer',     

    ];

    // Define base capabilities untuk setiap role beserta nilai default-nya
    private $displayed_capabilities_in_tabs = [
        'agency' => [
            'title' => 'Agency Permissions',
            'caps' => [
                // Agency capabilities
                'view_agency_list',
                'view_own_agency', 
                'add_agency',
                'edit_own_agency',
                'edit_all_agencies'
            ]
        ],
        'division' => [
            'title' => 'Division Permissions',
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
            'title' => 'Employee Permissions',
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
            'title' => 'WP Customer Permissions',
            'caps' => [
                // Customer
                'view_customer_list',
                'view_customer_detail',
                // Branch
                'view_customer_branch_list',
                'view_customer_branch_detail',
                // Employee
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
        // Set administrator capabilities
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Set agency role capabilities
        $agency = get_role('agency');
        if ($agency) {
            // Add 'read' capability - required for wp-admin access
            $agency->add_cap('read');

            $default_capabiities = [
                // Agency capabilities
                'view_agency_list' => true,
                'add_agency' => false,
                'view_own_agency' => true,
                'edit_own_agency' => true,
                'view_own_agency' => true,
                'delete_agency' => false,

                // Division capabilities
                'add_division' => true,
                'view_division_list' => true,
                'view_own_division' => true,
                'edit_own_division' => true,
                'delete_division' => false,

                // Employee capabilities
                'add_employee' => true,
                'view_employee_list' => true,
                'view_own_employee' => true,
                'edit_own_employee' => true,
                'delete_employee' => false,

                // WP Customer Plugin - View Access (wp-agency TODO-2064)
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,

            ];

            foreach ($default_capabiities as $cap => $enabled) {
                if ($enabled) {
                    $agency->add_cap($cap);
                } else {
                    $agency->remove_cap($cap);
                }
            }
        }

        // Set capabilities untuk semua agency employee roles
        // Roles: agency_admin_dinas, agency_admin_unit, agency_pengawas, dll
        $agency_roles = [
            'agency_admin_dinas',
            'agency_admin_unit',
            'agency_pengawas',
            'agency_pengawas_spesialis',
            'agency_kepala_unit',
            'agency_kepala_seksi',
            'agency_kepala_bidang',
            'agency_kepala_dinas'
        ];

        foreach ($agency_roles as $role_slug) {
            $role = get_role($role_slug);
            if ($role) {
                // Add 'read' capability - required for wp-admin access
                $role->add_cap('read');

                // Basic view capabilities untuk semua employee roles
                $employee_capabilities = [
                    // Agency capabilities
                    'view_agency_list' => true,
                    'view_own_agency' => true,

                    // Division capabilities
                    'view_division_list' => true,
                    'view_own_division' => true,

                    // Employee capabilities
                    'view_employee_list' => true,
                    'view_own_employee' => true,

                    // WP Customer Plugin - View Access
                    'view_customer_list' => true,
                    'view_customer_detail' => true,
                    'view_customer_branch_list' => true,
                    'view_customer_branch_detail' => true,
                    'view_customer_employee_list' => true,
                    'view_customer_employee_detail' => true,
                ];

                foreach ($employee_capabilities as $cap => $enabled) {
                    if ($enabled) {
                        $role->add_cap($cap);
                    }
                }
            }
        }
    }

    public function resetToDefault(): bool {
        try {
            // Get all agency roles
            $agency_role_slugs = \WP_Agency_Role_Manager::getRoleSlugs();

            // Reset all roles to default
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                // Remove all existing capabilities first
                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Administrator gets all capabilities
                if ($role_name === 'administrator') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                    continue;
                }

                // Agency roles get their default capabilities
                if (in_array($role_name, $agency_role_slugs)) {
                    $this->addCapabilities(); // Gunakan method yang sudah ada
                    break; // Exit loop karena addCapabilities() sudah handle semua roles
                }
            }
            return true;

        } catch (\Exception $e) {
            error_log('Error resetting permissions: ' . $e->getMessage());
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
}
