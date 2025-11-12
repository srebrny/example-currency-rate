<?php declare(strict_types=1);
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 */
namespace Example\Module\CurrencyRate\Service;

use Example\Module\CurrencyRate\DTO\ExchangeRate;
use Example\Module\CurrencyRate\Exception\ApiException;
use DateTimeImmutable;
use PrestaShopLogger;

/**
 * NBP API Service using native PHP cURL
 */
class NbpApiService
{
    private const API_BASE_URL = 'https://api.nbp.pl/api';
    private const DEFAULT_TABLE = 'A';
    private const DEFAULT_FORMAT = 'json';

    public function __construct(private readonly int $timeout = 30, private readonly int $maxRetries = 3)
    {
    }

    /**
     * Get current exchange rate
     */
    public function getCurrentRate(string $currency): ExchangeRate
    {
        $currency = strtoupper($currency);
        $url = sprintf(
            '%s/exchangerates/rates/%s/%s/?format=%s',
            self::API_BASE_URL,
            self::DEFAULT_TABLE,
            $currency,
            self::DEFAULT_FORMAT
        );


        $data = $this->makeRequest($url);

        if (!isset($data['rates'][0])) {
            throw new ApiException(
                sprintf('No rate data found for currency: %s. Response: %s', $currency, json_encode($data))
            );
        }

        $rateData = $data['rates'][0];

        return new ExchangeRate(
            $data['code'],
            (float) $rateData['mid'],
            new DateTimeImmutable($rateData['effectiveDate']),
            $data['table'],
            $rateData['no']
        );
    }

    /**
     * Get last N days of rates
     */
    public function getLastNDaysRates(string $currency, int $days = 30): array
    {
        $currency = strtoupper($currency);
        $url = sprintf(
            '%s/exchangerates/rates/%s/%s/last/%d/?format=%s',
            self::API_BASE_URL,
            self::DEFAULT_TABLE,
            $currency,
            min($days, 367),
            self::DEFAULT_FORMAT
        );

        $data = $this->makeRequest($url);

        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new ApiException(
                sprintf('No rate data found for currency: %s', $currency)
            );
        }

        $rates = [];
        foreach ($data['rates'] as $rateData) {
            $rates[] = new ExchangeRate(
                $data['code'],
                (float) $rateData['mid'],
                new DateTimeImmutable($rateData['effectiveDate']),
                $data['table'],
                $rateData['no']
            );
        }

        return $rates;
    }

    /**
     * Make HTTP request using cURL with retry logic
     */
    private function makeRequest(string $url): array
    {
        $lastException = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {

            try {
                $ch = curl_init($url);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_SSL_VERIFYPEER => true, // Zmieniono na false dla testów
                    CURLOPT_SSL_VERIFYHOST => 2,      // Zmieniono na 0 dla testów
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                    ],
                    CURLOPT_VERBOSE => true, // Debug
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                $info = curl_getinfo($ch);

                curl_close($ch);

                // Log dla debug
                PrestaShopLogger::addLog(
                    sprintf(
                        'NBP API Request: URL=%s, HTTP_CODE=%d, Response_Length=%d',
                        $url,
                        $httpCode,
                        strlen($response ?: '')
                    ),
                    1
                );

                if ($response === false) {
                    throw new ApiException(sprintf('cURL error: %s', $error));
                }

                if ($httpCode === 404) {
                    throw new ApiException(
                        'Currency or date range not found in NBP database',
                        404
                    );
                }

                if ($httpCode !== 200) {
                    PrestaShopLogger::addLog(
                        sprintf('NBP API Error Response: %s', substr($response, 0, 500)),
                        3
                    );
                    throw new ApiException(
                        sprintf('NBP API returned status code: %d, Response: %s', $httpCode, substr($response, 0, 200)),
                        $httpCode
                    );
                }

                $data = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    PrestaShopLogger::addLog(
                        sprintf('JSON Decode Error. Response: %s', substr($response, 0, 500)),
                        3
                    );
                    throw new ApiException(
                        sprintf('Failed to decode JSON response: %s', json_last_error_msg())
                    );
                }

                // Log sukcesu
                PrestaShopLogger::addLog(
                    sprintf('NBP API Success: Got %d items', isset($data['rates']) ? count($data['rates']) : 0),
                    1
                );

                return $data;

            } catch (ApiException $e) {
                $lastException = $e;

                PrestaShopLogger::addLog(
                    sprintf(
                        'NBP API: Request failed (attempt %d/%d): %s',
                        $attempt,
                        $this->maxRetries,
                        $e->getMessage()
                    ),
                    3
                );

                if ($attempt < $this->maxRetries) {
                    sleep(2 ** ($attempt - 1)); // Exponential backoff
                }
            }
        }

        throw new ApiException(
            sprintf(
                'NBP API: Max retries (%d) exceeded. Last error: %s',
                $this->maxRetries,
                $lastException ? $lastException->getMessage() : 'Unknown error'
            ),
            0,
            $lastException
        );
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $this->getCurrentRate('USD');
            return true;
        } catch (ApiException $e) {
            PrestaShopLogger::addLog(
                sprintf('NBP API: Connection test failed: %s', $e->getMessage()),
                3
            );
            return false;
        }
    }
}
