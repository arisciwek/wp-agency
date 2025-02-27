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

            // Check cache first
            $cached_result = $this->cache->getDataTableCache(
                'agency_employee_list',
                get_current_user_id(),
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
            foreach ($result['data'] as $employee) {
                // Get agency for permission check
                $agency = $this->agencyModel->find($employee->agency_id);
                if (!$this->validator->canViewEmployee($employee, $agency)) {
                    continue;
                }

                $data[] = [
                    'id' => $employee->id,
                    'name' => esc_html($employee->name),
                    'position' => esc_html($employee->position),
                    'department' => $this->generateDepartmentsBadges([
                        'finance' => (bool)$employee->finance,
                        'operation' => (bool)$employee->operation,
                        'legal' => (bool)$employee->legal,
                        'purchase' => (bool)$employee->purchase
                    ]),
                    'email' => esc_html($employee->email),
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
                get_current_user_id(),
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
     * Generate HTML for department badges
     */
    private function generateDepartmentsBadges(array $departments): string {
        // Check if any department is true
        $has_departments = array_filter($departments);
        if (empty($has_departments)) {
            return '<div class="department-badges-container empty">-</div>';
        }

        $badges = [];
        foreach ($departments as $dept => $active) {
            if ($active) {
                $label = ucfirst($dept); // Convert finance to Finance, etc.
                $badges[] = sprintf(
                    '<span class="department-badge %s">%s</span>',
                    esc_attr($dept),
                    esc_html($label)
                );
            }
        }

        return sprintf(
            '<div class="department-badges-container">%s</div>',
            implode('', $badges)
        );
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
        $actions = '';
        $current_user_id = get_current_user_id();
        
        // Get agency untuk validasi
        $agency = $this->agencyModel->find($employee->agency_id);
        if (!$agency) return $actions;

        // View Button
        if ($this->validator->canViewEmployee($employee, $agency)) {
            $actions .= sprintf(
                '<button type="button" class="button view-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $employee->id,
                __('Lihat', 'wp-agency')
            );
        }

        // Edit Button
        if ($this->validator->canEditEmployee($employee, $agency)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $employee->id,
                __('Edit', 'wp-agency')
            );
        }

        // Delete Button
        if ($this->validator->canDeleteEmployee($employee, $agency)) {
            $actions .= sprintf(
                '<button type="button" class="button delete-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-trash"></i>
                </button>',
                $employee->id,
                __('Hapus', 'wp-agency')
            );
        }

        // Status Toggle Button
        if ($this->validator->canEditEmployee($employee, $agency)) {
            $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
            $statusTitle = $employee->status === 'active' ? 
                __('Nonaktifkan', 'wp-agency') : 
                __('Aktifkan', 'wp-agency');
            $statusIcon = $employee->status === 'active' ? 'remove' : 'yes';
            
            $actions .= sprintf(
                '<button type="button" class="button toggle-status" data-id="%d" data-status="%s" title="%s">
                    <i class="dashicons dashicons-%s"></i>
                </button>',
                $employee->id,
                $newStatus,
                $statusTitle,
                $statusIcon
            );
        }

        return $actions;

    }
    /**
     * Show employee dengan cache
     */
    public function show() {
       try {
           check_ajax_referer('wp_agency_nonce', 'nonce');

           $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
           if (!$id) throw new \Exception('Invalid employee ID');

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $agency = $this->agencyModel->find($employee->agency_id);
            if (!$agency) throw new \Exception('Agency not found');

            // Tambahkan pengecekan permission
            if (!$this->validator->canViewEmployee($employee, $agency)) {
                throw new \Exception('Anda tidak memiliki izin untuk melihat detail karyawan ini.');
            }



           // Validate view permission
           $errors = $this->validator->validateView($id);
           if (!empty($errors)) {
               throw new \Exception(reset($errors));
           }

           // Check cache
           $employee = $this->cache->get("employee_{$id}");
           if (!$employee) {
               $employee = $this->model->find($id);
               if (!$employee) throw new \Exception('Employee not found');
               $this->cache->set("employee_{$id}", $employee);
           }

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
                   : 'active'
           ];

            if (!$this->validator->canCreateEmployee($data['agency_id'], $data['division_id'])) {
                throw new \Exception('Anda tidak memiliki izin untuk menambah karyawan.');
            }

           $errors = $this->validator->validateCreate($data);
           if (!empty($errors)) throw new \Exception(implode(', ', $errors));

           $user_data = [
               'user_login' => strstr($data['email'], '@', true) ?: sanitize_user(strtolower(str_replace(' ', '', $data['name']))),
               'user_email' => $data['email'],
               'first_name' => explode(' ', $data['name'], 2)[0],
               'last_name' => explode(' ', $data['name'], 2)[1] ?? '',
               'user_pass' => wp_generate_password(),
               'role' => 'agency'
           ];

           $user_id = wp_insert_user($user_data);
           if (is_wp_error($user_id)) throw new \Exception($user_id->get_error_message());

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

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $agency = $this->agencyModel->find($employee->agency_id);
            if (!$agency) throw new \Exception('Agency not found');

            if (!$this->validator->canEditEmployee($employee, $agency)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengedit karyawan ini.');
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
                   : 'active'
           ];

           $errors = $this->validator->validateUpdate($data, $id);
           if (!empty($errors)) throw new \Exception(implode(', ', $errors));

           if (!$this->model->update($id, $data)) {
               throw new \Exception('Failed to update employee');
           }

           $this->cache->delete("employee_{$id}");
           $employee = $this->model->find($id);
           if ($employee) {
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

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $agency = $this->agencyModel->find($employee->agency_id);
            if (!$agency) throw new \Exception('Agency not found');

            if (!$this->validator->canDeleteEmployee($employee, $agency)) {
                throw new \Exception('Anda tidak memiliki izin untuk menghapus karyawan ini.');
            }

           $errors = $this->validator->validateDelete($id);
           if (!empty($errors)) throw new \Exception(reset($errors));

           if (!$this->model->delete($id)) {
               throw new \Exception('Failed to delete employee');
           }

           $this->cache->delete("employee_{$id}");
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

            $employee = $this->model->find($id);
            if (!$employee) throw new \Exception('Employee not found');

            $agency = $this->agencyModel->find($employee->agency_id);
            if (!$agency) throw new \Exception('Agency not found');

            if (!$this->validator->canEditEmployee($employee, $agency)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengubah status karyawan ini.');
            }

           $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
           if (!in_array($status, ['active', 'inactive'])) {
               throw new \Exception('Invalid status');
           }

           $employee = $this->model->find($id);
           if (!$employee) throw new \Exception('Employee not found');

           if (!$this->model->changeStatus($id, $status)) {
               throw new \Exception('Failed to update employee status');
           }

           $this->cache->delete("employee_{$id}");
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
     * Khusus untuk membuat demo data employee
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

            // Clear semua cache yang terkait
            $this->cache->delete('employee', $employee_id);
            $this->cache->delete('employee_total_count', get_current_user_id());
            
            // Cache untuk relasi dengan agency
            $this->cache->delete('agency_employee', $data['agency_id']);
            $this->cache->delete('agency_employee_list', $data['agency_id']);
            
            // Cache untuk relasi dengan division
            $this->cache->delete('division_employee', $data['division_id']);
            $this->cache->delete('division_employee_list', $data['division_id']);
            
            // Invalidate DataTable cache
            $this->cache->invalidateDataTableCache('agency_employee_list', [
                'agency_id' => $data['agency_id']
            ]);

            // Invalidate cache agency dan division
            $this->cache->invalidateAgencyCache($data['agency_id']);
            $this->cache->invalidateDataTableCache('division_list', [
                'agency_id' => $data['agency_id']
            ]);

            return $employee_id;

        } catch (\Exception $e) {
            $this->debug_log('Error creating demo employee: ' . $e->getMessage());
            throw $e;
        }
    }
}
