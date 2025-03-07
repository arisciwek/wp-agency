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
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_divisions"
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

		// For agency owners (ID 2-11)
		for ($id = 2; $id <= 11; $id++) {
		    $agency = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_agencies WHERE user_id = %d",
		        $id
		    ));

		    if (!$agency) continue;

		    // Ambil division pusat untuk assign owner
		    $pusat_division = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_divisions 
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
		
        // 2. Division admins (ID 12-41)
        for ($id = 12; $id <= 41; $id++) {
            $division = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}app_divisions WHERE user_id = %d",
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
            $user_id = $this->wpUserGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'role' => $user_data['role']
            ]);

            if (!$user_id) {
                $this->debug("Failed to create WP user: {$user_data['username']}");
                continue;
            }

            // Create employee record with department assignments
            $this->createEmployeeRecord(
                $user_data['agency_id'],
                $user_data['division_id'],
                $user_id,
                $user_data['departments']
            );
        }
    }

private function createEmployeeRecord(
    int $agency_id, 
    int $division_id, 
    int $user_id, 
    array $departments
): void {
    try {
        $wp_user = get_userdata($user_id);
        if (!$wp_user) {
            throw new \Exception("WordPress user not found: {$user_id}");
        }

        $keterangan = [];
        if ($user_id >= 2 && $user_id <= 11) $keterangan[] = 'Admin Pusat';
        if ($user_id >= 12 && $user_id <= 41) $keterangan[] = 'Admin Division';
        if ($departments['finance']) $keterangan[] = 'Finance'; 
        if ($departments['operation']) $keterangan[] = 'Operation';
        if ($departments['legal']) $keterangan[] = 'Legal';
        if ($departments['purchase']) $keterangan[] = 'Purchase';

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

