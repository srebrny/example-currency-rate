<?php declare(strict_types=1);
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 */

namespace Example\Module\CurrencyRate\Repository;

use Example\Module\CurrencyRate\Service\NbpApiService;
use Example\Module\CurrencyRate\Service\CacheService;
use Example\Module\CurrencyRate\DTO\ExchangeRate;
use Example\Module\CurrencyRate\Exception\ApiException;
use PrestaShopLogger;
use Configuration;

/**
 * Repository using PSR-16 Cache and PSR-18 HTTP Client
 */
class ExchangeRateRepository
{
    private NbpApiService $apiService;
    private CacheService $cache;

    public function __construct(
        ?NbpApiService $apiService = null,
        ?CacheService $cache = null
    ) {
        $timeout = (int) Configuration::get('CURRENCYRATE_API_TIMEOUT', 30);
        $retries = (int) Configuration::get('CURRENCYRATE_API_RETRY_ATTEMPTS', 3);

        $this->apiService = $apiService ?? new NbpApiService($timeout, $retries);
        $this->cache = $cache ?? new CacheService();
    }

    /**
     * Get current rate with PSR-16 caching
     */
    public function getCurrentRate(string $currency, bool $forceRefresh = false): ?ExchangeRate
    {
        $cacheKey =  'current_' . strtoupper($currency);
        if (!$forceRefresh && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            $rate = $this->apiService->getCurrentRate($currency);
            $this->cache->set($cacheKey, $rate);
            return $rate;
        } catch (ApiException $e) {
            PrestaShopLogger::addLog(
                sprintf('Failed to fetch current rate for %s: %s', $currency, $e->getMessage()),
                3
            );
            return null;
        }
    }

    /**
     * Get multiple current rates
     */
    public function getMultipleCurrentRates(array $currencies, bool $forceRefresh = false): array
    {
        $cacheKey = 'multiple_' . implode('_', $currencies);
        if (!$forceRefresh && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }


        $rates = [];
        foreach ($currencies as $currency) {
            $rate = $this->getCurrentRate($currency, $forceRefresh);
            if ($rate !== null) {
                $rates[$currency] = $rate;
            }
        }

        if (!empty($rates)) {
            $this->cache->set($cacheKey, $rates);
        }

        return $rates;
    }

    /**
     * Get historical rates with PSR-16 caching
     */
    public function getHistoricalRates(string $currency, int $days = 30, bool $forceRefresh = false): array
    {
        $cacheKey = sprintf('historical_%s_%d', strtoupper($currency), $days);

        if (!$forceRefresh && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $rates = $this->apiService->getLastNDaysRates($currency, $days);
            $this->cache->set($cacheKey, $rates);
            return $rates;
        } catch (ApiException $e) {
            PrestaShopLogger::addLog(
                sprintf('Failed to fetch historical rates for %s: %s', $currency, $e->getMessage()),
                3
            );
            return [];
        }
    }

    /**
     * Get multiple historical rates
     */
    public function getMultipleHistoricalRates(array $currencies, int $days = 30, bool $forceRefresh = false): array
    {
        $result = [];

        foreach ($currencies as $currency) {
            $rates = $this->getHistoricalRates($currency, $days, $forceRefresh);
            if (!empty($rates)) {
                $result[$currency] = $rates;
            }
        }

        return $result;
    }

    /**
     * Clear cache using PSR-16 interface
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        return $this->apiService->testConnection();
    }
}
