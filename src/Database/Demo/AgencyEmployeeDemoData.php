<?php
/**
 * Agency Employee Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.7
 * @author      arisciwek
 * 
 * Path: /wp-agency/src/Database/Demo/AgencyEmployeeDemoData.php
 */

namespace WPAgency\Database\Demo;

use WPAgency\Database\Demo\Data\AgencyEmployeeUsersData;
use WPAgency\Database\Demo\Data\AgencyUsersData;
use WPAgency\Database\Demo\Data\DivisionUsersData;
use WPAgency\Controllers\Employee\AgencyEmployeeController;

defined('ABSPATH') || exit;

class AgencyEmployeeDemoData extends AbstractDemoData {
    use AgencyDemoDataHelperTrait;

    private $employeeController;
    private $wpUserGenerator;
    private static $employee_users;

    public function __construct() {
        parent::__construct();
        $this->wpUserGenerator = new WPUserGenerator();
        self::$employee_users = AgencyEmployeeUsersData::$data;
        $this->employeeController = new AgencyEmployeeController();
    }

    protected function validate(): bool {
        try {
            // 1. Validasi table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_agency_employees'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Employee table does not exist');
            }

            // 2. Validasi agency & division data exists
            $agency_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_agencies"
            );
            
            if ($agency_count == 0) {
                throw new \Exception('No agencies found - please generate agency data first');
            }

            $division_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_agency_divisions"
            );
            
            if ($division_count == 0) {
                throw new \Exception('No divisions found - please generate division data first');
            }

            // 3. Validasi static data untuk employee users
            if (empty(self::$employee_users)) {
                throw new \Exception('Employee users data not found');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        // Increase max execution time for employee generation with WP user creation
        ini_set('max_execution_time', '300'); // 5 minutes

        $this->debug('[AgencyEmployeeDemoData] Starting employee data generation');

        try {
            // Task-2070: Selective cleanup - ONLY delete demo employees (user_id 170-229)
            // PRESERVE 30 admin employees created by division hook (user_id 130-169)
            if ($this->shouldClearData()) {
                $this->debug("[AgencyEmployeeDemoData] === Cleanup mode enabled ===");

                // 1. Enable hard delete temporarily
                $original_settings = get_option('wp_agency_general_options', []);
                $cleanup_settings = array_merge($original_settings, ['enable_hard_delete_branch' => true]);
                update_option('wp_agency_general_options', $cleanup_settings);
                $this->debug("[AgencyEmployeeDemoData] Enabled hard delete mode for cleanup");

                // 2. Get demo employees ONLY (user_id in range 170-229)
                // DO NOT touch employees created by hook (user_id 130-169)
                $demo_employees = $this->wpdb->get_col(
                    "SELECT id FROM {$this->wpdb->prefix}app_agency_employees
                     WHERE user_id >= " . AgencyEmployeeUsersData::USER_ID_START . "
                       AND user_id <= " . AgencyEmployeeUsersData::USER_ID_END
                );
                $this->debug("[AgencyEmployeeDemoData] Found " . count($demo_employees) . " demo employees to clean (range 170-229)");

                // 3. Delete via Model (triggers wp_agency_employee_created hook if any handler exists)
                $deleted_count = 0;
                $employeeModel = new \WPAgency\Models\Employee\AgencyEmployeeModel();
                foreach ($demo_employees as $employee_id) {
                    if ($employeeModel->delete($employee_id)) {
                        $deleted_count++;
                    }
                }
                $this->debug("[AgencyEmployeeDemoData] Deleted {$deleted_count} demo employees via Model+HOOK");

                // 4. Restore original settings
                update_option('wp_agency_general_options', $original_settings);
                $this->debug("[AgencyEmployeeDemoData] Restored original delete mode");

                // 5. Clean up WordPress users (range 170-229)
                $employee_user_ids = range(
                    AgencyEmployeeUsersData::USER_ID_START,
                    AgencyEmployeeUsersData::USER_ID_END
                );
                $deleted_users = $this->wpUserGenerator->deleteUsers($employee_user_ids);
                $this->debug("[AgencyEmployeeDemoData] Cleaned up {$deleted_users} demo users (range 170-229)");

                $this->debug("Cleaned up {$deleted_count} employees and {$deleted_users} users before generation");
            }

            // Tahap 1: SKIP generateExistingUserEmployees()
            // Reason: Agency/Division admins are already auto-created by hooks (30 employees)
            // We only generate NEW staff employees from AgencyEmployeeUsersData
            $this->debug("[AgencyEmployeeDemoData] Skipping existing user employees - already created by hooks (30 admin employees)");

            // Tahap 2: Generate dari AgencyEmployeeUsersData (60 staff employees, ID 170-229)
            $this->generateNewEmployees();

            $this->debug('[AgencyEmployeeDemoData] Employee generation completed');

        } catch (\Exception $e) {
            $this->debug('Error generating employees: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateExistingUserEmployees(): void {

		// 1. For agency owners (ID 102-111)
		for ($id = AgencyUsersData::USER_ID_START; $id <= AgencyUsersData::USER_ID_END; $id++) {
		    $agency = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_agencies WHERE user_id = %d",
		        $id
		    ));

		    if (!$agency) continue;

		    // Ambil division pusat untuk assign owner
		    $pusat_division = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions 
		         WHERE agency_id = %d AND type = 'pusat'",
		        $agency->id
		    ));

		    if (!$pusat_division) continue;

		    // Create employee record for owner di division pusat
		    $this->createEmployeeRecord(
		        $agency->id,
		        $pusat_division->id,
		        $agency->user_id,
		        [
		            'finance' => true,
		            'operation' => true,
		            'legal' => true,
		            'purchase' => true
		        ]
		    );
		} 
		
        // 2. Division admins (ID 112-131)
        for ($id = DivisionUsersData::USER_ID_START; $id <= DivisionUsersData::USER_ID_END; $id++) {
            $division = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}app_agency_divisions WHERE user_id = %d",
                $id
            ));

            if (!$division) continue;

            // Division admin gets all department access for their division
            $this->createEmployeeRecord(
                $division->agency_id,
                $division->id,
                $division->user_id,
                [
                    'finance' => true,
                    'operation' => true,
                    'legal' => true,
                    'purchase' => true
                ]
            );
        }
    }

    /**
     * Build division mapping: static index (1-30) → actual division ID
     * Task-2070: Dynamic mapping to handle varying division IDs across regenerations
     *
     * @return array Mapping array [division_index => actual_division_id]
     */
    private function buildDivisionMapping(): array {
        // Get all active divisions ordered by agency, then type (pusat first, cabang second)
        $divisions = $this->wpdb->get_results(
            "SELECT id, agency_id, type
             FROM {$this->wpdb->prefix}app_agency_divisions
             WHERE status = 'active'
             ORDER BY agency_id ASC, type DESC, id ASC"  // type DESC: 'pusat' before 'cabang'
        );

        if (empty($divisions)) {
            throw new \Exception('No active divisions found for employee mapping');
        }

        // Build mapping: index 1-30 → actual division ID
        $mapping = [];
        $division_index = 1;

        foreach ($divisions as $division) {
            $mapping[$division_index] = $division->id;
            $this->debug("Division mapping: index {$division_index} → ID {$division->id} (agency {$division->agency_id}, type {$division->type})");
            $division_index++;
        }

        $this->debug("Built division mapping for " . count($mapping) . " divisions");
        return $mapping;
    }

    /**
     * Create employee via runtime flow (Task-2070)
     * Simulates production form submission with full validation
     *
     * @param int $agency_id     Agency ID
     * @param int $division_id   Division ID (actual DB ID, not index)
     * @param int $user_id       WordPress user ID (already created)
     * @return int               Created employee ID
     * @throws \Exception        If validation or creation fails
     */
    private function createEmployeeViaRuntimeFlow(
        int $agency_id,
        int $division_id,
        int $user_id
    ): int {
        // Step 1: Get user data
        $wp_user = get_userdata($user_id);
        if (!$wp_user) {
            throw new \Exception("WordPress user not found: {$user_id}");
        }

        // Step 2: Build employee data (same format as production form)
        $employee_data = [
            'agency_id' => $agency_id,
            'division_id' => $division_id,
            'user_id' => $user_id,
            'name' => $wp_user->display_name,
            'email' => $wp_user->user_email,  // Same as WP user email
            'phone' => $this->generatePhone(),
            'position' => 'Staff',
            'keterangan' => 'Demo employee - runtime flow (Task-2070)',
            'status' => 'active',
            'created_by' => 1  // Admin
        ];

        // Step 3: Validate via Validator (production validation - FULL VALIDATION!)
        $validator = new \WPAgency\Validators\Employee\AgencyEmployeeValidator();
        $errors = $validator->validateCreate($employee_data);

        if (!empty($errors)) {
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }

        // Step 4: Create via Model (triggers wp_agency_employee_created hook)
        $model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
        $employee_id = $model->create($employee_data);

        if (!$employee_id) {
            throw new \Exception('Failed to create employee via Model');
        }

        $this->debug("Created employee ID {$employee_id} for user {$wp_user->user_login} via runtime flow");
        return $employee_id;
    }

    /**
     * Generate new employees via runtime flow (Task-2070)
     * Uses production validation and triggers hooks
     */
    private function generateNewEmployees(): void {
        // Step 1: Build division mapping (index 1-30 → actual division IDs)
        $division_mapping = $this->buildDivisionMapping();

        // Step 2: Generate each employee via runtime flow
        foreach (self::$employee_users as $user_data) {
            // Map division_id from data (1-30) to actual DB ID
            $division_index = $user_data['division_id'];
            if (!isset($division_mapping[$division_index])) {
                $this->debug("Division index {$division_index} not found in mapping, skipping user {$user_data['id']}");
                continue;
            }
            $actual_division_id = $division_mapping[$division_index];

            // Get agency_id from division (more reliable than hardcoded agency_id in data)
            $division = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT agency_id FROM {$this->wpdb->prefix}app_agency_divisions WHERE id = %d",
                $actual_division_id
            ));

            if (!$division) {
                $this->debug("Division ID {$actual_division_id} not found, skipping user {$user_data['id']}");
                continue;
            }

            // Generate WordPress user with roles
            $user_id = $this->wpUserGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'roles' => $user_data['roles']
            ]);

            if (!$user_id) {
                $this->debug("Failed to create WP user: {$user_data['username']}");
                continue;
            }

            // Create employee via runtime flow (validation + model + hook)
            try {
                $employee_id = $this->createEmployeeViaRuntimeFlow(
                    $division->agency_id,
                    $actual_division_id,
                    $user_id
                );
                $this->debug("✓ Generated employee {$employee_id} for {$user_data['display_name']} in division {$actual_division_id}");
            } catch (\Exception $e) {
                $this->debug("✗ Failed to create employee for {$user_data['display_name']}: " . $e->getMessage());
                // Continue with next employee instead of throwing
            }
        }
    }

    /**
     * DEPRECATED: Use createEmployeeViaRuntimeFlow() instead (Task-2070)
     * Kept only for generateExistingUserEmployees() backward compatibility
     */
    private function createEmployeeRecord(
        int $agency_id,
        int $division_id,
        int $user_id,
        array $departments = null  // Made optional for backward compatibility
    ): void {
        // Task-2070: This method now delegates to runtime flow
        // for consistent validation and hook triggering
        try {
            $this->createEmployeeViaRuntimeFlow($agency_id, $division_id, $user_id);
        } catch (\Exception $e) {
            $this->debug("Error creating employee record: " . $e->getMessage());
            // Don't throw - let generation continue
        }
    }

    private function generatePhone(): string {
        return sprintf('08%d', rand(100000000, 999999999));
    }
}

