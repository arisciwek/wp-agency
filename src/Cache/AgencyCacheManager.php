<?php
/**
 * Cache Management Class
 *
 * @package     WP_Agency
 * @subpackage  Cache
 * @version     1.0.7
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
    private const CACHE_EXPIRY = 1 * HOUR_IN_SECONDS;

    // Cache keys for agencies
    private const KEY_AGENCY = 'agency';
    private const KEY_AGENCY_LIST = 'agency_list';
    private const KEY_AGENCY_STATS = 'agency_stats';
    private const KEY_USER_AGENCYS = 'user_agencies';

    // Cache keys for divisions
    private const KEY_AGENCY_DIVISION_LIST = 'agency_division_list';
    private const KEY_AGENCY_DIVISION = 'agency_division';
    private const KEY_DIVISION = 'division';
    private const KEY_DIVISION_LIST = 'division_list';
    private const KEY_DIVISION_STATS = 'division_stats';
    private const KEY_USER_DIVISIONS = 'user_divisions';

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

    public static function _getCacheKey(string $type): string {
        $constants = [
            'agency' => self::KEY_AGENCY,
            'agency_list' => self::KEY_AGENCY_LIST,
            'agency_stats' => self::KEY_AGENCY_STATS,
            'user_agencies' => self::KEY_USER_AGENCYS,
            'division' => self::KEY_DIVISION,
            'division_list' => self::KEY_DIVISION_LIST,
            'division_stats' => self::KEY_DIVISION_STATS,
            'user_divisions' => self::KEY_USER_DIVISIONS,
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

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache attempt - Key: {$key}, Type: {$type}");
        }
        
        $result = wp_cache_get($key, self::CACHE_GROUP);
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Cache miss - Key: {$key}");
            }
            return null;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Cache hit - Key: {$key}");
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
                error_log("Setting cache - Key: {$key}, Type: {$type}, Expiry: {$expiry}s");
            }
            
            return wp_cache_set($key, $value, self::CACHE_GROUP, $expiry);
        } catch (\InvalidArgumentException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Cache set failed: " . $e->getMessage());
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

    /**
     * Get cached DataTable data
     * Note: DataTable caching is disabled to prevent stale responses
     */
    public function getDataTableCache(
        string $context,
        string $access_type,
        int $start,
        int $length,
        string $search,
        string $orderColumn,
        string $orderDir,
        ?array $additionalParams = null
    ) {
        // DataTable caching is disabled - always return null to force fresh data
        return null;
    }

    /**
     * Set DataTable data in cache
     */
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

        // Build components untuk kunci cache - SAMA PERSIS dengan getDataTableCache
        $components = [
            $context,         // context specific (agency_list, division_list, etc)
            (string)$access_type,
            (string)$start,
            (string)$length,
            md5($search),
            (string)$orderColumn,
            (string)$orderDir
        ];

        // Add additional parameters if provided - SAMA PERSIS dengan getDataTableCache
        if ($additionalParams) {
            foreach ($additionalParams as $key => $value) {
                $components[] = $key . '_' . md5(serialize($value));
            }
        }

        // Disable caching for DataTable to prevent stale responses
        // return $this->set('datatable', $data, 0, ...$components);
        return true; // Skip caching
    }

    /**
     * Invalidate DataTable cache for a specific context
     * Clears all cached DataTable responses for the given context
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

            // Always use prefix-based deletion to clear all related DataTable caches
            // This ensures all pages, searches, and sorts are cleared for the context
            $prefix = 'datatable_' . $context;
            $result = $this->deleteByPrefix($prefix);

            $this->debug_log(sprintf(
                'Invalidated all DataTable cache entries for context %s. Result: %s',
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
     * Improved version of deleteByPrefix
     */
    private function deleteByPrefix(string $prefix): bool {
        global $wp_object_cache;
        
        // Jika grup tidak ada, tidak ada yang perlu dihapus
        if (!isset($wp_object_cache->cache[self::CACHE_GROUP])) {
            $this->debug_log('Cache group not found - nothing to delete');
            return true;
        }
        
        // Jika grup kosong, tidak ada yang perlu dihapus
        if (empty($wp_object_cache->cache[self::CACHE_GROUP])) {
            $this->debug_log('Cache group empty - nothing to delete');
            return true;
        }
        
        $deleted = 0;
        $keys = array_keys($wp_object_cache->cache[self::CACHE_GROUP]);
        
        foreach ($keys as $key) {
            if (strpos($key, $prefix) === 0) {
                $result = wp_cache_delete($key, self::CACHE_GROUP);
                if ($result) $deleted++;
            }
        }
        
        $this->debug_log(sprintf('Deleted %d keys with prefix %s', $deleted, $prefix));
        return true;
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

    /**
     * Memperlihatkan semua cache keys yang tersimpan
     */
    public function dumpCacheKeys() {
        global $wp_object_cache;
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return 'Debug mode not enabled';
        }
        
        $output = "Cache keys in " . self::CACHE_GROUP . ":\n";
        
        if (isset($wp_object_cache->cache[self::CACHE_GROUP])) {
            $keys = array_keys($wp_object_cache->cache[self::CACHE_GROUP]);
            foreach ($keys as $key) {
                $output .= "- $key\n";
            }
            $output .= "Total: " . count($keys) . " keys";
        } else {
            $output .= "No keys found or cache group not accessible";
        }
        
        error_log($output);
        return $output;
    }

}
