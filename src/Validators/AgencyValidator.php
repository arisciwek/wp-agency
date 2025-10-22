<?php
/**
 * Agency Validator Class
 *
 * @package     WP_Agency
 * @subpackage  Validators
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: src/Validators/AgencyValidator.php
 *
 * Description: Validator untuk memvalidasi operasi CRUD Agency.
 *              Menangani validasi form dan permission check.
 *              Mendukung multiple user roles: admin, owner, employee.
 *              Terintegrasi dengan WP Capability System.
 *
 * Dependencies:
 * - WPAgency\Models\AgencyModel untuk data checks
 * - WordPress Capability API
 * 
 * Changelog:
 * 2.0.0 - 2024-01-20
 * - Separated form and permission validation
 * - Added role-based permission system
 * - Added relation caching for better performance
 * - Added support for multiple user types (admin, owner, employee)
 * - Improved error handling and messages
 *
 * 1.0.0 - 2024-12-02
 * - Initial release
 */

namespace WPAgency\Validators;

use WPAgency\Models\Agency\AgencyModel;

class AgencyValidator {
    private AgencyModel $model;
    private array $relationCache = [];

    private array $action_capabilities = [
        'create' => 'add_agency',
        'update' => ['edit_all_agencies', 'edit_own_agency'],
        'view' => ['view_agency_detail', 'view_own_agency'],
        'delete' => 'delete_agency',
        'list' => 'view_agency_list'
    ];

    public function __construct() {
        $this->model = new AgencyModel();
    }

    /**
     * Validasi form input
     *
     * @param array $data Data yang akan divalidasi
     * @param int|null $id ID agency untuk update (optional)
     * @return array Array of errors, empty jika valid
     */
    public function validateForm(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama agency wajib diisi.', 'wp-agency');
        } 
        elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama agency maksimal 100 karakter.', 'wp-agency');
        }
        elseif ($this->model->existsByName($name, $id)) {
            $errors['name'] = __('Nama agency sudah ada.', 'wp-agency');
        }



        return $errors;
    }

    /**
     * Validasi permission untuk suatu action
     *
     * @param string $action Action yang akan divalidasi (create|update|view|delete|list)
     * @param int|null $id ID agency (optional)
     * @return array Array of errors, empty jika valid
     * @throws \Exception Jika action tidak valid
     */
    public function validatePermission(string $action, ?int $id = null): array {
        $errors = [];

        if (!$id) {
            // Untuk action yang tidak memerlukan ID (misal: create)
            return $this->validateBasicPermission($action);
        }

        // Dapatkan relasi user dengan agency
        $relation = $this->getUserRelation($id);
        
        // Validasi berdasarkan relasi dan action
        switch ($action) {
            case 'view':
                if (!$this->canView($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk melihat agency ini.', 'wp-agency');
                }
                break;

            case 'update':
                if (!$this->canUpdate($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk mengubah agency ini.', 'wp-agency');
                }
                break;

            case 'delete':
                if (!$this->canDelete($relation)) {
                    $errors['permission'] = __('Anda tidak memiliki akses untuk menghapus agency ini.', 'wp-agency');
                }
                break;

            default:
                throw new \Exception('Invalid action specified');
        }

        return $errors;
    }

    /**
     * Get user relation with agency
     *
     * @param int $agency_id
     * @return array Array containing is_admin, is_owner, is_employee flags
     */
    
    public function getUserRelation(int $agency_id): array {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Check cache first
        if (isset($this->relationCache[$agency_id])) {
            return $this->relationCache[$agency_id];
        }

        $relation = [
            'is_admin' => current_user_can('edit_all_agencies'),
            'is_owner' => false,
            'is_employee' => false
        ];

        if ($agency_id === 0) {
            // Cek apakah user adalah owner dari agency manapun
            $owner_check = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agencies 
                 WHERE user_id = %d 
                 LIMIT 1",
                $current_user_id
            ));

            if ($owner_check) {
                $relation['is_owner'] = true;
                $agency_id = (int)$owner_check;
            } else {
                // Jika bukan owner, cek apakah employee
                $employee_check = $wpdb->get_var($wpdb->prepare(
                    "SELECT agency_id 
                     FROM {$wpdb->prefix}app_agency_employees 
                     WHERE user_id = %d 
                     AND status = 'active' 
                     LIMIT 1",
                    $current_user_id
                ));

                if ($employee_check) {
                    $relation['is_employee'] = true;
                    $agency_id = (int)$employee_check;
                }
            }
        } else {
            // Existing logic for specific agency_id
            $agency = $this->model->find($agency_id);
            if ($agency) {
                $relation['is_owner'] = ((int)$agency->user_id === $current_user_id);
            }

            // Check if user is employee
            $is_employee = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->prefix}app_agency_employees 
                 WHERE agency_id = %d 
                 AND user_id = %d 
                 AND status = 'active'",
                $agency_id,
                $current_user_id
            ));

            $relation['is_employee'] = (int)$is_employee > 0;
        }

        // Save to cache with actual agency_id
        $this->relationCache[$agency_id] = $relation;

        return $relation;
    }

    public function validateAccess(int $agency_id): array {
        $relation = $this->getUserRelation($agency_id);
        
        return [
            'has_access' => $this->canView($relation),
            'access_type' => $this->getAccessType($relation),
            'relation' => $relation,
            'agency_id' => $agency_id // Tambahkan ini
        ];
    }
    
    private function getAccessType(array $relation): string {
        if ($relation['is_admin']) return 'admin';
        if ($relation['is_owner']) return 'owner';
        if ($relation['is_employee']) return 'employee';
        return 'none';
    }


    /**
     * Check if user can view agency
     */
    public function canView(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('view_own_agency')) return true;
        if ($relation['is_employee'] && current_user_can('view_own_agency')) return true;
        return false;
    }

    /**
     * Check if user can update agency
     */
    public function canUpdate(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('edit_own_agency')) return true;
        return false;
    }

    /**
     * Check if user can delete agency
     */
    public function canDelete(array $relation): bool {
        return $relation['is_admin'] && current_user_can('delete_agency');
    }

    /**
     * Validate basic permissions that don't require agency ID
     */
    private function validateBasicPermission(string $action): array {
        $errors = [];
        $required_cap = $this->action_capabilities[$action] ?? null;

        if (!$required_cap) {
            throw new \Exception('Invalid action specified');
        }

        if (!current_user_can($required_cap)) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk operasi ini.', 'wp-agency');
        }

        return $errors;
    }

    /**
     * Clear relation cache
     *
     * @param int|null $agency_id If provided, only clear cache for specific agency
     */
    public function clearCache(?int $agency_id = null): void {
        if ($agency_id) {
            unset($this->relationCache[$agency_id]);
        } else {
            $this->relationCache = [];
        }
    }


    public function validateDelete(int $id): array {
        $errors = [];

        // 1. Validasi permission dasar
        if (!current_user_can('delete_agency')) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus agency', 'wp-agency');
            return $errors;
        }

        // 2. Cek apakah agency ada
        $agency = $this->model->find($id);
        if (!$agency) {
            $errors[] = __('Agency tidak ditemukan', 'wp-agency');
            return $errors;
        }

        // 3. Cek relasi dengan User
        if (!$this->canDelete($this->getUserRelation($id))) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus agency ini', 'wp-agency');
            return $errors;
        }

        // 4. Cek apakah agency memiliki division
        $division_count = $this->model->getDivisionCount($id);
        if ($division_count > 0) {
            $errors[] = sprintf(
                __('Agency tidak dapat dihapus karena masih memiliki %d cabang', 'wp-agency'),
                $division_count
            );
        }

        // 5. Cek apakah agency memiliki employee aktif (soft delete aware)
        $employee_count = $this->model->getEmployeeCount($id);
        if ($employee_count > 0) {
            $errors[] = sprintf(
                __('Agency tidak dapat dihapus karena masih memiliki %d karyawan', 'wp-agency'),
                $employee_count
            );
        }

        return $errors;
    }

}
