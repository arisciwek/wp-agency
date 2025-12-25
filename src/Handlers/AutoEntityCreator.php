<?php
/**
 * Auto Entity Creator Handler
 *
 * @package     WP_Agency
 * @subpackage  Handlers
 * @version     1.0.8
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Handlers/AutoEntityCreator.php
 *
 * Description: Handles automatic entity creation and cascade delete via hooks
 *              - Creates division pusat when agency is created
 *              - Creates user when division is created (if admin data provided)
 *              - Creates employee when division is created
 *              - Cascade delete/deactivate employees when division is deleted
 *              Following wp-customer pattern for consistency
 *
 * Changelog:
 * 1.0.8 - 2025-11-04 (FIX: Use province_id/regency_id for division pusat)
 * - CRITICAL FIX: Changed from provinsi_code/regency_code to province_id/regency_id
 * - Updated handleAgencyCreated(): division_data now uses province_id/regency_id from agency_data
 * - Matches current DivisionsDB schema (ID-based FKs, not code-based)
 * - Fixes division pusat created with NULL regency_id (causing jurisdiction validation error)
 * - Auto-created division pusat now properly inherits location from parent agency
 *
 * 2.1.0 - 2025-10-22
 * - Added handleDivisionDeleted() for cascade delete employees
 * - Demo mode (WP_AGENCY_DEVELOPMENT=true): HARD DELETE (remove from DB)
 * - Production mode: SOFT DELETE (set status='inactive')
 * - Registered wp_agency_division_deleted hook in wp-agency.php
 *
 * 2.0.0 - 2025-01-22 (Task-2068 Division User Auto-Creation)
 * - BREAKING: User creation moved from Controller to Hook
 * - Added createDivisionUser() method for user creation via hook
 * - Updated handleDivisionCreated() to create user if admin data provided
 * - Division.user_id updated after user creation
 * - Consistent with agency creation pattern (hook-based)
 *
 * 1.0.0 - 2025-01-22
 * - Task-2066: Initial implementation
 * - Added handleAgencyCreated() for auto-create division pusat
 * - Added handleDivisionCreated() for auto-create employee
 * - Integrated with hook system (wp_agency_created, wp_agency_division_created)
 */

namespace WPAgency\Handlers;

use WPAgency\Models\Division\DivisionModel;
use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Models\Agency\AgencyModel;

class AutoEntityCreator {

    private DivisionModel $divisionModel;
    private AgencyEmployeeModel $employeeModel;
    private AgencyModel $agencyModel;

    public function __construct() {
        $this->divisionModel = new DivisionModel();
        $this->employeeModel = new AgencyEmployeeModel();
        $this->agencyModel = new AgencyModel();
    }

    /**
     * Handle agency created event
     * Automatically creates division pusat for the new agency
     *
     * @param int $agency_id The newly created agency ID
     * @param array $agency_data The agency data used for creation
     */
    public function handleAgencyCreated(int $agency_id, array $agency_data): void {
        error_log("[AutoEntityCreator] handleAgencyCreated triggered for agency ID: {$agency_id}");

        try {
            // Validate agency exists
            $agency = $this->agencyModel->find($agency_id);
            if (!$agency) {
                error_log("[AutoEntityCreator] Agency not found: {$agency_id}");
                return;
            }

            // Check if division pusat already exists
            $existing_pusat = $this->divisionModel->findPusatByAgency($agency_id);
            if ($existing_pusat) {
                error_log("[AutoEntityCreator] Division pusat already exists for agency {$agency_id}");
                return;
            }

            // Prepare division pusat data
            $division_data = [
                'agency_id' => $agency_id,
                'name' => $agency->name . ' - Pusat',
                'type' => 'pusat',
                'status' => 'active',
                'province_id' => $agency_data['province_id'] ?? null,
                'regency_id' => $agency_data['regency_id'] ?? null,
                'user_id' => $agency_data['user_id'] ?? null,
                'created_by' => $agency_data['created_by'] ?? get_current_user_id()
            ];

            error_log("[AutoEntityCreator] Creating division pusat with data: " . print_r($division_data, true));

            // Create division pusat
            $division_id = $this->divisionModel->create($division_data);

            if ($division_id) {
                error_log("[AutoEntityCreator] Successfully created division pusat ID: {$division_id} for agency {$agency_id}");
            } else {
                error_log("[AutoEntityCreator] Failed to create division pusat for agency {$agency_id}");
            }

        } catch (\Exception $e) {
            error_log("[AutoEntityCreator] Error in handleAgencyCreated: " . $e->getMessage());
            error_log("[AutoEntityCreator] Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Handle division created event
     * Automatically creates user (if admin data provided) and employee
     *
     * @param int $division_id The newly created division ID
     * @param array $division_data The division data used for creation
     */
    public function handleDivisionCreated(int $division_id, array $division_data): void {
        error_log("[AutoEntityCreator] handleDivisionCreated triggered for division ID: {$division_id}");

        try {
            // Validate division exists
            $division = $this->divisionModel->find($division_id);
            if (!$division) {
                error_log("[AutoEntityCreator] Division not found: {$division_id}");
                return;
            }

            // Get agency info
            $agency = $this->agencyModel->find($division->agency_id);
            if (!$agency) {
                error_log("[AutoEntityCreator] Agency not found for division {$division_id}");
                return;
            }

            // STEP 1: Check if admin data provided → create new user
            $has_admin_data = !empty($division_data['admin_username']) && !empty($division_data['admin_email']);
            $user_id = null;

            if ($has_admin_data) {
                error_log("[AutoEntityCreator] Admin data provided, creating new user for division {$division_id}");

                try {
                    // Create user via hook
                    $user_id = $this->createDivisionUser($division_data);

                    // Update division.user_id to new user
                    global $wpdb;
                    $update_result = $wpdb->update(
                        $wpdb->prefix . 'app_agency_divisions',
                        ['user_id' => $user_id],
                        ['id' => $division_id],
                        ['%d'],
                        ['%d']
                    );

                    if ($update_result === false) {
                        error_log("[AutoEntityCreator] Failed to update division.user_id: " . $wpdb->last_error);
                        // Continue with existing user_id
                        $user_id = $division_data['user_id'] ?? null;
                    } else {
                        error_log("[AutoEntityCreator] Successfully updated division {$division_id} with new user {$user_id}");
                    }

                } catch (\Exception $e) {
                    error_log("[AutoEntityCreator] Failed to create division user: " . $e->getMessage());
                    // Fallback to existing user_id
                    $user_id = $division_data['user_id'] ?? null;
                }
            } else {
                // Use existing user_id from division data (inherited from agency)
                $user_id = $division_data['user_id'] ?? null;
                error_log("[AutoEntityCreator] No admin data, using existing user_id: {$user_id}");
            }

            // STEP 2: Validate user_id exists
            if (!$user_id) {
                error_log("[AutoEntityCreator] No user_id available, skipping employee creation");
                return;
            }

            // STEP 3: Check if employee already exists for this user
            // Check by email to match database UNIQUE constraint
            $user_temp = get_userdata($user_id);
            if ($user_temp) {
                global $wpdb;
                $existing_by_email = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE email = %s",
                    $user_temp->user_email
                ));
                if ($existing_by_email) {
                    error_log("[AutoEntityCreator] Employee already exists with email {$user_temp->user_email} (ID: {$existing_by_email}), skipping creation");
                    return;
                }
            }

            // STEP 4: Get user info
            $user = get_userdata($user_id);
            if (!$user) {
                error_log("[AutoEntityCreator] User not found: {$user_id}");
                return;
            }

            // STEP 5: Create employee
            $employee_data = [
                'agency_id' => $division->agency_id,
                'division_id' => $division_id,
                'user_id' => $user_id,
                'name' => $user->display_name,
                'position' => 'Admin',
                'keterangan' => 'Auto-created by system',
                'email' => $user->user_email,
                'phone' => '-',
                'status' => 'active',
                'created_by' => $division_data['created_by'] ?? get_current_user_id()
            ];

            error_log("[AutoEntityCreator] Creating employee with data: " . print_r($employee_data, true));

            $employee_id = $this->employeeModel->create($employee_data);

            if ($employee_id) {
                error_log("[AutoEntityCreator] Successfully created employee ID: {$employee_id} for division {$division_id}");
            } else {
                error_log("[AutoEntityCreator] Failed to create employee for division {$division_id}");
            }

        } catch (\Exception $e) {
            error_log("[AutoEntityCreator] Error in handleDivisionCreated: " . $e->getMessage());
            error_log("[AutoEntityCreator] Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Create user for division admin
     *
     * @param array $division_data Division data containing admin fields
     * @return int User ID
     * @throws \Exception If user creation fails
     */
    private function createDivisionUser(array $division_data): int {
        error_log("[AutoEntityCreator] Creating division user from data: " . print_r($division_data, true));

        // Validate required fields
        if (empty($division_data['admin_username']) || empty($division_data['admin_email'])) {
            throw new \Exception('Admin username and email are required');
        }

        // Prepare user data
        $user_data = [
            'user_login' => sanitize_user($division_data['admin_username']),
            'user_email' => sanitize_email($division_data['admin_email']),
            'first_name' => sanitize_text_field($division_data['admin_firstname'] ?? ''),
            'last_name' => sanitize_text_field($division_data['admin_lastname'] ?? ''),
            'display_name' => sanitize_text_field($division_data['admin_firstname'] ?? $division_data['admin_username']),
            'user_pass' => wp_generate_password(),
            'role' => 'agency'  // Base role for all plugin users
        ];

        error_log("[AutoEntityCreator] Inserting user with wp_insert_user()");

        // Create user via wp_insert_user()
        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            $error_msg = $user_id->get_error_message();
            error_log("[AutoEntityCreator] wp_insert_user() failed: {$error_msg}");
            throw new \Exception("Failed to create user: {$error_msg}");
        }

        error_log("[AutoEntityCreator] User created successfully: ID={$user_id}, Username={$user_data['user_login']}");

        // Add agency_admin_unit role (dual-role pattern)
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user->add_role('agency_admin_unit');
            error_log("[AutoEntityCreator] Added role agency_admin_unit to user {$user_id}");
        }

        // Send email notification
        wp_new_user_notification($user_id, null, 'user');
        error_log("[AutoEntityCreator] Sent notification email to user {$user_id}");

        return $user_id;
    }

    /**
     * Handle division deleted event
     * Cascade delete/deactivate employees based on mode:
     * - Demo mode (WP_AGENCY_DEVELOPMENT): HARD DELETE (remove from DB)
     * - Production: SOFT DELETE (set status = 'inactive')
     *
     * @param int $division_id The deleted division ID
     * @param array $division_data The division data before deletion
     * @param bool $is_hard_delete Whether this was a hard delete or soft delete
     */
    public function handleDivisionDeleted(int $division_id, array $division_data, bool $is_hard_delete): void {
        error_log("[AutoEntityCreator] handleDivisionDeleted triggered for division ID: {$division_id}, hard_delete: " . ($is_hard_delete ? 'YES' : 'NO'));

        try {
            global $wpdb;

            // Check if in demo mode
            $is_demo_mode = defined('WP_AGENCY_DEVELOPMENT') && WP_AGENCY_DEVELOPMENT === true;

            // Get all employees in this division
            $employees = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_agency_employees WHERE division_id = %d",
                $division_id
            ));

            if (empty($employees)) {
                error_log("[AutoEntityCreator] No employees found for division {$division_id}");
                return;
            }

            error_log("[AutoEntityCreator] Found " . count($employees) . " employees for division {$division_id}, mode: " . ($is_demo_mode ? 'DEMO' : 'PRODUCTION'));

            if ($is_demo_mode) {
                // DEMO MODE: HARD DELETE via Model
                foreach ($employees as $employee) {
                    $result = $this->employeeModel->delete($employee->id);
                    if ($result) {
                        error_log("[AutoEntityCreator] HARD deleted employee ID: {$employee->id}");
                    } else {
                        error_log("[AutoEntityCreator] Failed to delete employee ID: {$employee->id}");
                    }
                }
            } else {
                // PRODUCTION: SOFT DELETE (set status = 'inactive')
                foreach ($employees as $employee) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'app_agency_employees',
                        [
                            'status' => 'inactive',
                            'updated_at' => current_time('mysql')
                        ],
                        ['id' => $employee->id],
                        ['%s', '%s'],
                        ['%d']
                    );

                    if ($result !== false) {
                        error_log("[AutoEntityCreator] SOFT deleted (set inactive) employee ID: {$employee->id}");
                    } else {
                        error_log("[AutoEntityCreator] Failed to deactivate employee ID: {$employee->id}");
                    }
                }
            }

            error_log("[AutoEntityCreator] Cascade delete completed for division {$division_id}");

        } catch (\Exception $e) {
            error_log("[AutoEntityCreator] Error in handleDivisionDeleted: " . $e->getMessage());
            error_log("[AutoEntityCreator] Stack trace: " . $e->getTraceAsString());
        }
    }
}
