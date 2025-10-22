<?php
/**
 * Auto Entity Creator Handler
 *
 * @package     WP_Agency
 * @subpackage  Handlers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Handlers/AutoEntityCreator.php
 *
 * Description: Handles automatic entity creation via hooks
 *              - Creates division pusat when agency is created
 *              - Creates employee when division is created
 *              Following wp-customer pattern for consistency
 *
 * Changelog:
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
                'provinsi_code' => $agency_data['provinsi_code'] ?? null,
                'regency_code' => $agency_data['regency_code'] ?? null,
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
     * Automatically creates employee for the division owner
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

            // Get user_id from division data
            $user_id = $division_data['user_id'] ?? null;
            if (!$user_id) {
                error_log("[AutoEntityCreator] No user_id in division data, skipping employee creation");
                return;
            }

            // Get agency info
            $agency = $this->agencyModel->find($division->agency_id);
            if (!$agency) {
                error_log("[AutoEntityCreator] Agency not found for division {$division_id}");
                return;
            }

            // Check if employee already exists for this user and division
            $existing_employee = $this->employeeModel->findByUserAndDivision($user_id, $division_id);
            if ($existing_employee) {
                error_log("[AutoEntityCreator] Employee already exists for user {$user_id} in division {$division_id}");
                return;
            }

            // Get user info
            $user = get_userdata($user_id);
            if (!$user) {
                error_log("[AutoEntityCreator] User not found: {$user_id}");
                return;
            }

            // Prepare employee data
            // Note: finance, operation, legal, purchase fields not used in wp-agency
            // Database will use default values (0) for these fields
            $employee_data = [
                'agency_id' => $division->agency_id,
                'division_id' => $division_id,
                'user_id' => $user_id,
                'name' => $user->display_name,
                'position' => 'Admin',
                'finance' => 0,
                'operation' => 0,
                'legal' => 0,
                'purchase' => 0,
                'keterangan' => 'Auto-created by system',
                'email' => $user->user_email,
                'phone' => '-',
                'status' => 'active',
                'created_by' => $division_data['created_by'] ?? get_current_user_id()
            ];

            error_log("[AutoEntityCreator] Creating employee with data: " . print_r($employee_data, true));

            // Create employee
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
}
