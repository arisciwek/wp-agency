<?php
/**
 * Agency Cache Manager
 *
 * @package     WP_Agency
 * @subpackage  Cache
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Cache/AgencyCacheManager.php
 *
 * Description: Cache manager untuk Agency entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk agency data, relations, dan DataTable.
 *
 * Changelog:
 * 2.0.0 - 2025-12-28 (AbstractCRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractCacheManager
 * - Added getInstance() singleton pattern
 * - Implements 5 abstract methods
 * - Cache expiry: 12 hours (default)
 * - Cache group: wp_agency
 * - Adapted from CustomerCacheManager pattern
 *
 * 1.0.0 - 2024-12-03
 * - Initial implementation
 * - Basic agency data caching
 */

namespace WPAgency\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class AgencyCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AgencyCacheManager
     */
    public static function getInstance(): AgencyCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (5 required)
    // ========================================

    /**
     * Get cache group name
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_agency';
    }

    /**
     * Get cache expiry time
     *
     * @return int Cache expiry in seconds (12 hours)
     */
    protected function getCacheExpiry(): int {
        return 12 * HOUR_IN_SECONDS;
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'agency';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'agency' => 'agency',
            'agency_list' => 'agency_list',
            'agency_stats' => 'agency_stats',
            'agency_total_count' => 'agency_total_count',
            'agency_relation' => 'agency_relation',
            'agency_by_code' => 'agency_by_code',
            'division_count' => 'division_count',
            'employee_count' => 'employee_count',
            'agency_ids' => 'agency_ids',
            'code_exists' => 'code_exists',
            'name_exists' => 'name_exists',
            'user_agencies' => 'user_agencies',
            'user_relation' => 'user_relation'
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'agency',
            'agency_list',
            'agency_stats',
            'agency_total_count',
            'agency_relation',
            'agency_by_code',
            'division_count',
            'employee_count',
            'agency_ids',
            'code_exists',
            'name_exists',
            'user_agencies',
            'user_relation',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get agency from cache
     *
     * @param int $id Agency ID
     * @return object|false Agency object or FALSE if not found (cache miss)
     */
    public function getAgency(int $id): object|false {
        return $this->get('agency', $id);
    }

    /**
     * Set agency in cache
     *
     * @param int $id Agency ID
     * @param object $agency Agency data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setAgency(int $id, object $agency, ?int $expiry = null): bool {
        return $this->set('agency', $agency, $expiry, $id);
    }

    /**
     * Invalidate agency cache
     *
     * Clears all cache related to a specific agency:
     * - Agency entity
     * - DataTable cache
     * - Relation cache
     * - Stats cache
     *
     * @param int $id Agency ID
     * @return void
     */
    public function invalidateAgencyCache(int $id): void {
        // Clear agency entity cache
        $this->delete('agency', $id);

        // Clear relation cache for this agency
        $this->clearCache('agency_relation');
        $this->clearCache('user_relation');

        // Clear DataTable cache
        $this->invalidateDataTableCache('agency_list');

        // Clear stats cache
        $this->clearCache('agency_stats');
        $this->clearCache('agency_total_count');

        // Clear division/employee count cache
        $this->delete('division_count', $id);
        $this->delete('employee_count', $id);

        // Clear agency IDs cache
        $this->delete('agency_ids', 'active');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[AgencyCacheManager] Invalidated all cache for agency {$id}");
        }
    }

    /**
     * Get agency by code from cache
     *
     * @param string $code Agency code
     * @return object|false Agency object or FALSE if not found
     */
    public function getAgencyByCode(string $code): object|false {
        return $this->get('agency_by_code', $code);
    }

    /**
     * Set agency by code in cache
     *
     * @param string $code Agency code
     * @param object $agency Agency data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setAgencyByCode(string $code, object $agency, ?int $expiry = null): bool {
        return $this->set('agency_by_code', $agency, $expiry, $code);
    }

    /**
     * Invalidate ALL agency caches
     *
     * Clears all agency-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllAgencyCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[AgencyCacheManager] Invalidating ALL agency caches");
        }

        return $this->clearAll();
    }
}
