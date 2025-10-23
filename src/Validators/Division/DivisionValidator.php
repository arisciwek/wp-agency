<?php
/**
* Division Validator Class
*
* @package     WP_Agency
* @subpackage  Validators/Division
* @version     1.0.7
* @author      arisciwek
*
* Path: src/Validators/Division/DivisionValidator.php
*
* Description: Validator untuk operasi CRUD Division.
*              Memastikan semua input data valid sebelum diproses model.
*              Menyediakan validasi untuk create, update, dan delete.
*              Includes validasi permission dan ownership.
*
* Changelog:
* 1.0.0 - 2024-12-10
* - Initial release
* - Added create validation
* - Added update validation
* - Added delete validation
* - Added permission validation
* 
* 
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
 * 2. Regular User Rights:
 *    - Users with edit_own_division can only edit divisions they created
 *    - Created_by field determines ownership for regular users
 * 
 * 3. Staff Rights:
 *    - Staff members (in agency_employees table) can view but not edit
 *    - View rights are automatic for agency scope
 * 
 * 4. Administrator Rights:
 *    - Only administrators use edit_all_divisions capability
 *    - This is for system-wide access, not agency-scope access
 *    
 * Example:
 * - If user is agency owner: Can edit all divisions under their agency
 * - If user has edit_own_division: Can only edit divisions where created_by matches
 * - If user has edit_division: System administrator with full access
 */

namespace WPAgency\Validators\Division;

use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Cache\AgencyCacheManager;

class DivisionValidator {
    private $division_model;
    private $agency_model;
    private AgencyCacheManager $cache;

    public function __construct() {
        $this->division_model = new DivisionModel();
        $this->agency_model = new AgencyModel();
        $this->cache = new AgencyCacheManager();
    }

    public function validateAccess(int $division_id): array {
        // Cek cache untuk hasil validateAccess terlebih dahulu
        $cache_key = 'division_access_' . $division_id . '_' . get_current_user_id();
        $cached_access = $this->cache->get($cache_key);
        
        if ($cached_access !== null) {
            return $cached_access;
        }
        
        // Jika tidak ada di cache, lakukan validasi
        $relation = $this->getUserRelation($division_id);
        
        // Dapatkan data division untuk mendapatkan agency_id
        // Gunakan cache jika tersedia
        $cached_division = $this->cache->get('division', $division_id);
        
        if ($cached_division !== null) {
            $division = $cached_division;
        } else {
            $division = $this->division_model->find($division_id);
        }
        
        $agency_id = $division ? $division->agency_id : 0;
        
        $access_result = [
            'has_access' => $this->canViewDivision($relation),
            'access_type' => $this->getAccessType($relation),
            'relation' => $relation,
            'agency_id' => $agency_id
        ];
        
        // Simpan hasil ke cache dengan waktu lebih singkat (10 menit)
        // Karena akses permission bisa berubah lebih sering
        $this->cache->set($cache_key, $access_result, 10 * MINUTE_IN_SECONDS);
        
        return $access_result;
    }

    public function invalidateAccessCache(int $division_id, ?int $user_id = null): void {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $cache_key = 'division_access_' . $division_id . '_' . $user_id;
        $this->cache->delete($cache_key);
    }
    
    private function getAccessType(array $relation): string {
        if ($relation['is_admin']) return 'admin';
        if ($relation['is_owner']) return 'owner';
        if ($relation['is_employee']) return 'employee';
        return 'none';
    }

    /**
     * Get user relation with division
     *
     * @param int $division_id
     * @return array Array containing is_admin, is_owner, is_employee flags
     */
    public function getUserRelation(int $division_id): array {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Default relation
        $relation = [
            'is_admin' => current_user_can('edit_all_divisions'),
            'is_owner' => false,
            'is_employee' => false,
            'is_division_admin' => false
        ];

        // Jika tidak ada division_id, kembalikan default
        if (!$division_id) {
            return $relation;
        }

        // Dapatkan data division dari cache dulu
        $division = $this->cache->get('division', $division_id);
        
        // Jika tidak ada di cache, ambil dari database
        if ($division === null) {
            $division = $this->division_model->find($division_id);
            
            // Simpan ke cache untuk penggunaan berikutnya
            if ($division) {
                $this->cache->set('division', $division, 3600, $division_id);
            }
        }

        if (!$division) {
            return $relation;
        }

        // Dapatkan data agency dari cache dulu
        $agency = $this->cache->get('agency', $division->agency_id);
        
        // Jika tidak ada di cache, ambil dari database
        if ($agency === null) {
            $agency = $this->agency_model->find($division->agency_id);
            
            // Simpan ke cache untuk penggunaan berikutnya
            if ($agency) {
                $this->cache->set('agency', $agency, 3600, $division->agency_id);
            }
        }

        if (!$agency) {
            return $relation;
        }

        // Isi data relation
        $relation['is_owner'] = ((int)$agency->user_id === $current_user_id);
        $relation['is_division_admin'] = ((int)$division->user_id === $current_user_id);

        // Gunakan method dari model untuk cek status employee
        $relation['is_employee'] = $this->division_model->isEmployeeActive($division_id, $current_user_id);

        return $relation;
    }

    public function canViewDivision(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('view_own_division')) return true;
        if ($relation['is_division_admin'] && current_user_can('view_own_division')) return true;
        if ($relation['is_employee'] && current_user_can('view_division_detail')) return true;
        
        return false;
    }

    public function canCreateDivision($agency_id): bool {
        $current_user_id = get_current_user_id();

        // 1. Agency Owner Check - per docs, this is exclusive to owner
        $agency = $this->agency_model->find($agency_id);
        if ($agency && (int)$agency->user_id === (int)$current_user_id) {
            return true;
        }

        // 2. System Admin Check with explicit capability
        if (current_user_can('add_division')) {
            return true;
        }

        return apply_filters('wp_agency_can_create_division', false, $agency_id, $current_user_id);
    }

    public function canEditDivision(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_owner'] && current_user_can('edit_own_division')) return true;
        if ($relation['is_division_admin'] && current_user_can('edit_own_division')) return true;
        
        return false;
    }

    public function canDeleteDivision(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_division')) return true;
        if ($relation['is_owner']) return true;
        if ($relation['is_division_admin'] && current_user_can('delete_division')) return true;
        
        return false;
    }

    public function validateView($division, $agency): array {
        $errors = [];
        
        // Hanya validasi bahwa data yang dibutuhkan ada
        if (!$division || !$agency) {
            $errors['data'] = __('Data tidak valid.', 'wp-agency');
        }

        return $errors;
    }

    public function validateUpdate(array $data, int $id): array {
        $errors = [];

        // Check if division exists
        $division = $this->division_model->find($id);
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
        $division = $this->division_model->find($id);
        if (!$division) {
            $errors['id'] = __('Cabang tidak ditemukan.', 'wp-agency');
            return $errors;
        }

        // Get agency for permission check
        $agency = $this->agency_model->find($division->agency_id);
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

    private function isStaffMember($user_id, $division_id) {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees 
             WHERE user_id = %d AND division_id = %d AND status = 'active'",
            $user_id, $division_id
        ));
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
        
        if ($cached_result !== null) {
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
        
        if ($cached_result !== null) {
            return $cached_result;
        }
        
        // If not changing to 'cabang', no validation needed
        if ($new_type !== 'cabang') {
            $result = ['valid' => true];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Get current division type - gunakan caching dari DivisionModel.find() jika memungkinkan
        $division_model = new \WPAgency\Models\Division\DivisionModel();
        $division = $division_model->find($division_id);
        
        // Jika tidak ditemukan division atau type bukan pusat, tidak perlu validasi
        if (!$division || $division->type !== 'pusat') {
            $result = ['valid' => true];
            $this->cache->set($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Hitung jumlah kantor pusat - gunakan metode cached dari DivisionModel
        $pusat_count = $division_model->countPusatByAgency($agency_id);
        
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

    // Tambahkan cache untuk validateDivisionTypeDelete
    public function validateDivisionTypeDelete(int $division_id): array {
        global $wpdb;

        // Cek cache terlebih dahulu
        $cache_key = 'division_type_delete_validation_' . $division_id;
        $cached_result = $this->cache->get($cache_key);

        if ($cached_result !== null) {
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
