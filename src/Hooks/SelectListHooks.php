<?php
/**
* Select List Hooks Class
*
* @package     WP_Agency
* @subpackage  Hooks
* @version     1.0.0
* @author      arisciwek
*
* Path: /wp-agency/src/Hooks/SelectListHooks.php
*
* Description: Hooks untuk mengelola select list agency dan kabupaten.
*              Menyediakan filter dan action untuk render select lists.
*              Includes dynamic loading untuk kabupaten berdasarkan agency.
*              Terintegrasi dengan cache system.
*
* Hooks yang tersedia:
* - wp_agency_get_agency_options (filter)
* - wp_agency_get_division_options (filter) 
* - wp_agency_agency_select (action)
* - wp_agency_division_select (action)
*
* Changelog:
* 1.0.0 - 2024-01-06
* - Initial implementation
* - Added agency options filter
* - Added division options filter
* - Added select rendering actions
* - Added cache integration
*/


namespace WPAgency\Hooks;

use WPAgency\Models\AgencyModel;
use WPAgency\Models\Division\DivisionModel;
use WPAgency\Cache\WPCache;

class SelectListHooks {
    private $agency_model;
    private $division_model;
    private $cache;
    private $debug_mode;

    public function __construct() {
        $this->agency_model = new AgencyModel();
        $this->division_model = new DivisionModel();
        $this->cache = new WPCache();
        $this->debug_mode = apply_filters('wp_agency_debug_mode', false);
        
        $this->registerHooks();
    }

    private function registerHooks() {
        // Register filters
        add_filter('wp_agency_get_agency_options', [$this, 'getAgencyOptions'], 10, 2);
        add_filter('wp_agency_get_division_options', [$this, 'getDivisionOptions'], 10, 3);
        
        // Register actions
        add_action('wp_agency_agency_select', [$this, 'renderAgencySelect'], 10, 2);
        add_action('wp_agency_division_select', [$this, 'renderDivisionSelect'], 10, 3);
        
        // Register AJAX handlers
        add_action('wp_ajax_get_division_options', [$this, 'handleAja.DivisionOptions']);
        add_action('wp_ajax_nopriv_get_division_options', [$this, 'handleAja.DivisionOptions']);
    }

    /**
     * Get agency options with caching
     */
    public function getAgencyOptions(array $default_options = [], bool $include_empty = true): array {
        try {
            $cache_key = 'agency_options_' . md5(serialize($default_options) . $include_empty);
            
            // Try to get from cache first
            $options = $this->cache->get($cache_key);
            if (false !== $options) {
                $this->debugLog('Retrieved agency options from cache');
                return $options;
            }

            $options = $default_options;
            
            if ($include_empty) {
                $options[''] = __('Pilih Agency', 'wp-agency');
            }

            $agencies = $this->agency_model->getAllAgencys();
            foreach ($agencies as $agency) {
                $options[$agency->id] = esc_html($agency->name);
            }

            // Cache the results
            $this->cache->set($cache_key, $options);
            $this->debugLog('Cached new agency options');

            return $options;

        } catch (\Exception $e) {
            $this->logError('Error getting agency options: ' . $e->getMessage());
            return $default_options;
        }
    }

    /**
     * Get division options with caching
     */
    public function getDivisionOptions(array $default_options = [], ?int $agency_id = null, bool $include_empty = true): array {
        try {
            if ($agency_id) {
                $cache_key = "division_options_{$agency_id}_" . md5(serialize($default_options) . $include_empty);
                
                // Try cache first
                $options = $this->cache->get($cache_key);
                if (false !== $options) {
                    $this->debugLog("Retrieved division options for agency {$agency_id} from cache");
                    return $options;
                }
            }

            $options = $default_options;
            
            if ($include_empty) {
                $options[''] = __('Pilih Division', 'wp-agency');
            }

            if ($agency_id) {
                $divisions = $this->division_model->getByAgency($agency_id);
                foreach ($divisions as $division) {
                    $options[$division->id] = esc_html($division->name);
                }

                // Cache the results
                $this->cache->set($cache_key, $options);
                $this->debugLog("Cached new division options for agency {$agency_id}");
            }

            return $options;

        } catch (\Exception $e) {
            $this->logError('Error getting division options: ' . $e->getMessage());
            return $default_options;
        }
    }

    /**
     * Render agency select element
     */
    public function renderAgencySelect(array $attributes = [], ?int $selected_id = null): void {
        try {
            $default_attributes = [
                'name' => 'agency_id',
                'id' => 'agency_id',
                'class' => 'wp-agency-agency-select'
            ];

            $attributes = wp_parse_args($attributes, $default_attributes);
            $options = $this->getAgencyOptions();

            $this->renderSelect($attributes, $options, $selected_id);

        } catch (\Exception $e) {
            $this->logError('Error rendering agency select: ' . $e->getMessage());
            echo '<p class="error">' . esc_html__('Error loading agency selection', 'wp-agency') . '</p>';
        }
    }

    /**
     * Render division select element
     */
    public function renderDivisionSelect(array $attributes = [], ?int $agency_id = null, ?int $selected_id = null): void {
        try {
            $default_attributes = [
                'name' => 'division_id',
                'id' => 'division_id',
                'class' => 'wp-agency-division-select'
            ];

            $attributes = wp_parse_args($attributes, $default_attributes);
            $options = $this->getDivisionOptions([], $agency_id);

            $this->renderSelect($attributes, $options, $selected_id);

        } catch (\Exception $e) {
            $this->logError('Error rendering division select: ' . $e->getMessage());
            echo '<p class="error">' . esc_html__('Error loading division selection', 'wp-agency') . '</p>';
        }
    }

    /**
     * Handle AJAX request for division options
     */
    public function handleAja.DivisionOptions(): void {
        try {
            if (!check_ajax_referer('wp_agency_select_nonce', 'nonce', false)) {
                throw new \Exception('Invalid security token');
            }

            $agency_id = isset($_POST['agency_id']) ? absint($_POST['agency_id']) : 0;
            if (!$agency_id) {
                throw new \Exception('Invalid agency ID');
            }

            $options = $this->getDivisionOptions([], $agency_id);
            $html = $this->generateOptionsHtml($options);

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logError('AJAX Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Gagal memuat data cabang', 'wp-agency')
            ]);
        }
    }

    /**
     * Helper method to render select element
     */
    private function renderSelect(array $attributes, array $options, ?int $selected_id): void {
        ?>
        <select <?php echo $this->buildAttributes($attributes); ?>>
            <?php foreach ($options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" 
                    <?php selected($selected_id, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Generate HTML for select options
     */
    private function generateOptionsHtml(array $options): string {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label)
            );
        }
        return $html;
    }

    /**
     * Build HTML attributes string
     */
    private function buildAttributes(array $attributes): string {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= sprintf(' %s', esc_attr($key));
                }
            } else {
                $html .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
        }
        return $html;
    }

    /**
     * Debug logging
     */
    private function debugLog(string $message): void {
        if ($this->debug_mode) {
            error_log('WP Select Debug: ' . $message);
        }
    }

    /**
     * Error logging
     */
    private function logError(string $message): void {
        error_log('WP Select Error: ' . $message);
    }
}
