<?php
/**
 * Cache Management Class
 *
 * @package     WP_Agency
 * @subpackage  Cache
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Cache/CacheManager.php
 *
 * Description: Manager untuk menangani caching data agency.
 *              Menggunakan WordPress Object Cache API.
 *              Includes cache untuk:
 *              - Single agency/division/employee data
 *              - Lists (agency/division/employee)
 *              - Statistics
 *              - Relations
 *
 * Cache Groups:
 * - wp_agency: Grup utama untuk semua cache
 * 
 * Cache Keys:
 * - agency_{id}: Data single agency
 * - agency_list: Daftar semua agency
 * - agency_stats: Statistik agency
 * - division_{id}: Data single division
 * - division_list: Daftar semua division
 * - division_stats: Statistik division
 * - employee_{id}: Data single employee
 * - employee_list: Daftar semua employee
 * - employee_stats: Statistik employee
 * - user_agencies_{user_id}: Relasi user-agency
 * 
 * Dependencies:
 * - WordPress Object Cache API
 * 
 * Changelog:
 * 3.0.0 - 2024-01-31
 * - Added division caching support
 * - Added employee caching support
 * - Extended cache key management
 * - Added statistics caching for all entities
 * 
 * 2.0.0 - 2024-01-20
 * - Added agency statistics cache
 * - Added user-agency relations cache
 * - Added cache group invalidation
 * - Enhanced cache key management
 * - Improved cache expiry handling
 * 
 * 1.0.0 - 2024-12-03
 * - Initial implementation
 * - Basic agency data caching
 * - List caching functionality
 */
/**
 * Cache Management Class
 * 
 * @package     WP_Agency
 * @subpackage  Cache
 */
namespace WPAgency\Cache;

class AgencyCacheManager {

    // Cache keys
    private const CACHE_GROUP = 'wp_agency';
    private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;

    // Cache keys for agencies
    private const KEY_AGENCY = 'agency';
    private const KEY_AGENCY_LIST = 'agency_list';
    private const KEY_AGENCY_STATS = 'agency_stats';
    private const KEY_USER_AGENCYS = 'user_agencies';

    // Cache keys for divisiones
    private const KEY_AGENCY_BRANCH_LIST = 'agency_division_list';
    private const KEY_AGENCY_BRANCH = 'agency_division';
    private const KEY_BRANCH = 'division';
    private const KEY_BRANCH_LIST = 'division_list';
    private const KEY_BRANCH_STATS = 'division_stats';
    private const KEY_USER_BRANCHES = 'user_divisiones';

    // Cache keys for employees
    private const KEY_EMPLOYEE = 'employee';
    private const KEY_EMPLOYEE_LIST = 'employee_list';
    private const KEY_AGENCY_EMPLOYEE_LIST = 'agency_employee_list';
    private const KEY_EMPLOYEE_STATS = 'employee_stats';
    private const KEY_USER_EMPLOYEES = 'user_employees';

    // Getter methods for external access to constants
    public static function getCacheGroup(): string {
        return self::CACHE_GROUP;
    }

    public static function getCacheExpiry(): int {
        return self::CACHE_EXPIRY;
    }

    public static function getCacheKey(string $type): string {
        $constants = [
            'agency' => self::KEY_AGENCY,
            'agency_list' => self::KEY_AGENCY_LIST,
            'agency_stats' => self::KEY_AGENCY_STATS,
            'user_agencies' => self::KEY_USER_AGENCYS,
            'division' => self::KEY_BRANCH,
            'division_list' => self::KEY_BRANCH_LIST,
            'division_stats' => self::KEY_BRANCH_STATS,
            'user_divisiones' => self::KEY_USER_BRANCHES,
            'employee' => self::KEY_EMPLOYEE,
            'employee_list' => self::KEY_EMPLOYEE_LIST,
            'employee_stats' => self::KEY_EMPLOYEE_STATS,
            'user_employees' => self::KEY_USER_EMPLOYEES,
        ];

        return $constants[$type] ?? '';
    }

    /**
     * Generates valid cache key based on components
     */
    private function generateKey(string ...$components): string {
        // Filter out empty components
        $validComponents = array_filter($components, function($component) {
            return !empty($component) && is_string($component);
        });
        
        if (empty($validComponents)) {
            // Instead of returning empty key or default key, throw exception
            //throw new \InvalidArgumentException('Cache key cannot be generated from empty components');

            // error_log('Cache key cannot be generated from empty components : '. print_r($validComponents));
 
            return 'default_' . md5(serialize($components));
        }

        // Join with underscore and ensure valid length
        $key = implode('_', $validComponents);
        
        // WordPress has a key length limit of 172 characters
        if (strlen($key) > 172) {
            $key = substr($key, 0, 140) . '_' . md5($key);
        }
        
        return $key;
    }

    /**
     * Get value from cache with validation
     */
    public function get(string $type, ...$keyComponents) {
        $key = $this->generateKey($type, ...$keyComponents);
        // error_log("Cache key generated: " . $key);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            //error_log("Cache attempt - Key: {$key}, Type: {$type}");
        }
        
        $result = wp_cache_get($key, self::CACHE_GROUP);
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                //error_log("Cache miss - Key: {$key}");
            }
            return null;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            //error_log("Cache hit - Key: {$key}");
        }
        
        return $result;
    }

    /**
     * Set value in cache with validation
     */
    public function set(string $type, $value, int $expiry = null, ...$keyComponents): bool {
        try {
            $key = $this->generateKey($type, ...$keyComponents);

            if ($expiry === null) {
                $expiry = self::CACHE_EXPIRY;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                //error_log("Setting cache - Key: {$key}, Type: {$type}, Expiry: {$expiry}s");
            }
            
            return wp_cache_set($key, $value, self::CACHE_GROUP, $expiry);
        } catch (\InvalidArgumentException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                //error_log("Cache set failed: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Delete value from cache
     */
    public function delete(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            //error_log("Deleting cache - Key: {$key}, Type: {$type}");
        }
        
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Check if key exists in cache
     */
    public function exists(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        return wp_cache_get($key, self::CACHE_GROUP) !== false;
    }

    public function getDataTableCache(
        string $context,      // Misal: 'agency_list', 'agency_history', 'division_list', dll
        string $access_type,
        int $start,
        int $length,
        string $search,
        string $orderColumn,
        string $orderDir,
        ?array $additionalParams = null  // Parameter tambahan spesifik untuk context
    ) {
        // Validate required parameters
        if (empty($context) || !$access_type || !is_numeric($start) || !is_numeric($length)) {
            $this->debug_log('Invalid parameters in getDataTableCache');
            return null;
        }
        
        try {
            // Build cache key components
            $components = [
                "datatable",      // prefix
                $context,         // specific context
                (string)$access_type,
                (string)$start,
                (string)$length,
                md5($search),
                (string)$orderColumn,
                (string)$orderDir
            ];

            // Add additional parameters if provided
            if ($additionalParams) {
                foreach ($additionalParams as $key => $value) {
                    $components[] = $key . '_' . md5(serialize($value));
                }
            }

            return $this->get(...$components);

        } catch (\Exception $e) {
            $this->debug_log("Error getting datatable data for context {$context}: " . $e->getMessage());
            return null;
        }
    }

    public function setDataTableCache(
        string $context,
        string $access_type,
        int $start,
        int $length,
        string $search,
        string $orderColumn,
        string $orderDir,
        $data,
        ?array $additionalParams = null
    ) {
        // Validate required parameters
        if (empty($context) || !$access_type || !is_numeric($start) || !is_numeric($length)) {
            $this->debug_log('Invalid parameters in setDataTableCache');
            return false;
        }

        // Build cache key components
        $components = [
            "datatable",
            $context,
            (string)$access_type,
            (string)$start,
            (string)$length,
            md5($search),
            (string)$orderColumn,
            (string)$orderDir
        ];

        // Add additional parameters if provided
        if ($additionalParams) {
            foreach ($additionalParams as $key => $value) {
                $components[] = $key . '_' . md5(serialize($value));
            }
        }

        return $this->set('datatable', $data, 2 * MINUTE_IN_SECONDS, ...$components);
    }

    /**
     * Invalidate DataTable cache for specific context
     * 
     * @param string $context Context name (e.g. 'agency_list', 'agency_divisiones')
     * @param array|null $filters Additional filters that were used (e.g. ['agency_id' => 123])
     * @return bool True if cache was invalidated, false otherwise
     */
    public function invalidateDataTableCache(string $context, ?array $filters = null): bool {
        try {
            if (empty($context)) {
                $this->debug_log('Invalid context in invalidateDataTableCache');
                return false;
            }

            // Log invalidation attempt
            $this->debug_log(sprintf(
                'Attempting to invalidate DataTable cache - Context: %s, Filters: %s',
                $context,
                $filters ? json_encode($filters) : 'none'
            ));

            // Base cache key components
            $components = [
                'datatable',
                $context
            ];

            // If we have filters, create filter-specific invalidation
            if ($filters) {
                foreach ($filters as $key => $value) {
                    // Add each filter to components
                    $components[] = sprintf('%s_%s', $key, md5(serialize($value)));
                }
                
                // Delete specific filtered cache
                $result = $this->delete(...$components);
                
                $this->debug_log(sprintf(
                    'Invalidated filtered cache for context %s with filters. Result: %s',
                    $context,
                    $result ? 'success' : 'failed'
                ));
                
                return $result;
            }

            // If no filters, do a broader invalidation using deleteByPrefix
            $prefix = implode('_', $components);
            $result = $this->deleteByPrefix($prefix);

            $this->debug_log(sprintf(
                'Invalidated all cache entries for context %s. Result: %s',
                $context,
                $result ? 'success' : 'failed'
            ));

            return $result;

        } catch (\Exception $e) {
            $this->debug_log('Error in invalidateDataTableCache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all cache entries that match a prefix
     * 
     * @param string $prefix The prefix to match
     * @return bool True if operation was successful
     */
    private function deleteByPrefix(string $prefix): bool {
        try {
            global $wp_object_cache;

            // If using WordPress default object cache
            if (isset($wp_object_cache->cache[self::CACHE_GROUP])) {
                foreach ($wp_object_cache->cache[self::CACHE_GROUP] as $key => $value) {
                    if (strpos($key, $prefix) === 0) {
                        wp_cache_delete($key, self::CACHE_GROUP);
                    }
                }
            } else {
                // For persistent caching plugins (e.g., Redis, Memcached)
                // Get all keys in our group (if supported by the caching plugin)
                $keys = wp_cache_get_multiple([self::CACHE_GROUP . '_keys'], self::CACHE_GROUP);
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        if (strpos($key, $prefix) === 0) {
                            wp_cache_delete($key, self::CACHE_GROUP);
                        }
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            $this->debug_log('Error in deleteByPrefix: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper method to generate cache key for DataTable
     * 
     * @param string $context The DataTable context
     * @param array $components Additional key components
     * @return string The generated cache key
     */
    private function generateDataTableCacheKey(string $context, array $components): string {
        $key_parts = ['datatable', $context];
        
        foreach ($components as $component) {
            if (is_scalar($component)) {
                $key_parts[] = (string)$component;
            } else {
                $key_parts[] = md5(serialize($component));
            }
        }
        
        return implode('_', $key_parts);
    }

    /**
     * Logger method for debugging cache operations
     * 
     * @param string $message The message to log
     * @param mixed $data Optional data to include in log
     */
    private function debug_log(string $message, $data = null): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CacheManager] %s %s',
                $message,
                $data ? '| Data: ' . print_r($data, true) : ''
            ));
        }
    }

    // Method untuk invalidate cache saat ada update
    public function invalidateAgencyCache(int $id): void {
        $this->delete('agency_detail', $id);
        $this->delete('division_count', $id);
        $this->delete('agency', $id);
        // Clear agency list cache
        $this->delete('agency_total_count', get_current_user_id());
    }

    /**
     * Clear all caches in group
     * Alias method to maintain backward compatibility
     * 
     * @return bool True if cache was cleared successfully
     */
    public function clearAllCaches(): bool {
        return $this->clearAll();
    }

private function clearCache(): bool {
    try {
        global $wp_object_cache;

        // Check if using default WordPress object cache
        if (isset($wp_object_cache->cache[self::CACHE_GROUP])) {
            if (is_array($wp_object_cache->cache[self::CACHE_GROUP])) {
                foreach (array_keys($wp_object_cache->cache[self::CACHE_GROUP]) as $key) {
                    wp_cache_delete($key, self::CACHE_GROUP);
                }
            }
            unset($wp_object_cache->cache[self::CACHE_GROUP]);
            return true;
        }

        // Alternative approach for external cache plugins
        if (function_exists('wp_cache_flush_group')) {
            // Some caching plugins provide group-level flush
            return wp_cache_flush_group(self::CACHE_GROUP);
        }

        // Fallback method - iteratively clear known cache keys
        $known_types = [
            'agency',
            'agency_list',
            'agency_total_count',
            'agency_membership',
            'membership',
            'division',
            'division_list',
            'employee',
            'employee_list',
            'datatable'
        ];

        foreach ($known_types as $type) {
            if ($cached_keys = wp_cache_get($type . '_keys', self::CACHE_GROUP)) {
                if (is_array($cached_keys)) {
                    foreach ($cached_keys as $key) {
                        wp_cache_delete($key, self::CACHE_GROUP);
                    }
                }
            }
        }

        // Also clear the master key list
        wp_cache_delete('cache_keys', self::CACHE_GROUP);

        return true;

    } catch (\Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Error clearing cache: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Clear all caches in group with enhanced error handling
 * 
 * @return bool True if cache was cleared successfully
 */
public function clearAll(): bool {
    try {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Attempting to clear all caches in group: ' . self::CACHE_GROUP);
        }

        $result = $this->clearCache();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Cache clear result: ' . ($result ? 'success' : 'failed'));
        }

        return $result;
    } catch (\Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Error in clearAll(): ' . $e->getMessage());
        }
        return false;
    }
}

}
