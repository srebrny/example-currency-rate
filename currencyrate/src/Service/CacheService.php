<?php declare(strict_types=1);
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 */

namespace Example\Module\CurrencyRate\Service;

use Cache;
use Configuration;

/**
 * Service for caching exchange rates using PrestaShop Cache
 * Supports Memcached, Redis, APCu, etc. depending on PrestaShop configuration
 */
class CacheService
{
    private const CACHE_KEY_PREFIX = 'currencyrate_';
    private const DEFAULT_TTL = 86400; // 24 hours

    /**
     * Get cached data
     */
    public function get(string $key)
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;

        if (Cache::isStored($cacheKey)) {
            $data = Cache::retrieve($cacheKey);

            // Log cache hit for debugging
            if (Configuration::get('CURRENCYRATE_DEBUG_MODE')) {
                \PrestaShopLogger::addLog(
                    sprintf('Cache HIT: %s', $cacheKey),
                    1
                );
            }

            return $data;
        }

        // Log cache miss
        if (Configuration::get('CURRENCYRATE_DEBUG_MODE')) {
            \PrestaShopLogger::addLog(
                sprintf('Cache MISS: %s', $cacheKey),
                1
            );
        }

        return null;
    }

    /**
     * Store data in cache
     */
    public function set(string $key, $data, ?int $ttl = null): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;
        Cache::store($cacheKey, $data);

        if (Configuration::get('CURRENCYRATE_DEBUG_MODE')) {
            \PrestaShopLogger::addLog(
                sprintf('Cache SET: %s (TTL: %d)', $cacheKey, $ttl ?? self::DEFAULT_TTL),
                1
            );
        }
    }

    /**
     * Delete cached data
     */
    public function delete(string $key): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;
        Cache::delete($cacheKey);

        if (Configuration::get('CURRENCYRATE_DEBUG_MODE')) {
            \PrestaShopLogger::addLog(
                sprintf('Cache DELETE: %s', $cacheKey),
                1
            );
        }
    }

    /**
     * Clear all cache for this module
     */
    public function clearAll(): void
    {
        Cache::clean(self::CACHE_KEY_PREFIX . '*');

        \PrestaShopLogger::addLog(
            'Cache CLEAR ALL: currencyrate_*',
            1
        );
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;
        return Cache::isStored($cacheKey);
    }

    /**
     * Get cache statistics (if available)
     */
    public function getStats(): array
    {
        $stats = [
            'enabled' => (bool) Configuration::get('PS_CACHE_ENABLE'),
            'system' => Configuration::get('PS_CACHING_SYSTEM', 'CacheFs'),
        ];

        // Try to get Memcached stats if available
        if ($stats['system'] === 'CacheMemcache' && class_exists('Memcached')) {
            try {
                $memcached = new \Memcached();
                $memcached->addServer('memcached', 11211);
                $stats['memcached'] = $memcached->getStats();
            } catch (\Exception $e) {
                $stats['error'] = $e->getMessage();
            }
        }

        return $stats;
    }
}
