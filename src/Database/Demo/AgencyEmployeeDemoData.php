<?php
/**
 * Agency Employee Demo Data Generator
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.0
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
        $this->debug('Starting employee data generation');

        try {
            // Clear existing data if in development mode
            if ($this->shouldClearData()) {
                $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_agency_employees");
                $this->debug('Cleared existing employee data');
            }

            // Clear employee data for demo generation
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_agency_employees");
            $this->debug('Cleared existing employee data for demo');

            // Tahap 1: Generate dari user yang sudah ada (agency owners & division admins)
            $this->generateExistingUserEmployees();

            // Tahap 2: Generate dari AgencyEmployeeUsersData
            $this->generateNewEmployees();

            $this->debug('Employee generation completed');

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

    private function generateNewEmployees(): void {
        foreach (self::$employee_users as $user_data) {
            // Generate WordPress user first
            // Note: roles is now an array ['agency', 'agency_xxx']
            // But WPUserGenerator needs the primary role (first one after 'agency')
            $roles = $user_data['roles'] ?? ['agency'];
            $primary_role = count($roles) > 1 ? $roles[1] : $roles[0];

            $user_id = $this->wpUserGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'role' => $primary_role
            ]);

            if (!$user_id) {
                $this->debug("Failed to create WP user: {$user_data['username']}");
                continue;
            }

            // Create employee record
            // Department assignments removed from new structure
            $this->createEmployeeRecord(
                $user_data['agency_id'],
                $user_data['division_id'],
                $user_id
            );
        }
    }

private function createEmployeeRecord(
    int $agency_id,
    int $division_id,
    int $user_id,
    array $departments = null  // Made optional for backward compatibility
): void {
    try {
        $wp_user = get_userdata($user_id);
        if (!$wp_user) {
            throw new \Exception("WordPress user not found: {$user_id}");
        }

        // Debug logging for user data
        $this->debug("Processing user_id: {$user_id}, username: {$wp_user->user_login}, display_name: {$wp_user->display_name}, email: {$wp_user->user_email}");

        // Ensure email is correct
        $expected_email = $wp_user->user_login . '@example.com';
        if ($wp_user->user_email !== $expected_email) {
            wp_update_user([
                'ID' => $user_id,
                'user_email' => $expected_email
            ]);
            $this->debug("Updated email for user {$user_id} from {$wp_user->user_email} to {$expected_email}");
            // Refresh user data
            $wp_user = get_userdata($user_id);
        }

        // Build keterangan based on user type
        $keterangan = [];
        if ($user_id >= AgencyUsersData::USER_ID_START && $user_id <= AgencyUsersData::USER_ID_END) {
            $keterangan[] = 'Admin Pusat';
        }
        if ($user_id >= DivisionUsersData::USER_ID_START && $user_id <= DivisionUsersData::USER_ID_END) {
            $keterangan[] = 'Admin Division';
        }

        // Add department info only if departments array is provided (for backward compatibility)
        if ($departments !== null) {
            if ($departments['finance'] ?? false) $keterangan[] = 'Finance';
            if ($departments['operation'] ?? false) $keterangan[] = 'Operation';
            if ($departments['legal'] ?? false) $keterangan[] = 'Legal';
            if ($departments['purchase'] ?? false) $keterangan[] = 'Purchase';
        }

        // Check if email already exists in employees table
        $existing_employee = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}app_agency_employees WHERE email = %s",
            $wp_user->user_email
        ));

        if ($existing_employee) {
            $this->debug("Email {$wp_user->user_email} already exists in employees table, skipping user {$user_id}");
            return;
        }

        $employee_data = [
            'agency_id' => $agency_id,
            'division_id' => $division_id,
            'user_id' => $user_id,
            'name' => $wp_user->display_name,
            'position' => 'Staff',
            'email' => $wp_user->user_email,
            'phone' => $this->generatePhone(),
            'finance' => $departments['finance'] ?? false,
            'operation' => $departments['operation'] ?? false,
            'legal' => $departments['legal'] ?? false,
            'purchase' => $departments['purchase'] ?? false,
            'keterangan' => implode(', ', $keterangan),
            'created_by' => 1,
            'status' => 'active'
        ];

            // Ubah dari insert langsung ke model menjadi menggunakan controller
            $employee_id = $this->employeeController->createDemoEmployee($employee_data);

            if ($employee_id === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            $this->debug("Created employee record for: {$wp_user->display_name}");

        } catch (\Exception $e) {
            $this->debug("Error creating employee record: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePhone(): string {
        return sprintf('08%d', rand(100000000, 999999999));
    }
}

