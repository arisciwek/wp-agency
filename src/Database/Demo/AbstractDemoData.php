<?php
/**
 * Abstract Base Class for Demo Data Generation
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/AbstractDemoData.php
 *
 * Description: Base abstract class for demo data generation.
 *              Provides common functionality and structure for:
 *              - Membership levels data generation
 *              - Agency data generation
 *              - Division data generation
 *              - Employee data generation
 *              
 * Order of Execution:
 * 1. Membership Levels (base config)
 * 2. Agencys (with WP Users)
 * 3. Divisiones 
 * 4. Employees
 *
 * Dependencies:
 * - WPUserGenerator for WordPress user creation
 * - WordPress database ($wpdb)
 * - WPAgency Models:
 *   * AgencyMembershipModel
 *   * AgencyModel
 *   * DivisionModel
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added base abstract structure
 * - Added model dependencies
 */

namespace WPAgency\Database\Demo;

use WPAgency\Cache\AgencyCacheManager;

defined('ABSPATH') || exit;

abstract class AbstractDemoData {
    protected $wpdb;
    protected $agencyMembershipModel;
    protected $agencyModel;
    protected $divisionModel;
    protected AgencyCacheManager $cache;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Initialize cache manager immediately since it doesn't require plugins_loaded
        $this->cache = new AgencyCacheManager();
        
        // Initialize models after plugins are loaded to prevent memory issues
        add_action('plugins_loaded', [$this, 'initModels'], 30);
    }

    public function initModels() {
        // Only initialize if not already done
        if (!isset($this->agencyModel)) {
            if (class_exists('\WPAgency\Models\Agency\AgencyModel')) {
                $this->agencyModel = new \WPAgency\Models\Agency\AgencyModel();
            }
        }
        
        if (!isset($this->divisionModel)) {
            if (class_exists('\WPAgency\Models\Division\DivisionModel')) {
                $this->divisionModel = new \WPAgency\Models\Division\DivisionModel();
            }
        }
        
        if (!isset($this->agencyMembershipModel)) {
            if (class_exists('\WPAgency\Models\Agency\AgencyMembershipModel')) {
                $this->agencyMembershipModel = new \WPAgency\Models\Agency\AgencyMembershipModel();
            }
        }
    }

    abstract protected function generate();
    abstract protected function validate();

    public function run() {
        try {
            // Ensure models are initialized
            $this->initModels();
            
            // Increase memory limit for demo data generation
            wp_raise_memory_limit('admin');
            
            $this->wpdb->query('START TRANSACTION');
            
            if (!$this->validate()) {
                throw new \Exception("Validation failed in " . get_class($this));
            }

            $this->generate();

            $this->wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $this->debug("Demo data generation failed: " . $e->getMessage());
            return false;
        }
    }

    protected function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[" . get_class($this) . "] {$message}");
        }
    }
}
