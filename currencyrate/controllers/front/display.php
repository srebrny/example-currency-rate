<?php declare(strict_types=1);
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Example\Module\CurrencyRate\Repository\ExchangeRateRepository;
use Example\Module\CurrencyRate\DTO\ExchangeRate;

/**
 * Front controller for currency rates display page
 */
class CurrencyRateDisplayModuleFrontController extends ModuleFrontController
{
    private ExchangeRateRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ExchangeRateRepository();
    }

    public function initContent(): void
    {
        parent::initContent();

        // Get enabled currencies
        $enabledCurrencies = $this->getEnabledCurrencies();

        // Check if force refresh is requested
        $forceRefresh = (bool) Tools::getValue('refresh', true);

        // Get current rates from NBP API
        $currentRates = $this->getCurrentRatesFromApi($enabledCurrencies, $forceRefresh);

        // Get historical rates from NBP API
        $historicalRates = $this->getHistoricalRatesFromApi($enabledCurrencies, $forceRefresh);

        // Pagination
        $itemsPerPage = (int) Configuration::get('CURRENCYRATE_ITEMS_PER_PAGE', 10);
        $page = max(1, (int) Tools::getValue('page', 1));
        $totalItems = count($historicalRates);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;
        $paginatedRates = array_slice($historicalRates, $offset, $itemsPerPage);

        // Assign to template
        $this->context->smarty->assign([
            'current_rates' => $currentRates,
            'historical_rates' => $paginatedRates,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'items_per_page' => $itemsPerPage,
            'enable_sorting' => Configuration::get('CURRENCYRATE_ENABLE_SORTING', true),
            'decimal_places' => Configuration::get('CURRENCYRATE_DECIMAL_PLACES', 4),
            'module_dir' => $this->module->getPathUri(),
            'refresh_url' => $this->context->link->getModuleLink('currencyrate', 'display', ['refresh' => 1]),
        ]);

        $this->setTemplate('module:currencyrate/views/templates/front/display.tpl');
    }

    /**
     * Get enabled currencies from configuration
     */
    private function getEnabledCurrencies(): array
    {
        $currencies = [];
        $availableCurrencies = ['USD', 'EUR', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK', 'JPY', 'CAD', 'AUD'];

        foreach ($availableCurrencies as $currency) {
            if (Configuration::get('CURRENCYRATE_CURRENCIES_' . $currency)) {
                $currencies[] = $currency;
            }
        }

        return !empty($currencies) ? $currencies : ['USD', 'EUR', 'GBP'];
    }

    /**
     * Get current rates from NBP API via Repository
     */
    private function getCurrentRatesFromApi(array $currencies, bool $forceRefresh = false): array
    {

        $rates = $this->repository->getMultipleCurrentRates($currencies, $forceRefresh);
        return array_map(function (ExchangeRate $rate) {
            return [
                'currency' => $rate->getCurrency(),
                'rate' => number_format(
                    $rate->getRate(),
                    (int) Configuration::get('CURRENCYRATE_DECIMAL_PLACES', 4),
                    '.',
                    ''
                ),
                'date' => $rate->getDate()->format('Y-m-d'),
                'formatted_date' => $rate->getDate()->format('d.m.Y'),
            ];
        }, $rates);
    }

    /**
     * Get historical rates from NBP API via Repository
     */
    private function getHistoricalRatesFromApi(array $currencies, bool $forceRefresh = false): array
    {
        $days = (int) Configuration::get('CURRENCYRATE_HISTORY_DAYS', 30);
        $allRates = $this->repository->getMultipleHistoricalRates($currencies, $days, $forceRefresh);

        $formatted = [];
        foreach ($allRates as $currency => $rates) {
            /** @var ExchangeRate $rate */
            foreach ($rates as $rate) {
                $formatted[] = [
                    'currency' => $rate->getCurrency(),
                    'rate' => number_format(
                        $rate->getRate(),
                        (int) Configuration::get('CURRENCYRATE_DECIMAL_PLACES', 4),
                        '.',
                        ''
                    ),
                    'date' => $rate->getDate()->format('Y-m-d'),
                    'formatted_date' => $rate->getDate()->format('d.m.Y'),
                ];
            }
        }

        // Sort by date DESC
        usort($formatted, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $formatted;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = [
            'title' => $this->module->l('Currency Rates'),
            'url' => $this->context->link->getModuleLink('currencyrate', 'display'),
        ];

        return $breadcrumb;
    }
}
