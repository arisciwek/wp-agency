<?php
/**
 * Agency Employee Controller Class
 *
 * @package     WP_Agency
 * @subpackage  Controllers/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Controllers/Employee/AgencyEmployeeController.php
 *
 * Description: Controller untuk mengelola data karyawan agency.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk panel kanan.
 *              Menyediakan endpoints untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added CRUD operations
 * - Added DataTable integration
 * - Added permission handling
 */

namespace WPAgency\Controllers\Employee;

use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Models\Employee\AgencyEmployeeModel;
use WPAgency\Validators\Employee\AgencyEmployeeValidator;
use WPAgency\Cache\AgencyCacheManager;

class AgencyEmployeeController {
    private AgencyModel $agencyModel;
    private AgencyEmployeeModel $model;
    private AgencyEmployeeValidator $validator;
    private AgencyCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/employee.log';

    public function __construct() {
        $this->model = new AgencyEmployeeModel();
        $this->agencyModel = new AgencyModel();
        $this->validator = new AgencyEmployeeValidator();
        $this->cache = new AgencyCacheManager();

        // Initialize log file in plugin directory
        $this->log_file = WP_AGENCY_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX endpoints
        add_action('wp_ajax_handle_employee_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_employee', [$this, 'show']);
        add_action('wp_ajax_create_employee', [$this, 'store']);
        add_action('wp_ajax_update_employee', [$this, 'update']);
        add_action('wp_ajax_delete_employee', [$this, 'delete']);
        add_action('wp_ajax_change_employee_status', [$this, 'changeStatus']);
        add_action('wp_ajax_create_employee_button', [$this, 'createEmployeeButton']);
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $agency_base_dir = $upload_dir['basedir'] . '/wp-agency';
        $agency_log_dir = $agency_base_dir . '/logs';
        
        // Update log file path with monthly rotation
        $this->log_file = $agency_log_dir . '/employee-' . date('Y-m') . '.log';

        // Create directories if needed
        if (!file_exists($agency_base_dir)) {
            wp_mkdir_p($agency_base_dir);
        }

        if (!file_exists($agency_log_dir)) {
            wp_mkdir_p($agency_log_dir);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
            chmod($this->log_file, 0644);
        }
    }

    /**
     * Log debug messages
     */
    private function debug_log($message): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";
        error_log($log_message, 3, $this->log_file);
    }

    /**
     * Handle DataTable AJAX request dengan cache
     */
    public function handleDataTableRequest() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');

            // Get and validate parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            $agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
            
            if (!$agency_id) {
                throw new \Exception('Agency ID is required');
            }

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $access = $this->validator->validateAccess(0);

            // Check cache first
            $cached_result = $this->cache->getDataTableCache(
                'agency_employee_list',
                $access['access_type'],
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                ['agency_id' => $agency_id]
            );

            if ($cached_result !== null) {
                wp_send_json($cached_result);
                return;
            }

            // Get fresh data if no cache
            $result = $this->model->getDataTableData(
                $agency_id,
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir
            );

            if (!$result) {
                throw new \Exception('No data returned from model');
            }

            // Format data with validation
            $data = [];
           // Get selected roles from form
           $selected_roles = isset($_POST['roles']) ? (array)$_POST['roles'] : [];
           $selected_roles = array_map('sanitize_text_field', $selected_roles);
            foreach ($result['data'] as $employee) {
                // Skip permission check if user has admin access
                if ($access['access_type'] !== 'admin') {
                    $relation = $this->validator->getUserRelation($employee->id);
                    if (!$this->validator->canViewEmployee($relation)) {
                        continue; // Skip this employee instead of throwing error
                    }
                }

                $data[] = [
                    'id' => $employee->id,
                    'name' => esc_html($employee->name),
                    'position' => esc_html($employee->position),
                    'role' => $this->getUserRole($employee->user_id),
                    'division_name' => esc_html($employee->division_name),
                    'status' => $employee->status,
                    'actions' => $this->generateActionButtons($employee)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data
            ];

            // Cache the result
            $this->cache->setDataTableCache(
                'agency_employee_list',
                $access['access_type'],
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                $response,
                ['agency_id' => $agency_id]
            );

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user role for display
     */
    private function getUserRole(int $user_id): string {
        $user = get_userdata($user_id);
        if (!$user) {
            return '-';
        }

        $roles = $user->roles;
        if (empty($roles)) {
            return '-';
        }

        // Get role names from single source of truth
        $role_names = \WP_Agency_Activator::getRoles();

        // Get all role display names
        $display_roles = [];
        foreach ($roles as $role) {
            $display_roles[] = $role_names[$role] ?? ucfirst($role);
        }

        // Return comma-separated list of roles
        return implode(', ', $display_roles);
    }

    /**
     * Generate HTML for status badge
     */
    private function generateStatusBadge(string $status): string {
        $label = $status === 'active' ? __('Aktif', 'wp-agency') : __('Non-aktif', 'wp-agency');
        return sprintf(
            '<span class="status-badge status-%s">%s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    public function createEmployeeButton() {
        try {
            check_ajax_referer('wp_agency_nonce', 'nonce');
            
            $agency_id = isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0;
            $division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0;
            
            if (!$agency_id) {
                throw new \Exception('ID Agency tidak valid');
            }

            $validator = new AgencyEmployeeValidator();
            $canCreate = $validator->canCreateEmployee($agency_id, $division_id);

            if ($canCreate) {
                $button = '<button type="button" class="button button-primary" id="add-employee-btn">';
                $button .= '<span class="dashicons dashicons-plus-alt"></span>';
                $button .= __('Tambah Karyawan', 'wp-agency');
                $button .= '</button>';
            } else {
                $button = '';
            }

            wp_send_json_success([
                'button' => $button
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate action buttons HTML
     */
    private function generateActionButtons($employee) {
        $actions = [];

        // Dapatkan relasi dan periksa permission
        $relation = $this->validator->getUserRelation($employee->id);

        // View Button
        if ($this->validator->canViewEmployee($relation)) {
            $actions[] = sprintf(
                '<button type="button" class="button view-employee" data-id="%d" title="%s"><i class="dashicons dashicons-visibility"></i></button>',
                $employee->id,
                __('Lihat', 'wp-agency')
            );
        }

        // Edit Button
        if ($this->validator->canEditEmployee($relation)) {
            $actions[] = sprintf(
                '<button type="button" class="button edit-employee" data-id="%d" title="%s"><i class="dashicons dashicons-edit"></i></button>',
                $employee->id,
                __('Edit', 'wp-agency')
            );
        }

        // Delete Button
        if ($this->validator->canDeleteEmployee($relation)) {
            $actions[] = sprintf(
                '<button type="button" class="button delete-employee" data-id="%d" title="%s"><i class="dashicons dashicons-trash"></i></button>',
                $employee->id,
                __('Hapus', 'wp-agency')
            );
        }

        // Status Toggle Button
        if ($this->validator->canEditEmployee($relation)) {
            $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
            $statusTitle = $employee->status === 'active' ?
                __('Nonaktifkan', 'wp-agency') :
                __('Aktifkan', 'wp-agency');
            $statusIcon = $employee->status === 'active' ? 'remove' : 'yes';

            $actions[] = sprintf(
                '<button type="button" class="button toggle-status" data-id="%d" data-status="%s" title="%s"><i class="dashicons dashicons-%s"></i></button>',
                $employee->id,
                $newStatus,
                $statusTitle,
                $statusIcon
            );
        }

        return implode('', $actions);
    }

    /**
     * Show employee dengan cache yang konsisten
     */
	// In the show() method, update to include user roles:
	public function show() {
	    try {
		check_ajax_referer('wp_agency_nonce', 'nonce');

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if (!$id) throw new \Exception('Invalid employee ID');

		// Get employee data from cache or database
		$cached_employee = $this->cache->get('agency_employee', $id);
		
		if ($cached_employee !== null) {
		    $employee = $cached_employee;
		} else {
		    $employee = $this->model->find($id);
		    
		    if ($employee) {
		        $this->cache->set('agency_employee', $employee, $this->cache::getCacheExpiry(), $id);
		    }
		}

		if (!$employee) throw new \Exception('Employee not found');

		// Check permissions
		$relation = $this->validator->getUserRelation($id);
		if (!$this->validator->canViewEmployee($relation)) {
		    throw new \Exception('Anda tidak memiliki izin untuk melihat detail karyawan ini.');
		}

		// Get user roles for the employee
		$user_roles = [];
		if ($employee->user_id) {
		    $user = get_userdata($employee->user_id);
		    if ($user) {
		        $user_roles = $user->roles;
		    }
		}
		
		// Add roles to employee data
		$employee->user_roles = $user_roles;

		wp_send_json_success($employee);

	    } catch (\Exception $e) {
		wp_send_json_error(['message' => $e->getMessage()]);
	    }
	}

    /**
     * Store dengan cache invalidation
     */
	public function store() {
	    try {
		check_ajax_referer('wp_agency_nonce', 'nonce');

		// Get selected roles from form
		$selected_roles = isset($_POST['roles']) ? (array)$_POST['roles'] : [];
		$selected_roles = array_map('sanitize_text_field', $selected_roles);

		if (empty($selected_roles)) {
		    throw new \Exception('Minimal satu role harus dipilih');
		}

		$data = [
		    'agency_id' => isset($_POST['agency_id']) ? (int)$_POST['agency_id'] : 0,
		    'division_id' => isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0,
		    'name' => sanitize_text_field($_POST['name'] ?? ''),
		    'position' => sanitize_text_field($_POST['position'] ?? ''),
		    'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
		    'operation' => isset($_POST['operation']) && $_POST['operation'] === "1", 
		    'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
		    'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
		    'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
		    'email' => sanitize_email($_POST['email'] ?? ''),
		    'phone' => sanitize_text_field($_POST['phone'] ?? ''),
		    'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive']) 
		        ? $_POST['status'] 
		        : 'active',
		    'roles' => $selected_roles // Add roles to data array for validation
		];

		if (!$this->validator->canCreateEmployee($data['agency_id'], $data['division_id'])) {
		    throw new \Exception('Anda tidak memiliki izin untuk menambah karyawan.');
		}

		$errors = $this->validator->validateCreate($data);
		if (!empty($errors)) throw new \Exception(implode(', ', $errors));

		// Create WordPress user with first role as primary
		$primary_role = reset($selected_roles);
		$user_data = [
		    'user_login' => strstr($data['email'], '@', true) ?: sanitize_user(strtolower(str_replace(' ', '', $data['name']))),
		    'user_email' => $data['email'],
		    'first_name' => explode(' ', $data['name'], 2)[0],
		    'last_name' => explode(' ', $data['name'], 2)[1] ?? '',
		    'user_pass' => wp_generate_password(),
		    'role' => $primary_role
		];

		$user_id = wp_insert_user($user_data);
		if (is_wp_error($user_id)) throw new \Exception($user_id->get_error_message());

		// Add additional roles if more than one selected
		if (count($selected_roles) > 1) {
		    $user = new \WP_User($user_id);
		    foreach ($selected_roles as $role) {
		        if ($role !== $primary_role) {
		            $user->add_role($role);
		        }
		    }
		}

		// Remove roles from data array before database insert
		unset($data['roles']);
		
		$data['user_id'] = $user_id;
		$id = $this->model->create($data);
		if (!$id) {
		    wp_delete_user($user_id);
		    throw new \Exception('Failed to create employee');
		}

		wp_new_user_notification($user_id, null, 'user');

		$this->cache->invalidateDataTableCache('agency_employee_list', [
		    'agency_id' => $data['agency_id']
		]);

		$employee = $this->model->find($id);
		if ($employee) {
		    $this->cache->set('agency_employee', $employee, $this->cache::getCacheExpiry(), $id);
		}

		wp_send_json_success([
		    'message' => __('Karyawan berhasil ditambahkan dan email aktivasi telah dikirim', 'wp-agency'),
		    'employee' => $employee
		]);

	    } catch (\Exception $e) {
		wp_send_json_error(['message' => $e->getMessage()]);
	    }
	}

    /**
     * Update dengan cache invalidation
     */
	public function update() {
	    try {
		check_ajax_referer('wp_agency_nonce', 'nonce');

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if (!$id) throw new \Exception('Invalid employee ID');

		// Get employee data and verify existence
		$employee = $this->model->find($id);
		if (!$employee) throw new \Exception('Employee not found');

		// Check permissions
		$relation = $this->validator->getUserRelation($id);
		if (!$this->validator->canEditEmployee($relation)) {
		    throw new \Exception('Anda tidak memiliki izin untuk mengedit karyawan ini.');
		}

		// Get selected roles from form
		$selected_roles = isset($_POST['roles']) ? (array)$_POST['roles'] : [];
		$selected_roles = array_map('sanitize_text_field', $selected_roles);

		if (empty($selected_roles)) {
		    throw new \Exception('Minimal satu role harus dipilih');
		}

		$data = [
		    'name' => sanitize_text_field($_POST['name'] ?? ''),
		    'position' => sanitize_text_field($_POST['position'] ?? ''),
		    'email' => sanitize_email($_POST['email'] ?? ''),
		    'phone' => sanitize_text_field($_POST['phone'] ?? ''),
		    'division_id' => isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0,
		    'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
		    'operation' => isset($_POST['operation']) && $_POST['operation'] === "1",
		    'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
		    'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
		    'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
		    'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive']) 
		        ? $_POST['status'] 
		        : 'active',
		    'roles' => $selected_roles // Add roles to data array for validation
		];

		$errors = $this->validator->validateUpdate($data, $id);
		if (!empty($errors)) throw new \Exception(implode(', ', $errors));

		// Update WordPress user roles
		if ($employee->user_id) {
		    $user = new \WP_User($employee->user_id);
		    if ($user->exists()) {
		        // Remove all existing roles
		        $existing_roles = $user->roles;
		        foreach ($existing_roles as $role) {
		            $user->remove_role($role);
		        }
		        
		        // Add new roles
		        foreach ($selected_roles as $role) {
		            $user->add_role($role);
		        }

		        // Update user email if changed
		        if ($user->user_email !== $data['email']) {
		            wp_update_user([
		                'ID' => $employee->user_id,
		                'user_email' => $data['email']
		            ]);
		        }
		    }
		}

		// Remove roles from data array before database update
		unset($data['roles']);

		if (!$this->model->update($id, $data)) {
		    throw new \Exception('Failed to update employee');
		}

		// Clear cache
		$this->cache->delete('agency_employee', $id);

		$employee = $this->model->find($id);
		if ($employee) {
		    $this->cache->set('agency_employee', $employee, $this->cache::getCacheExpiry(), $id);

		    $this->cache->invalidateDataTableCache('agency_employee_list', [
		        'agency_id' => $employee->agency_id
		    ]);
		}

		wp_send_json_success([
		    'message' => __('Data karyawan berhasil diperbarui', 'wp-agency'),
		    'employee' => $employee
		]);

	    } catch (\Exception $e) {
		wp_send_json_error(['message' => $e->getMessage()]);
	    }
	}

    /**
     * Delete dengan cache invalidation
     */
    public function delete() {
       try {
           check_ajax_referer('wp_agency_nonce', 'nonce');

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            // Dapatkan data employee dan verifikasi keberadaannya
            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            // Dapatkan relasi dan periksa permission
            $relation = $this->validator->getUserRelation($id);
            if (!$this->validator->canDeleteEmployee($relation)) {
                throw new \Exception('Anda tidak memiliki izin untuk menghapus karyawan ini.');
            }

           $errors = $this->validator->validateDelete($id);
           if (!empty($errors)) throw new \Exception(reset($errors));

           if (!$this->model->delete($id)) {
               throw new \Exception('Failed to delete employee');
           }

           // Hapus cache untuk employee yang diupdate
           $this->cache->delete('agency_employee', $id);

           $this->cache->invalidateDataTableCache('agency_employee_list', [
               'agency_id' => $employee->agency_id
           ]);

           wp_send_json_success([
               'message' => __('Karyawan berhasil dihapus', 'wp-agency')
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }

    /**
     * Change status dengan cache invalidation
     */
    public function changeStatus() {
       try {
           check_ajax_referer('wp_agency_nonce', 'nonce');

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            // Dapatkan data employee dan verifikasi keberadaannya
            $employee = $this->model->find($id);
            if ($employee) {
                // Simpan data terbaru ke cache
                $this->cache->set('agency_employee', $employee, $this->cache::getCacheExpiry(), $id);
                
                // Invalidasi cache DataTable
                $this->cache->invalidateDataTableCache('agency_employee_list', [
                    'agency_id' => $employee->agency_id
                ]);
            }

            if (!$employee) throw new \Exception('Employee not found');

            // Dapatkan relasi dan periksa permission
            $relation = $this->validator->getUserRelation($id);
            if (!$this->validator->canEditEmployee($relation)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengubah status karyawan ini.');
            }

           $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
           if (!in_array($status, ['active', 'inactive'])) {
               throw new \Exception('Invalid status');
           }

           if (!$this->model->changeStatus($id, $status)) {
               throw new \Exception('Failed to update employee status');
           }

            $this->cache->delete('agency_employee', $id);

           $this->cache->invalidateDataTableCache('agency_employee_list', [
               'agency_id' => $employee->agency_id
           ]);

           $employee = $this->model->find($id);
           wp_send_json_success([
               'message' => __('Status karyawan berhasil diperbarui', 'wp-agency'),
               'employee' => $employee
           ]);

       } catch (\Exception $e) {
           wp_send_json_error(['message' => $e->getMessage()]);
       }
    }
    

    /**
     * Khusus untuk membuat demo data employee tanpa cache
     */
    public function createDemoEmployee(array $data): ?int {
        try {
            // Debug log
            $this->debug_log('Creating demo employee: ' . print_r($data, true));

            // Buat employee via model
            $employee_id = $this->model->create($data);

            if (!$employee_id) {
                throw new \Exception('Gagal membuat demo employee');
            }

            $this->debug_log("Created employee record with ID: " . $employee_id);

            return $employee_id;
        } catch (\Exception $e) {
            $this->debug_log('Error creating demo employee: ' . $e->getMessage());
            throw $e;
        }
    }
}
