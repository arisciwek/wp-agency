<?php
/**
 * Agency Employee Validator Class
 *
 * @package     WP_Agency
 * @subpackage  Validators/Employee
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: src/Validators/Employee/AgencyEmployeeValidator.php
 *
 * Description: Validator untuk operasi CRUD Employee.
 *              Extends AbstractValidator dari wp-app-core.
 *              Memastikan semua input data valid sebelum diproses model.
 *              Menyediakan validasi untuk create, update, dan delete.
 *
 * Changelog:
 * 1.1.0 - 2025-12-28
 * - Refactored to extend AbstractValidator from wp-app-core
 * - Implemented 13 abstract methods required by AbstractValidator
 * - Moved getUserRelation() to AgencyEmployeeModel
 * - Renamed canView/canEdit/canDelete to checkViewPermission/checkUpdatePermission/checkDeletePermission
 * - Kept custom employee validation methods
 *
 * 1.0.0 - 2024-12-10
 * - Initial release
 */

namespace WPAgency\Validators\Employee;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Cache\AgencyCacheManager;

class AgencyEmployeeValidator extends AbstractValidator {

    private AgencyEmployeeModel $model;
    private AgencyModel $agencyModel;
    private DivisionModel $divisionModel;
    private AgencyCacheManager $cache;
    protected array $relationCache = [];

    public function __construct() {
        $this->model = new AgencyEmployeeModel();
        $this->agencyModel = new AgencyModel();
        $this->divisionModel = new DivisionModel();
        $this->cache = AgencyCacheManager::getInstance();
    }

    // ========================================
    // IMPLEMENT 13 ABSTRACT METHODS
    // ========================================

    protected function getEntityName(): string {
        return 'employee';
    }

    protected function getEntityDisplayName(): string {
        return 'Karyawan';
    }

    protected function getTextDomain(): string {
        return 'wp-agency';
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCreateCapability(): string {
        return 'add_agency_employee';
    }

    protected function getViewCapabilities(): array {
        return ['view_agency_employee_detail', 'view_own_agency_employee'];
    }

    protected function getUpdateCapabilities(): array {
        return ['edit_all_agency_employees', 'edit_own_agency_employee'];
    }

    protected function getDeleteCapability(): string {
        return 'delete_agency_employee';
    }

    protected function getListCapability(): string {
        return 'view_agency_employee_list';
    }

    protected function validateFormFields(array $data, ?int $id = null): array {
        return $this->validateBasicData($data);
    }

    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('view_own_agency_employee')) return true;
        if ($relation['is_division_admin'] && current_user_can('view_own_agency_employee')) return true;
        if ($relation['is_self'] && current_user_can('view_own_agency_employee')) return true;
        if ($relation['is_same_division'] && current_user_can('view_agency_employee_detail')) return true;

        return false;
    }

    protected function checkUpdatePermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('edit_own_agency_employee')) return true;
        if ($relation['is_division_admin'] && current_user_can('edit_own_agency_employee')) return true;

        return false;
    }

    protected function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_agency_employee')) return true;
        if ($relation['is_agency_admin']) return true;

        return false;
    }

    // ========================================
    // CUSTOM EMPLOYEE METHODS
    // ========================================

    public function canCreateEmployee($agency_id, $division_id): bool {
        $current_user_id = get_current_user_id();

        // 1. Agency Owner Check
        $agency = $this->agencyModel->find($agency_id);
        if ($agency && (int)$agency->user_id === (int)$current_user_id) {
            return true;
        }

        // 2. Division Admin Check
        if ($division_id && $this->isDivisionAdmin($current_user_id, $division_id)) {
            return true;
        }

        // 3. System Admin Check
        if (current_user_can('add_agency_employee')) {
            return true;
        }

        return apply_filters('wp_agency_can_create_employee', false, $agency_id, $division_id, $current_user_id);
    }

    public function validateView($employee, $agency): array {
        $errors = [];

        if (!$employee || !$agency) {
            $errors['data'] = __('Data tidak valid.', 'wp-agency');
        }

        return $errors;
    }

    public function validateCreate(array $data): array {
        $errors = [];

        // Basic data validation
        $basic_errors = $this->validateBasicData($data);
        if (!empty($basic_errors)) {
            return $basic_errors;
        }

        // Agency ID validation
        $agency_id = $data['agency_id'] ?? 0;
        if (!$agency_id) {
            $errors['agency_id'] = __('Agency ID tidak valid', 'wp-agency');
            return $errors;
        }

        // Pastikan minimal ada satu division atau agency assignment
        if (!$this->hasAtLeastOneDepartment($data)) {
            $errors['assignment'] = __('Karyawan harus ditugaskan minimal ke satu divisi atau agency', 'wp-agency');
        }

        // Email unique validation
        if (!empty($data['email'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE email = %s",
                $data['email']
            ));

            if ($exists) {
                $errors['email'] = __('Email sudah digunakan oleh karyawan lain', 'wp-agency');
            }
        }

        // Phone unique validation
        if (!empty($data['phone'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE phone = %s",
                $data['phone']
            ));

            if ($exists) {
                $errors['phone'] = __('Nomor telepon sudah digunakan oleh karyawan lain', 'wp-agency');
            }
        }

        // NIP unique validation within agency
        if (!empty($data['nip'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees
                 WHERE nip = %s AND agency_id = %d",
                $data['nip'],
                $agency_id
            ));

            if ($exists) {
                $errors['nip'] = __('NIP sudah digunakan oleh karyawan lain di agency ini', 'wp-agency');
            }
        }

        return $errors;
    }

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Basic data validation (only for fields being updated)
        $basic_errors = $this->validateBasicData($data);
        if (!empty($basic_errors)) {
            return $basic_errors;
        }

        // Email unique validation (exclude current record)
        if (!empty($data['email'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE email = %s AND id != %d",
                $data['email'],
                $id
            ));

            if ($exists) {
                $errors['email'] = __('Email sudah digunakan oleh karyawan lain', 'wp-agency');
            }
        }

        // Phone unique validation (exclude current record)
        if (!empty($data['phone'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE phone = %s AND id != %d",
                $data['phone'],
                $id
            ));

            if ($exists) {
                $errors['phone'] = __('Nomor telepon sudah digunakan oleh karyawan lain', 'wp-agency');
            }
        }

        // NIP unique validation within agency (exclude current record)
        if (!empty($data['nip'])) {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees
                 WHERE nip = %s AND agency_id = %d AND id != %d",
                $data['nip'],
                $employee->agency_id,
                $id
            ));

            if ($exists) {
                $errors['nip'] = __('NIP sudah digunakan oleh karyawan lain di agency ini', 'wp-agency');
            }
        }

        return $errors;
    }

    public function validateDelete(int $id): array {
        $errors = [];

        // Check if employee exists
        $employee = $this->model->find($id);
        if (!$employee) {
            $errors['id'] = __('Karyawan tidak ditemukan', 'wp-agency');
            return $errors;
        }

        // Get agency for permission check
        $agency = $this->agencyModel->find($employee->agency_id);
        if (!$agency) {
            $errors['id'] = __('Agency tidak ditemukan', 'wp-agency');
            return $errors;
        }

        return $errors;
    }

    public function validateAccess(int $employee_id): array {
        $cache_key = 'employee_access_' . $employee_id . '_' . get_current_user_id();
        $cached_access = $this->cache->get($cache_key);

        if ($cached_access !== false) {
            return $cached_access;
        }

        $relation = $this->model->getUserRelation($employee_id);

        $access_result = [
            'has_access' => $this->checkViewPermission($relation),
            'access_type' => $this->getAccessType($relation),
            'relation' => $relation
        ];

        $this->cache->set($cache_key, $access_result, 10 * MINUTE_IN_SECONDS);

        return $access_result;
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    private function getAccessType(array $relation): string {
        if ($relation['is_admin']) return 'admin';
        if ($relation['is_agency_admin']) return 'agency_admin';
        if ($relation['is_division_admin']) return 'division_admin';
        if ($relation['is_self']) return 'self';
        if ($relation['is_same_division']) return 'same_division';
        if ($relation['is_same_agency']) return 'same_agency';
        return 'none';
    }

    private function validateBasicData(array $data): array {
        $errors = [];

        // Name validation
        if (isset($data['full_name'])) {
            $name = trim($data['full_name']);
            if (empty($name)) {
                $errors['full_name'] = __('Nama lengkap wajib diisi.', 'wp-agency');
            } elseif (mb_strlen($name) > 100) {
                $errors['full_name'] = __('Nama lengkap maksimal 100 karakter.', 'wp-agency');
            }
        }

        // Email validation
        if (isset($data['email']) && !empty($data['email'])) {
            if (!is_email($data['email'])) {
                $errors['email'] = __('Format email tidak valid.', 'wp-agency');
            }
        }

        // Phone validation
        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^[0-9\+\-\(\) ]+$/', $data['phone'])) {
                $errors['phone'] = __('Format nomor telepon tidak valid.', 'wp-agency');
            }
        }

        return $errors;
    }

    private function hasAtLeastOneDepartment(array $data): bool {
        return !empty($data['division_id']) || !empty($data['agency_id']);
    }

    private function isDivisionAdmin($user_id, $division_id): bool {
        $division = $this->divisionModel->find($division_id);
        return $division && (int)$division->user_id === (int)$user_id;
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
