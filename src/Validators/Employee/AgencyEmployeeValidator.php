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

class AgencyEmployeeValidator {
   private $employee_model;
   private $agency_model;

   public function __construct() {
       $this->employee_model = new AgencyEmployeeModel();
       $this->agency_model = new AgencyModel(); 
   }

   public function canViewEmployee($employee, $agency): bool {
       $current_user_id = get_current_user_id();

       // Agency Owner Check
       if ((int)$agency->user_id === (int)$current_user_id) {
           return true;
       }

       // Division Admin Check
       if ($this->isDivisionAdmin($current_user_id, $employee->division_id)) {
           return true;
       }

       // Staff Check (dari AgencyEmployees)
       if ($this->isStaffMember($current_user_id, $employee->division_id)) {
           return true;
       }

       // System Admin Check
       if (current_user_can('view_employee_detail')) {
           return true;
       }

       return apply_filters('wp_agency_can_view_employee', false, $employee, $agency, $current_user_id);
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

   public function canEditEmployee($employee, $agency): bool {
       $current_user_id = get_current_user_id();

       // Agency Owner Check
       if ((int)$agency->user_id === (int)$current_user_id) {
           return true;
       }

       // Division Admin Check
       if ($this->isDivisionAdmin($current_user_id, $employee->division_id) && 
           current_user_can('edit_own_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id && 
           current_user_can('edit_own_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('edit_all_employees')) {
           return true;
       }

       return apply_filters('wp_agency_can_edit_employee', false, $employee, $agency, $current_user_id);
   }

   public function canDeleteEmployee($employee, $agency): bool {
       $current_user_id = get_current_user_id();

       // Agency Owner Check
       if ((int)$agency->user_id === (int)$current_user_id) {
           return true;
       }

       // Division Admin Check
       if ($this->isDivisionAdmin($current_user_id, $employee->division_id) && 
           current_user_can('delete_employee')) {
           return true;
       }

       // Creator Check
       if ((int)$employee->created_by === (int)$current_user_id && 
           current_user_can('delete_employee')) {
           return true;
       }

       // System Admin Check
       if (current_user_can('delete_employee')) {
           return true;
       }

       return false;
   }

   public function validateCreate(array $data): array {
       $errors = [];

       // Permission check
       if (!$this->canCreateEmployee($data['agency_id'], $data['division_id'])) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk menambah karyawan.', 'wp-agency');
           return $errors;
       }

       // Basic data validation
       $errors = array_merge($errors, $this->validateBasicData($data));

       // Agency ID validation
       if (empty($data['agency_id'])) {
           $errors['agency_id'] = __('ID Agency wajib diisi.', 'wp-agency');
       } else {
           $agency = $this->agency_model->find($data['agency_id']);
           if (!$agency) {
               $errors['agency_id'] = __('Agency tidak ditemukan.', 'wp-agency');
           }
       }

       // Division ID validation
       if (empty($data['division_id'])) {
           $errors['division_id'] = __('ID Division wajib diisi.', 'wp-agency');
       }

       // Email uniqueness
       if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'])) {
           $errors['email'] = __('Email sudah digunakan.', 'wp-agency');
       }

       // Department validation
       if (!$this->hasAtLeastOneDepartment($data)) {
           $errors['department'] = __('Minimal satu departemen harus dipilih.', 'wp-agency');
       }

       return $errors;
   }

   public function validateUpdate(array $data, int $id): array {
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

       // Permission check
       if (!$this->canEditEmployee($employee, $agency)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk mengedit karyawan ini.', 'wp-agency');
           return $errors;
       }

       // Basic data validation
       $errors = array_merge($errors, $this->validateBasicData($data));

       // Email uniqueness (excluding current ID)
       if (!empty($data['email']) && $this->employee_model->existsByEmail($data['email'], $id)) {
           $errors['email'] = __('Email sudah digunakan.', 'wp-agency');
       }

       // Department validation on update
       if (!$this->hasAtLeastOneDepartment($data)) {
           $errors['department'] = __('Minimal satu departemen harus dipilih.', 'wp-agency');
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

       // Permission check
       if (!$this->canDeleteEmployee($employee, $agency)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk menghapus karyawan ini.', 'wp-agency');
       }

       return $errors;
   }

   public function validateView(int $id): array {
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

       // Permission check
       if (!$this->canViewEmployee($employee, $agency)) {
           $errors['permission'] = __('Anda tidak memiliki izin untuk melihat detail karyawan ini.', 'wp-agency');
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
           "SELECT COUNT(*) FROM {$wpdb->prefix}app_divisiones 
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
}
