<?php
/**
 * Agency Employee Validator Class
 *
 * @package     WP_Agency
 * @subpackage  Validators/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Validators/Employee/AgencyEmployeeValidator.php
 *
 * Description: Validator untuk operasi CRUD Employee.
 *              Memastikan semua input data valid sebelum diproses model.
 *              Menyediakan validasi untuk create, update, dan delete.
 *              Includes validasi permission dan ownership.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added create validation
 * - Added update validation
 * - Added delete validation
 * - Added permission validation
 */

namespace WPAgency\Validators\Employee;

use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Cache\AgencyCacheManager;

class AgencyEmployeeValidator {
   private $employee_model;
   private $agency_model;
   private AgencyCacheManager $cache;

   public function __construct() {
       $this->employee_model = new AgencyEmployeeModel();
       $this->agency_model = new AgencyModel(); 
       $this->cache = new AgencyCacheManager();
   }

    public function getUserRelation(int $employee_id): array {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Cek cache terlebih dahulu
        $cache_key = 'employee_relation_' . $employee_id . '_' . $current_user_id;
        $cached_relation = $this->cache->get($cache_key);
        
        if ($cached_relation !== null) {
            return $cached_relation;
        }
        
        // Default relation
        $relation = [
            'is_admin' => current_user_can('edit_all_employees'),
            'is_owner' => false,
            'is_division_admin' => false,
            'is_creator' => false
        ];

        // Jika tidak ada employee_id, kembalikan default
        if (!$employee_id) {
            return $relation;
        }

        // Dapatkan data employee
        $employee = $this->employee_model->find($employee_id);
        if (!$employee) {
            return $relation;
        }

        // Dapatkan data agency
        $agency = $this->agency_model->find($employee->agency_id);
        if (!$agency) {
            return $relation;
        }

        // Isi data relation
        $relation['is_owner'] = ((int)$agency->user_id === $current_user_id);
        $relation['is_division_admin'] = $this->isDivisionAdmin($current_user_id, $employee->division_id);
        $relation['is_creator'] = ((int)$employee->created_by === $current_user_id);

        // Simpan ke cache dengan waktu lebih singkat (10 menit)
        $this->cache->set($cache_key, $relation, 10 * MINUTE_IN_SECONDS);
        
        return $relation;
    }

   public function canViewEmployee(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('view_own_employee')) return true;
        if ($relation['is_division_admin'] && current_user_can('view_division_employee')) return true;
        if ($relation['is_creator'] && current_user_can('view_own_employee')) return true;
        
        return false;
    }

    /**
     * Check if at least one role is selected
     * (Replaces hasAtLeastOneDepartment)
     */
    protected function hasAtLeastOneRole(array $roles): bool {
        return !empty($roles) && is_array($roles) && count($roles) > 0;
    }

    /**
     * Validate roles array
     */
    protected function validateRoles(array $roles): array {
        $errors = [];
        
        if (empty($roles)) {
            $errors[] = __('Minimal satu role harus dipilih', 'wp-agency');
            return $errors;
        }

        // Get valid roles from the source of truth
        $valid_roles = array_keys(\WP_Agency_Activator::getRoles());
        
        // Remove administrator from valid selections
        $valid_roles = array_diff($valid_roles, ['administrator']);
        
        foreach ($roles as $role) {
            if (!in_array($role, $valid_roles)) {
                $errors[] = sprintf(__('Role "%s" tidak valid', 'wp-agency'), $role);
            }
        }
        
        return $errors;
    }
        
   public function canCreateEmployee($agency_id, $division_id): bool {
       $current_user_id = get_current_user_id();

       // Agency Owner Check
       $agency = $this->agency_model->find($agency_id);
       if ($agency && (int)$agency->user_id === (int)$current_user_id) {
           return true;
       }

       // Division Admin Check dengan add_employee capability
       if ($this->isDivisionAdmin($current_user_id, $division_id) && current_user_can('add_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('add_employee')) {
           return true;
       }

       return apply_filters('wp_agency_can_create_employee', false, $agency_id, $division_id, $current_user_id);
   }

    public function canEditEmployee(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('edit_own_employee')) return true;
        if ($relation['is_division_admin'] && current_user_can('edit_own_employee')) return true;
        if ($relation['is_creator'] && current_user_can('edit_own_employee')) return true;
        
        return false;
    }

    public function canDeleteEmployee(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_employee')) return true;
        if ($relation['is_owner']) return true;
        if ($relation['is_division_admin'] && current_user_can('delete_employee')) return true;
        if ($relation['is_creator'] && current_user_can('delete_employee')) return true;
        
        return false;
    }

    /**
     * Validate create data
     */
    public function validateCreate(array $data): array {
        $errors = [];

        // Required fields
        if (empty($data['name'])) {
            $errors[] = __('Nama karyawan wajib diisi', 'wp-agency');
        } elseif (strlen($data['name']) < 3) {
            $errors[] = __('Nama karyawan minimal 3 karakter', 'wp-agency');
        } elseif (strlen($data['name']) > 100) {
            $errors[] = __('Nama karyawan maksimal 100 karakter', 'wp-agency');
        }

        if (empty($data['position'])) {
            $errors[] = __('Jabatan wajib diisi', 'wp-agency');
        } elseif (strlen($data['position']) < 2) {
            $errors[] = __('Jabatan minimal 2 karakter', 'wp-agency');
        } elseif (strlen($data['position']) > 100) {
            $errors[] = __('Jabatan maksimal 100 karakter', 'wp-agency');
        }

        if (empty($data['email'])) {
            $errors[] = __('Email wajib diisi', 'wp-agency');
        } elseif (!is_email($data['email'])) {
            $errors[] = __('Format email tidak valid', 'wp-agency');
        } elseif (email_exists($data['email'])) {
            $errors[] = __('Email sudah digunakan', 'wp-agency');
        }

        if (empty($data['division_id'])) {
            $errors[] = __('Cabang wajib dipilih', 'wp-agency');
        }

        // Validate roles (if provided separately)
        if (isset($data['roles'])) {
            $role_errors = $this->validateRoles($data['roles']);
            $errors = array_merge($errors, $role_errors);
        }

        // Optional fields validation
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 20) {
                $errors[] = __('Nomor telepon maksimal 20 karakter', 'wp-agency');
            }
            // Indonesian phone number validation
            if (!preg_match('/^(\+62|62|0)[\s-]?8[1-9]{1}[\s-]?\d{1,4}[\s-]?\d{1,4}[\s-]?\d{1,4}$/', $data['phone'])) {
                $errors[] = __('Format nomor telepon tidak valid', 'wp-agency');
            }
        }

        if (!empty($data['keterangan']) && strlen($data['keterangan']) > 200) {
            $errors[] = __('Keterangan maksimal 200 karakter', 'wp-agency');
        }

        return $errors;
    }

    /**
     * Validate update data
     */
    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Required fields
        if (empty($data['name'])) {
            $errors[] = __('Nama karyawan wajib diisi', 'wp-agency');
        } elseif (strlen($data['name']) < 3) {
            $errors[] = __('Nama karyawan minimal 3 karakter', 'wp-agency');
        } elseif (strlen($data['name']) > 100) {
            $errors[] = __('Nama karyawan maksimal 100 karakter', 'wp-agency');
        }

        if (empty($data['position'])) {
            $errors[] = __('Jabatan wajib diisi', 'wp-agency');
        } elseif (strlen($data['position']) < 2) {
            $errors[] = __('Jabatan minimal 2 karakter', 'wp-agency');
        } elseif (strlen($data['position']) > 100) {
            $errors[] = __('Jabatan maksimal 100 karakter', 'wp-agency');
        }

        if (empty($data['email'])) {
            $errors[] = __('Email wajib diisi', 'wp-agency');
        } elseif (!is_email($data['email'])) {
            $errors[] = __('Format email tidak valid', 'wp-agency');
        } else {
            // Check if email is already used by another user
            $current_employee = $this->employee_model->find($id);
            if ($current_employee && $current_employee->user_id) {
                $user = get_userdata($current_employee->user_id);
                if ($user && $user->user_email !== $data['email']) {
                    if (email_exists($data['email'])) {
                        $errors[] = __('Email sudah digunakan oleh user lain', 'wp-agency');
                    }
                }
            }
        }

        if (empty($data['division_id'])) {
            $errors[] = __('Cabang wajib dipilih', 'wp-agency');
        }

        // Validate roles (if provided separately)
        if (isset($data['roles'])) {
            $role_errors = $this->validateRoles($data['roles']);
            $errors = array_merge($errors, $role_errors);
        }

        // Optional fields validation
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 20) {
                $errors[] = __('Nomor telepon maksimal 20 karakter', 'wp-agency');
            }
            // Indonesian phone number validation
            if (!preg_match('/^(\+62|62|0)[\s-]?8[1-9]{1}[\s-]?\d{1,4}[\s-]?\d{1,4}[\s-]?\d{1,4}$/', $data['phone'])) {
                $errors[] = __('Format nomor telepon tidak valid', 'wp-agency');
            }
        }

        if (!empty($data['keterangan']) && strlen($data['keterangan']) > 200) {
            $errors[] = __('Keterangan maksimal 200 karakter', 'wp-agency');
        }

        // Validate status if provided
        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors[] = __('Status tidak valid', 'wp-agency');
        }

        return $errors;
    }

    public function validateDelete(int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->employee_model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Get agency for permission check
        $agency = $this->agency_model->find($employee->agency_id);
        if (!$agency) {
            $errors['agency'] = __('Agency tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Gunakan getUserRelation dan canDeleteEmployee dengan relasi
        $relation = $this->getUserRelation($id);
        if (!$this->canDeleteEmployee($relation)) {
            $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus karyawan ini.', 'wp-agency');
        }

        return $errors;
    }

    public function validateView($employee, $agency): array {
        $errors = [];
        
        // Validasi bahwa data yang dibutuhkan ada
        if (!$employee || !$agency) {
            $errors['data'] = __('Data tidak valid.', 'wp-agency');
        }

        return $errors;
    }

   private function validateBasicData(array $data): array {
       $errors = [];

       // Name validation
       $name = trim(sanitize_text_field($data['name'] ?? ''));
       if (empty($name)) {
           $errors['name'] = __('Nama karyawan wajib diisi.', 'wp-agency');
       } elseif (mb_strlen($name) > 100) {
           $errors['name'] = __('Nama karyawan maksimal 100 karakter.', 'wp-agency');
       }

       // Email validation
       $email = sanitize_email($data['email'] ?? '');
       if (empty($email)) {
           $errors['email'] = __('Email wajib diisi.', 'wp-agency');
       } elseif (!is_email($email)) {
           $errors['email'] = __('Format email tidak valid.', 'wp-agency');
       }

       // Position validation
       $position = trim(sanitize_text_field($data['position'] ?? ''));
       if (empty($position)) {
           $errors['position'] = __('Jabatan wajib diisi.', 'wp-agency');
       } elseif (mb_strlen($position) > 100) {
           $errors['position'] = __('Jabatan maksimal 100 karakter.', 'wp-agency');
       }

       // Phone validation (optional)
       if (!empty($data['phone'])) {
           $phone = trim(sanitize_text_field($data['phone']));
           if (mb_strlen($phone) > 20) {
               $errors['phone'] = __('Nomor telepon maksimal 20 karakter.', 'wp-agency');
           } elseif (!preg_match('/^[0-9\+\-\(\)\s]*$/', $phone)) {
               $errors['phone'] = __('Format nomor telepon tidak valid.', 'wp-agency');
           }
       }

       return $errors;
   }

   private function hasAtLeastOneDepartment(array $data): bool {
       return ($data['finance'] ?? false) || 
              ($data['operation'] ?? false) || 
              ($data['legal'] ?? false) || 
              ($data['purchase'] ?? false);
   }

   private function isDivisionAdmin($user_id, $division_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_divisions 
            WHERE id = %d AND user_id = %d",
           $division_id, $user_id
       ));
   }

   private function isStaffMember($user_id, $division_id): bool {
       global $wpdb;
       return (bool)$wpdb->get_var($wpdb->prepare(
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees 
            WHERE user_id = %d AND division_id = %d AND status = 'active'",
           $user_id, $division_id
       ));
   }

    public function validateAccess(int $employee_id): array {
        $relation = $this->getUserRelation($employee_id);
        
        // Dapatkan data employee untuk mendapatkan agency_id
        $employee = $this->employee_model->find($employee_id);
        $agency_id = $employee ? $employee->agency_id : 0;
        $division_id = $employee ? $employee->division_id : 0;
        
        return [
            'has_access' => $this->canViewEmployee($relation),
            'access_type' => $this->getAccessType($relation),
            'relation' => $relation,
            'agency_id' => $agency_id,
            'division_id' => $division_id
        ];
    }

    private function getAccessType(array $relation): string {
        if ($relation['is_admin']) return 'admin';
        if ($relation['is_owner']) return 'owner';
        if ($relation['is_division_admin']) return 'division_admin';
        if ($relation['is_creator']) return 'creator';
        return 'none';
    }

   
}
