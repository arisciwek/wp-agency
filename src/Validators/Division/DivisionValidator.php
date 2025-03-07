<?php
/**
* Division Validator Class
*
* @package     WP_Agency
* @subpackage  Validators/Division
* @version     1.0.0
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

class DivisionValidator {
    private $division_model;
    private $agency_model;

    public function __construct() {
        $this->division_model = new DivisionModel();
        $this->agency_model = new AgencyModel();
    }

    public function validateAccess(int $division_id): array {
        $relation = $this->getUserRelation($division_id);
        
        // Dapatkan data division untuk mendapatkan agency_id
        $division = $this->division_model->find($division_id);
        $agency_id = $division ? $division->agency_id : 0;
        
        return [
            'has_access' => $this->canViewDivision($relation),
            'access_type' => $this->getAccessType($relation),
            'relation' => $relation,
            'agency_id' => $agency_id
        ];
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

        // Dapatkan data division
        $division = $this->division_model->find($division_id);
        if (!$division) {
            return $relation;
        }

        // Dapatkan data agency
        $agency = $this->agency_model->find($division->agency_id);
        if (!$agency) {
            return $relation;
        }

        // Cek apakah user adalah agency owner
        $relation['is_owner'] = ((int)$agency->user_id === $current_user_id);

        // Cek apakah user adalah division admin
        $relation['is_division_admin'] = ((int)$division->user_id === $current_user_id);

        // Cek apakah user adalah employee di division ini
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees 
             WHERE division_id = %d 
             AND user_id = %d 
             AND status = 'active'",
            $division_id,
            $current_user_id
        ));

        $relation['is_employee'] = (int)$is_employee > 0;

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
        
        $division_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_divisions WHERE agency_id = %d",
            $agency_id
        ));

        if ($division_count === '0' && $type !== 'pusat') {
            return [
                'valid' => false,
                'message' => 'Cabang pertama harus bertipe kantor pusat'
            ];
        }

        return ['valid' => true];
    }

    public function validateDivisionTypeChange(int $division_id, string $new_type, int $agency_id): array {
        global $wpdb;
        
        // If not changing to 'cabang', no validation needed
        if ($new_type !== 'cabang') {
            return ['valid' => true];
        }

        // Get current division type
        $current_division = $wpdb->get_row($wpdb->prepare(
            "SELECT type FROM {$wpdb->prefix}app_divisions 
             WHERE id = %d AND agency_id = %d",
            $division_id, $agency_id
        ));

        // If current type is not 'pusat', no validation needed
        if (!$current_division || $current_division->type !== 'pusat') {
            return ['valid' => true];
        }

        // Count remaining 'pusat' divisions excluding current division
        $pusat_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_divisions 
             WHERE agency_id = %d AND type = 'pusat' AND id != %d",
            $agency_id, $division_id
        ));

        if ($pusat_count === '0') {
            return [
                'valid' => false,
                'message' => 'Minimal harus ada 1 kantor pusat. Tidak bisa mengubah tipe kantor pusat terakhir.'
            ];
        }

        return ['valid' => true];
    }

    public function validateDivisionTypeDelete(int $division_id): array {
        global $wpdb;
        
        // Get division details including agency_id and type
        $division = $wpdb->get_row($wpdb->prepare(
            "SELECT type, agency_id FROM {$wpdb->prefix}app_divisions WHERE id = %d",
            $division_id
        ));

        if (!$division) {
            return ['valid' => false, 'message' => 'Division tidak ditemukan'];
        }

        // If not pusat, no validation needed
        if ($division->type !== 'pusat') {
            return ['valid' => true];
        }

        // Count active non-pusat divisions
        $active_divisions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_divisions 
             WHERE agency_id = %d 
             AND type = 'cabang' 
             AND status = 'active'
             AND id != %d",
            $division->agency_id,
            $division_id
        ));

        if ($active_divisions > 0) {
            return [
                'valid' => false,
                'message' => 'Tidak dapat menghapus kantor pusat karena masih ada cabang aktif'
            ];
        }

        return ['valid' => true];
    }

}
