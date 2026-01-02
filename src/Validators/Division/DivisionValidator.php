<?php
/**
* Division Validator Class
*
* @package     WP_Agency
* @subpackage  Validators/Division
* @version     1.1.0
* @author      arisciwek
*
* Path: src/Validators/Division/DivisionValidator.php
*
* Description: Validator untuk operasi CRUD Division.
*              Extends AbstractValidator dari wp-app-core.
*              Memastikan semua input data valid sebelum diproses model.
*              Menyediakan validasi untuk create, update, dan delete.
*              Includes validasi permission dan ownership.
*
* Changelog:
* 1.1.0 - 2025-12-28
* - Refactored to extend AbstractValidator from wp-app-core
* - Implemented 13 abstract methods required by AbstractValidator
* - Moved getUserRelation() to DivisionModel
* - Renamed canView/canEdit/canDelete to checkViewPermission/checkUpdatePermission/checkDeletePermission
* - Kept custom division type validation methods
*
* 1.0.0 - 2024-12-10
* - Initial release
* - Added create validation
* - Added update validation
* - Added delete validation
* - Added permission validation
*/

/**
 * Division Permission Logic
 *
 * Permission hierarchy for division management follows these rules:
 *
 * 1. Agency Owner Rights:
 *    - Owner (user_id in agencies table) has full control of ALL entities under their agency
 *    - No need for *_all_* capabilities
 *    - Can edit/delete any division within their agency scope
 *    - This is ownership-based permission, not capability-based
 *
 * 2. Division Admin Rights:
 *    - User assigned as division.user_id can manage their division
 *    - Can edit but not delete (unless has delete_division capability)
 *
 * 3. Regular User Rights:
 *    - Users with edit_own_division can only edit divisions they created
 *    - Created_by field determines ownership for regular users
 *
 * 4. Staff Rights:
 *    - Staff members (in agency_employees table) can view but not edit
 *    - View rights are automatic for agency scope
 *
 * 5. Administrator Rights:
 *    - Only administrators use edit_all_divisions capability
 *    - This is for system-wide access, not agency-scope access
 *
 * Example:
 * - If user is agency owner: Can edit all divisions under their agency
 * - If user is division admin: Can edit their assigned division
 * - If user has edit_own_division: Can only edit divisions where created_by matches
 * - If user has edit_division: System administrator with full access
 */

namespace WPAgency\Validators\Division;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Cache\AgencyCacheManager;

class DivisionValidator extends AbstractValidator {

    private DivisionModel $model;
    private AgencyCacheManager $cache;
    protected array $relationCache = [];

    public function __construct() {
        $this->model = new DivisionModel();
        $this->cache = AgencyCacheManager::getInstance();
    }

    // ========================================
    // IMPLEMENT 13 ABSTRACT METHODS
    // ========================================

    protected function getEntityName(): string {
        return 'division';
    }

    protected function getEntityDisplayName(): string {
        return 'Cabang';
    }

    protected function getTextDomain(): string {
        return 'wp-agency';
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCreateCapability(): string {
        return 'add_division';
    }

    protected function getViewCapabilities(): array {
        return ['view_division_detail', 'view_own_division'];
    }

    protected function getUpdateCapabilities(): array {
        return ['edit_all_divisions', 'edit_own_division'];
    }

    protected function getDeleteCapability(): string {
        return 'delete_division';
    }

    protected function getListCapability(): string {
        return 'view_division_list';
    }

    protected function validateFormFields(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama cabang wajib diisi.', 'wp-agency');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = __('Nama cabang maksimal 100 karakter.', 'wp-agency');
        }

        // Type validation
        $type = $data['type'] ?? '';
        if (empty($type)) {
            $errors['type'] = __('Tipe cabang wajib dipilih.', 'wp-agency');
        } elseif (!in_array($type, ['pusat', 'cabang'])) {
            $errors['type'] = __('Tipe cabang tidak valid.', 'wp-agency');
        }

        // Agency ID validation
        $agency_id = isset($data['agency_id']) ? (int)$data['agency_id'] : 0;
        if ($agency_id <= 0) {
            $errors['agency_id'] = __('Agency ID tidak valid.', 'wp-agency');
        }

        return $errors;
    }

    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('view_own_division')) return true;
        if ($relation['is_division_admin'] && current_user_can('view_own_division')) return true;
        if ($relation['is_employee'] && current_user_can('view_division_detail')) return true;

        return false;
    }

    public function checkUpdatePermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('edit_own_division')) return true;
        if ($relation['is_division_admin'] && current_user_can('edit_own_division')) return true;

        return false;
    }

    public function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_division')) return true;
        if ($relation['is_owner']) return true;
        if ($relation['is_division_admin'] && current_user_can('delete_division')) return true;

        return false;
    }

    // ========================================
    // CUSTOM DIVISION METHODS
    // ========================================

    public function canCreateDivision($agency_id): bool {
        $current_user_id = get_current_user_id();

        // 1. Agency Owner Check
        $agency_model = new \WPAgency\Models\Agency\AgencyModel();
        $agency = $agency_model->find($agency_id);
        if ($agency && (int)$agency->user_id === (int)$current_user_id) {
            return true;
        }

        // 2. System Admin Check
        if (current_user_can('add_division')) {
            return true;
        }

        return apply_filters('wp_agency_can_create_division', false, $agency_id, $current_user_id);
    }

    public function validateView($division, $agency): array {
        $errors = [];

        if (!$division || !$agency) {
            $errors['data'] = __('Data tidak valid.', 'wp-agency');
        }

        return $errors;
    }

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if division exists
        $division = $this->model->find($id);
        if (!$division) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Validasi type change jika ada
        if ($data['type'] ?? false) {
            $type_validation = $this->validateDivisionTypeChange(
                $id,
                $data['type'],
                $division->agency_id
            );

            if (!$type_validation['valid']) {
                $errors['type'] = $type_validation['message'];
            }
        }

        return $errors;
    }

    public function validateDelete(int $id): array {
        $errors = [];

        // Check if division exists
        $division = $this->model->find($id);
        if (!$division) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Get agency for permission check
        $agency_model = new \WPAgency\Models\Agency\AgencyModel();
        $agency = $agency_model->find($division->agency_id);
        if (!$agency) {
            $errors['id'] = __('Agency tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Division type deletion validation
        $type_validation = $this->validateDivisionTypeDelete($id);
        if (!$type_validation['valid']) {
            $errors['type'] = $type_validation['message'];
        }

        return $errors;
    }

    /**
     * Helper function to sanitize input data
     */
    public function sanitizeInput(array $data): array {
        $sanitized = [];

        if (isset($data['name'])) {
            $sanitized['name'] = trim(sanitize_text_field($data['name']));
        }

        if (isset($data['type'])) {
            $sanitized['type'] = trim(sanitize_text_field($data['type']));
        }

        if (isset($data['agency_id'])) {
            $sanitized['agency_id'] = intval($data['agency_id']);
        }

        return $sanitized;
    }

    public function validateDivisionTypeCreate(string $type, int $agency_id): array {
        global $wpdb;

        // Cek cache terlebih dahulu
        $cache_key = 'division_type_create_validation_' . $agency_id . '_' . $type;
        $cached_result = $this->cache->get($cache_key);

        if ($cached_result !== false) {
            // Cache hit - return cached array
            return $cached_result;
        }

        $division_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d",
            $agency_id
        ));

        $result = ['valid' => true];

        if ($division_count === '0' && $type !== 'pusat') {
            $result = [
                'valid' => false,
                'message' => 'Cabang pertama harus bertipe kantor pusat'
            ];
        }

        // Simpan hasil ke cache - 5 menit
        $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    public function validateDivisionTypeChange(int $division_id, string $new_type, int $agency_id): array {
        global $wpdb;

        // Cek cache terlebih dahulu
        $cache_key = 'division_type_change_validation_' . $division_id . '_' . $new_type;
        $cached_result = $this->cache->get($cache_key);

        if ($cached_result !== false) {
            return $cached_result;
        }

        // If not changing to 'cabang', no validation needed
        if ($new_type !== 'cabang') {
            $result = ['valid' => true];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Get current division type
        $division = $this->model->find($division_id);

        // Jika tidak ditemukan division atau type bukan pusat, tidak perlu validasi
        if (!$division || $division->type !== 'pusat') {
            $result = ['valid' => true];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Hitung jumlah kantor pusat
        $pusat_count = $this->model->countPusatByAgency($agency_id);

        $result = ['valid' => true];
        if ($pusat_count <= 1) {
            $result = [
                'valid' => false,
                'message' => 'Minimal harus ada 1 kantor pusat. Tidak bisa mengubah tipe kantor pusat terakhir.'
            ];
        }

        // Simpan hasil ke cache - 5 menit
        $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    public function validateDivisionTypeDelete(int $division_id): array {
        global $wpdb;

        // Cek cache terlebih dahulu
        $cache_key = 'division_type_delete_validation_' . $division_id;
        $cached_result = $this->cache->get($cache_key);

        if ($cached_result !== false) {
            return $cached_result;
        }

        // Get division details including agency_id and type
        $division = $wpdb->get_row($wpdb->prepare(
            "SELECT type, agency_id FROM {$wpdb->prefix}app_agency_divisions WHERE id = %d",
            $division_id
        ));

        if (!$division) {
            $result = ['valid' => false, 'message' => 'Division tidak ditemukan'];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // If not pusat, no validation needed
        if ($division->type !== 'pusat') {
            $result = ['valid' => true];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Count active non-pusat divisions
        $active_divisions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions
             WHERE agency_id = %d
             AND type = 'cabang'
             AND status = 'active'
             AND id != %d",
            $division->agency_id,
            $division_id
        ));

        if ($active_divisions > 0) {
            $result = [
                'valid' => false,
                'message' => 'Tidak dapat menghapus kantor pusat karena masih ada cabang aktif'
            ];
        } else {
            $result = ['valid' => true];
        }

        // Simpan hasil ke cache
        $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }
}
