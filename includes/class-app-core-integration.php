<?php
/**
 * WP App Core Integration Class
 *
 * @package     WP_Agency
 * @subpackage  Includes
 * @version     1.7.0
 * @author      arisciwek
 *
 * Path: /wp-agency/includes/class-app-core-integration.php
 *
 * Description: Integration layer untuk menghubungkan wp-agency dengan wp-app-core.
 *              Menyediakan user info untuk admin bar dan fitur shared lainnya.
 *
 * Usage:
 * - Dipanggil otomatis saat wp-app-core aktif
 * - Menyediakan callback untuk mendapatkan user info agency
 * - Role names disediakan langsung via $result['role_names'] (no filters needed)
 *
 * Changelog:
 * 1.7.0 - 2025-01-18
 * - CLEANUP (Review-02): Removed redundant role name filters (9 filters)
 * - Reason: Role names now provided via $result['role_names'] from Model
 * - Removed: get_role_name() method (no longer needed)
 * - Simplified: init() method now only registers plugin with app core
 * - Benefits: Less code, no hardcoded filters, fully dynamic role handling
 *
 * 1.6.0 - 2025-01-18
 * - REFACTOR: Moved getUserInfo() query to AgencyEmployeeModel for reusability
 * - Added: Cache support in AgencyEmployeeModel::getUserInfo()
 * - Improved: get_user_info() now delegates to Model layer
 * - Benefits: Cleaner separation of concerns, cacheable, reusable across codebase
 *
 * 1.5.0 - 2025-01-18
 * - CRITICAL OPTIMIZATION: Replaced 3 separate queries with 1 comprehensive query
 * - Performance: Reduced from 3 database queries to just 1 query for all user types
 * - Added: JOIN with wp_users and wp_usermeta for email and capabilities
 * - Simplified: Single query handles employee, division admin, and agency owner
 * - Rationale: wp_app_agency_employees table already has user_id, division_id, agency_id
 * - Benefits: Faster execution, less database load, cleaner code structure
 *
 * 1.4.0 - 2025-01-18
 * - MAJOR IMPROVEMENT: Complete query rewrite for all user types
 * - Added: Comprehensive data retrieval with INNER/LEFT JOIN
 * - Added: GROUP_CONCAT for multiple jurisdiction_codes
 * - Added: MAX() aggregation for single-value fields
 * - Added: Subquery with GROUP BY to avoid duplicates
 * - Added: New fields: division_code, jurisdiction_codes, is_primary_jurisdiction
 * - Improved: Employee query now includes ALL related data in single query
 * - Improved: Division admin query includes jurisdictions
 * - Improved: Agency owner query gets first division with jurisdictions
 * - Result: Complete data structure with all necessary information
 *
 * 1.3.0 - 2025-01-18
 * - CRITICAL FIX: Reordered query priority in get_user_info()
 * - Changed: Check employee FIRST (most common case), not agency owner
 * - Fixed: Employee table has agency_id directly, no need to check owner first
 * - Improved: Query order now: Employee → Division Admin → Agency Owner → Fallback
 * - Rationale: Not all employees are agency owners, check employee record first
 *
 * 1.2.0 - 2025-01-18
 * - Changed: init() method now uses explicit add_filter for each role (like wp-customer)
 * - Changed: Replaced loop with explicit filter registration for all 9 agency roles
 * - Added: Comprehensive debug logging for all queries and results
 * - Improved: Better traceability with detailed error_log output
 *
 * 1.1.0 - 2025-01-18
 * - Fixed: Removed hardcoded "Dinas Tenaga Kerja" and "DISNAKER"
 * - Fixed: Changed branch_name/branch_type to division_name/division_type (correct terminology)
 * - Fixed: Agency owner now shows first division instead of hardcoded "Kantor Pusat"
 * - Fixed: Fallback uses "Agency System" with role name as division (not hardcoded agency name)
 * - Consistent field names across all return structures
 *
 * 1.0.0 - 2025-01-18
 * - Initial creation
 * - Integration dengan wp-app-core admin bar
 * - Support untuk agency roles
 */

defined('ABSPATH') || exit;

class WP_Agency_App_Core_Integration {

    /**
     * Initialize integration
     */
    public static function init() {
        // Check if wp-app-core is active
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        // Register agency plugin with app core
        add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register_with_app_core']);

        // Note: Role name filters removed in v1.7.0 (Review-02)
        // Role names are now provided directly in getUserInfo() result array
        // via $result['role_names'] from getRoleNamesFromCapabilities()
    }

    /**
     * Register agency plugin with app core
     */
    public static function register_with_app_core() {
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        WP_App_Core_Admin_Bar_Info::register_plugin('agency', [
            'roles' => WP_Agency_Role_Manager::getRoleSlugs(),
            'get_user_info' => [__CLASS__, 'get_user_info'],
        ]);
    }

    /**
     * Get user information for admin bar
     *
     * @param int $user_id
     * @return array|null
     */
    public static function get_user_info($user_id) {
        // DEBUG: Log function call
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_Agency get_user_info START for user_id: {$user_id} ===");
        }

        $result = null;

        // Delegate to AgencyEmployeeModel for data retrieval
        // This provides caching and makes the query reusable
        $employee_model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
        $result = $employee_model->getUserInfo($user_id);

        // DEBUG: Log result from model
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($result) {
                error_log("USER DATA FROM MODEL: " . print_r($result, true));
            } else {
                error_log("No employee data found in model");
            }
        }

        // Fallback: If user has agency role but no entity link, show role-based info
        if (!$result) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $agency_roles = WP_Agency_Role_Manager::getRoleSlugs();
                $user_roles = (array) $user->roles;

                // DEBUG: Log user roles
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("User Roles: " . print_r($user_roles, true));
                    error_log("Agency Roles (available): " . print_r($agency_roles, true));
                }

                // Check if user has any agency role
                $has_agency_role = false;
                foreach ($agency_roles as $role_slug) {
                    if (in_array($role_slug, $user_roles)) {
                        $has_agency_role = true;
                        break;
                    }
                }

                // DEBUG: Log role check result
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Has Agency Role: " . ($has_agency_role ? 'YES' : 'NO'));
                }

                if ($has_agency_role) {
                    // Get first agency role for display
                    $first_agency_role = null;
                    foreach ($agency_roles as $role_slug) {
                        if (in_array($role_slug, $user_roles)) {
                            $first_agency_role = $role_slug;
                            break;
                        }
                    }

                    $role_name = WP_Agency_Role_Manager::getRoleName($first_agency_role);

                    // For users without entity link, show role-based info
                    // Use role name as division name
                    $result = [
                        'entity_name' => 'Agency System', // Generic for system roles
                        'entity_code' => 'AGENCY',
                        'division_id' => null,
                        'division_name' => $role_name ?? 'Staff',
                        'division_type' => 'admin',
                        'relation_type' => 'role_only',
                        'icon' => '🏛️'
                    ];

                    // DEBUG: Log fallback result
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("FALLBACK RESULT: " . print_r($result, true));
                    }
                }
            }
        }

        // DEBUG: Log final result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_Agency get_user_info END - Final Result: " . print_r($result ?? null, true) . " ===");
        }

        return $result ?? null;
    }
}
