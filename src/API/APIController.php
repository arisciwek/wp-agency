 <?php
/**
 * WP Agency REST API Controller
 *
 * @package     WP_Agency
 * @subpackage  API
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/API/APIController.php
 *
 * Description: Controller untuk mengelola REST API WP Agency.
 *              Handles endpoint registration dan request processing.
 *              Includes authentication, validation dan response formatting.
 *              
 * Dependencies:
 * - WordPress REST API
 * - WPAgency\Models\Agency\AgencyModel
 * - WPAgency\Models\Membership\MembershipLevelModel
 *
 * Changelog:
 * 1.0.0 - 2024-02-16
 * - Initial version
 * - Added CRUD endpoints for agencies
 * - Added membership level endpoints
 * - Added CORS support
 */

namespace WPAgency\API;

class APIController {
    private const API_NAMESPACE = 'wp-agency/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Agency endpoints
        register_rest_route(self::API_NAMESPACE, '/agencies', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_agencies'],
                'permission_callback' => [$this, 'check_agency_list_permission'],
                'args' => [
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint'
                    ],
                    'per_page' => [
                        'default' => 10,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_agency'],
                'permission_callback' => [$this, 'check_create_permission'],
                'args' => [
                    'name' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'npwp' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'nib' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'  
                    ]
                ]
            ]
        ]);

        // Single agency endpoint
        register_rest_route(self::API_NAMESPACE, '/agencies/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_agency'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_agency'],
                'permission_callback' => [$this, 'check_update_permission'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ],
                    'name' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_agency'],
                'permission_callback' => [$this, 'check_delete_permission']
            ]
        ]);

        // Membership level endpoints
        register_rest_route(self::API_NAMESPACE, '/membership-levels', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_membership_levels'],
                'permission_callback' => [$this, 'check_membership_permission']
            ]
        ]);
    }

    /**
     * Permission checks
     */
    public function check_agency_list_permission(\WP_REST_Request $request) {
        return current_user_can('view_agency_list');
    }

    public function check_read_permission(\WP_REST_Request $request) {
        $agency_id = $request->get_param('id');
        
        // Check if user can view all agencies
        if (current_user_can('view_agency_detail')) {
            return true;
        }

        // Check if user owns this agency
        if (current_user_can('view_own_agency')) {
            $agency = new \WPAgency\Models\Agency\AgencyModel();
            return $agency->isOwner(get_current_user_id(), $agency_id);
        }

        return false;
    }

    public function check_create_permission() {
        return current_user_can('add_agency');
    }

    public function check_update_permission(\WP_REST_Request $request) {
        $agency_id = $request->get_param('id');
        
        if (current_user_can('edit_all_agencies')) {
            return true;
        }

        if (current_user_can('edit_own_agency')) {
            $agency = new \WPAgency\Models\Agency\AgencyModel();
            return $agency->isOwner(get_current_user_id(), $agency_id);
        }

        return false;
    }

    public function check_delete_permission(\WP_REST_Request $request) {
        return current_user_can('delete_agency');
    }

    public function check_membership_permission() {
        return is_user_logged_in();
    }

    /**
     * Endpoint handlers
     */
    public function get_agencies(\WP_REST_Request $request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            
            $model = new \WPAgency\Models\Agency\AgencyModel();
            $agencies = $model->paginate($page, $per_page);
            
            return new \WP_REST_Response([
                'success' => true,
                'data' => $agencies
            ], 200);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_agency(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            
            $model = new \WPAgency\Models\Agency\AgencyModel();
            $agency = $model->find($id);
            
            if (!$agency) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Agency not found'
                ], 404);
            }

            return new \WP_REST_Response([
                'success' => true,
                'data' => $agency
            ], 200);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create_agency(\WP_REST_Request $request) {
        try {
            $data = [
                'name' => $request->get_param('name'),
                'npwp' => $request->get_param('npwp'),
                'nib' => $request->get_param('nib'),
                'created_by' => get_current_user_id()
            ];

            $model = new \WPAgency\Models\Agency\AgencyModel();
            $id = $model->create($data);

            if (!$id) {
                throw new \Exception('Failed to create agency');
            }

            $agency = $model->find($id);

            return new \WP_REST_Response([
                'success' => true,
                'data' => $agency,
                'message' => 'Agency created successfully'
            ], 201);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update_agency(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            $data = [];

            // Only update provided fields
            foreach (['name', 'npwp', 'nib'] as $field) {
                if ($request->has_param($field)) {
                    $data[$field] = $request->get_param($field);
                }
            }

            $model = new \WPAgency\Models\Agency\AgencyModel();
            $updated = $model->update($id, $data);

            if (!$updated) {
                throw new \Exception('Failed to update agency');
            }

            $agency = $model->find($id);

            return new \WP_REST_Response([
                'success' => true,
                'data' => $agency,
                'message' => 'Agency updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete_agency(\WP_REST_Request $request) {
        try {
            $id = $request->get_param('id');
            
            $model = new \WPAgency\Models\Agency\AgencyModel();
            $deleted = $model->delete($id);

            if (!$deleted) {
                throw new \Exception('Failed to delete agency');
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Agency deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_membership_levels() {
        try {
            $model = new \WPAgency\Models\Membership\MembershipLevelModel();
            $levels = $model->get_all_levels();

            return new \WP_REST_Response([
                'success' => true,
                'data' => $levels
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
