<?php
/**
 * Agency Validator
 *
 * @package     WP_Agency
 * @subpackage  Validators
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Validators/AgencyValidator.php
 *
 * Description: Validator untuk Agency CRUD operations.
 *              Extends AbstractValidator dari wp-app-core.
 *              Handles form validation dan permission checks.
 *
 * Changelog:
 * 2.0.0 - 2025-12-28 (AbstractCRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractValidator
 * - Code reduction: 330 lines → ~304 lines
 * - Implements 13 abstract methods dari AbstractValidator
 * - getUserRelation() calls AgencyModel->getUserRelation()
 * - Custom validation: Agency name uniqueness, delete checks
 * - Adapted from CustomerValidator pattern
 */

namespace WPAgency\Validators;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Cache\AgencyCacheManager;

defined('ABSPATH') || exit;

class AgencyValidator extends AbstractValidator {

    /**
     * @var AgencyModel
     */
    private $model;

    /**
     * @var AgencyCacheManager
     */
    private $cache;

    /**
     * Relation cache (in-memory)
     * Must be protected array (same as parent AbstractValidator)
     */
    protected array $relationCache = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new AgencyModel();
        $this->cache = AgencyCacheManager::getInstance();
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (13 required)
    // ========================================

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'agency';
    }

    /**
     * Get entity display name
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Agency';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-agency';
    }

    /**
     * Get model instance
     *
     * @return AgencyModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get create capability
     *
     * @return string
     */
    protected function getCreateCapability(): string {
        return 'add_agency';
    }

    /**
     * Get view capabilities
     *
     * @return array
     */
    protected function getViewCapabilities(): array {
        return ['view_agency_detail', 'view_own_agency'];
    }

    /**
     * Get update capabilities
     *
     * @return array
     */
    protected function getUpdateCapabilities(): array {
        return ['edit_all_agencies', 'edit_own_agency'];
    }

    /**
     * Get delete capability
     *
     * @return string
     */
    protected function getDeleteCapability(): string {
        return 'delete_agency';
    }

    /**
     * Get list capability
     *
     * @return string
     */
    protected function getListCapability(): string {
        return 'view_agency_list';
    }

    /**
     * Validate create operation
     *
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateCreate(array $data): array {
        return $this->validateForm($data);
    }

    /**
     * Validate update operation
     *
     * @param int $id Entity ID
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateUpdate(int $id, array $data): array {
        return $this->validateForm($data, $id);
    }

    /**
     * Validate view operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateView(int $id): array {
        $relation = $this->getUserRelation($id);

        if (!$this->canView($relation)) {
            return ['permission' => __('Anda tidak memiliki akses untuk melihat agency ini.', 'wp-agency')];
        }

        return [];
    }

    /**
     * Validate delete operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateDeleteOperation(int $id): array {
        return $this->validateDelete($id);
    }

    /**
     * Check if user can create
     *
     * @return bool
     */
    protected function canCreate(): bool {
        return current_user_can('add_agency');
    }

    /**
     * Check if user can update
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canUpdateEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canUpdate($relation);
    }

    /**
     * Check if user can view
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canViewEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canView($relation);
    }

    /**
     * Check if user can delete
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canDeleteEntity(int $id): bool {
        $relation = $this->getUserRelation($id);
        return $this->canDelete($relation);
    }

    /**
     * Check if user can list
     *
     * @return bool
     */
    protected function canList(): bool {
        return current_user_can('view_agency_list');
    }

    // ========================================
    // CUSTOM VALIDATION METHODS
    // ========================================

    /**
     * Validate form fields (implements abstract method)
     *
     * @param array $data Data to validate
     * @param int|null $id Entity ID (for update)
     * @return array Errors (empty if valid)
     */
    protected function validateFormFields(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama agency wajib diisi.', 'wp-agency');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama agency maksimal 100 karakter.', 'wp-agency');
        } elseif ($this->model->existsByName($name, $id)) {
            $errors['name'] = __('Nama agency sudah ada.', 'wp-agency');
        }

        return $errors;
    }

    /**
     * Validate delete operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    public function validateDelete(int $id): array {
        $errors = [];

        // Check permission
        if (!current_user_can('delete_agency')) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus agency', 'wp-agency');
            return $errors;
        }

        // Check if agency exists
        $agency = $this->model->find($id);
        if (!$agency) {
            $errors[] = __('Agency tidak ditemukan', 'wp-agency');
            return $errors;
        }

        // Check relation permission
        if (!$this->canDelete($this->getUserRelation($id))) {
            $errors[] = __('Anda tidak memiliki izin untuk menghapus agency ini', 'wp-agency');
            return $errors;
        }

        // Check if agency has divisions
        $division_count = $this->model->getDivisionCount($id);
        if ($division_count > 0) {
            $errors[] = sprintf(
                __('Agency tidak dapat dihapus karena masih memiliki %d divisi', 'wp-agency'),
                $division_count
            );
        }

        // Check if agency has employees
        $employee_count = $this->model->getEmployeeCount($id);
        if ($employee_count > 0) {
            $errors[] = sprintf(
                __('Agency tidak dapat dihapus karena masih memiliki %d karyawan', 'wp-agency'),
                $employee_count
            );
        }

        return $errors;
    }

    /**
     * Validate access for agency
     *
     * @param int $agency_id Agency ID
     * @return array Access info
     */
    public function validateAccess(int $agency_id): array {
        $relation = $this->getUserRelation($agency_id);

        return [
            'has_access' => $this->canView($relation),
            'access_type' => $relation['access_type'],
            'relation' => $relation,
            'agency_id' => $agency_id
        ];
    }

    // ========================================
    // PERMISSION CHECKING (User Relation)
    // ========================================

    /**
     * Get user relation with agency
     *
     * Delegates to AgencyModel->getUserRelation()
     *
     * @param int $agency_id Agency ID
     * @return array Relation data
     */
    public function getUserRelation(int $agency_id): array {
        $current_user_id = get_current_user_id();

        // Check in-memory cache
        if (isset($this->relationCache[$agency_id])) {
            return $this->relationCache[$agency_id];
        }

        // Call model's getUserRelation (which handles caching)
        $relation = $this->model->getUserRelation($agency_id, $current_user_id);

        // Store in memory cache
        $this->relationCache[$agency_id] = $relation;

        return $relation;
    }

    /**
     * Check if user can view
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canView(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('view_own_agency')) return true;
        if ($relation['is_agency_employee'] && current_user_can('view_own_agency')) return true;
        if ($relation['is_division_head'] && current_user_can('view_own_agency')) return true;
        if (current_user_can('view_agency_detail')) return true;

        return false;
    }

    /**
     * Check if user can update
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canUpdate(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('edit_own_agency')) return true;
        if (current_user_can('edit_all_agencies')) return true;

        return false;
    }

    /**
     * Check if user can delete
     *
     * @param array $relation User relation
     * @return bool
     */
    public function canDelete(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_agency')) return true;
        if (current_user_can('delete_agency')) return true;

        return false;
    }

    /**
     * Check view permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('view_own_agency')) return true;
        if ($relation['is_agency_employee'] && current_user_can('view_own_agency')) return true;
        if ($relation['is_division_head'] && current_user_can('view_own_agency')) return true;
        if (current_user_can('view_agency_detail')) return true;

        return false;
    }

    /**
     * Check update permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkUpdatePermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_agency_admin'] && current_user_can('edit_own_agency')) return true;
        if (current_user_can('edit_all_agencies')) return true;

        return false;
    }

    /**
     * Check delete permission (implements abstract method)
     *
     * @param array $relation User relation
     * @return bool
     */
    protected function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_agency')) return true;
        if (current_user_can('delete_agency')) return true;

        return false;
    }

    /**
     * Clear relation cache
     *
     * @param int|null $agency_id Agency ID (null for all)
     * @return void
     */
    public function clearCache(?int $agency_id = null): void {
        if ($agency_id) {
            unset($this->relationCache[$agency_id]);
        } else {
            $this->relationCache = [];
        }

        $this->cache->clearCache('agency_relation');
    }
}
