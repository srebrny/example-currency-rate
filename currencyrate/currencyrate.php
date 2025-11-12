<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Autoload Composer dependencies
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback error if composer install wasn't run
    if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
        die('Composer autoloader not found. Please run: composer install in ' . __DIR__);
    }
}

use Example\Module\CurrencyRate\Service\NbpApiService;
use Example\Module\CurrencyRate\Repository\ExchangeRateRepository;

class CurrencyRate extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'currencyrate';
        $this->tab = 'payments_gateways';
        $this->version = '0.1.0';
        $this->author = 'Example';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Currency Rate');
        $this->description = $this->l('Shows current currency rates and historical data');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array('min' => '9.0.0', 'max' => '9.99.99');
    }

    public function install()
    {
        // General
        Configuration::updateValue('CURRENCYRATE_ENABLED', true);
        Configuration::updateValue('CURRENCYRATE_DEBUG_MODE', false);

        // Currencies
        Configuration::updateValue('CURRENCYRATE_CURRENCIES_USD', true);
        Configuration::updateValue('CURRENCYRATE_CURRENCIES_EUR', true);
        Configuration::updateValue('CURRENCYRATE_CURRENCIES_GBP', true);

        // API
        Configuration::updateValue('CURRENCYRATE_API_PROVIDER', 'nbp');
        Configuration::updateValue('CURRENCYRATE_API_TIMEOUT', 30);
        Configuration::updateValue('CURRENCYRATE_API_RETRY_ATTEMPTS', 3);

        // Cache & Updates
        //@todo: Implement cache lifetime and auto-update
//        Configuration::updateValue('CURRENCYRATE_CACHE_LIFETIME', 24);
//        Configuration::updateValue('CURRENCYRATE_AUTO_UPDATE', true);
//        Configuration::updateValue('CURRENCYRATE_UPDATE_INTERVAL', 24);
//        Configuration::updateValue('CURRENCYRATE_HISTORY_DAYS', 30);
//        Configuration::updateValue('CURRENCYRATE_FETCH_HISTORY_ON_INSTALL', true);

        // Display
        Configuration::updateValue('CURRENCYRATE_SHOW_ON_PRODUCT', true);
        Configuration::updateValue('CURRENCYRATE_PRODUCT_POSITION', 'displayRightColumnProduct');
        Configuration::updateValue('CURRENCYRATE_SHOW_HISTORY_TABLE', true);
        Configuration::updateValue('CURRENCYRATE_ITEMS_PER_PAGE', 10);
        Configuration::updateValue('CURRENCYRATE_ENABLE_SORTING', true);
        Configuration::updateValue('CURRENCYRATE_DECIMAL_PLACES', 4);

        // Advanced
        Configuration::updateValue('CURRENCYRATE_NOTIFICATION_EMAIL', Configuration::get('PS_SHOP_EMAIL'));
        Configuration::updateValue('CURRENCYRATE_ENABLE_NOTIFICATIONS', true);
        Configuration::updateValue('CURRENCYRATE_ERROR_MESSAGE',
            $this->l('Currency rates are temporarily unavailable. Please try again later.'));
        Configuration::updateValue('CURRENCYRATE_CSS_FRAMEWORK', 'bootstrap');

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayRightColumnProduct') &&
            $this->registerHook('displayNav') &&
            $this->registerHook('displayTop');
    }

    public function uninstall()
    {
        // Delete all configuration values
        Configuration::deleteByName('CURRENCYRATE_ENABLED');
        Configuration::deleteByName('CURRENCYRATE_DEBUG_MODE');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_USD');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_EUR');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_GBP');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_CHF');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_CZK');
        Configuration::deleteByName('CURRENCYRATE_CURRENCIES_SEK');
        Configuration::deleteByName('CURRENCYRATE_API_TIMEOUT');
        Configuration::deleteByName('CURRENCYRATE_API_RETRY_ATTEMPTS');
        Configuration::deleteByName('CURRENCYRATE_CACHE_LIFETIME');
        Configuration::deleteByName('CURRENCYRATE_AUTO_UPDATE');
        Configuration::deleteByName('CURRENCYRATE_UPDATE_INTERVAL');
        Configuration::deleteByName('CURRENCYRATE_HISTORY_DAYS');
        Configuration::deleteByName('CURRENCYRATE_SHOW_ON_PRODUCT');
        Configuration::deleteByName('CURRENCYRATE_ITEMS_PER_PAGE');
        Configuration::deleteByName('CURRENCYRATE_NOTIFICATION_EMAIL');
        Configuration::deleteByName('CURRENCYRATE_DECIMAL_PLACES');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        /**
         * If values have been submitted in the form, process.
         */
        if (Tools::isSubmit('submitCurrencyRateModule')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }

        // Handle manual update button
        if (Tools::isSubmit('submitCurrencyRateUpdate')) {
            // TODO: Implement manual rate update
            $output .= $this->displayConfirmation($this->l('Exchange rates updated successfully.'));
        }

        // Handle fetch history button
        if (Tools::isSubmit('submitCurrencyRateFetchHistory')) {
            // TODO: Implement historical data fetch
            $output .= $this->displayConfirmation($this->l('Historical data fetched successfully.'));
        }

        // Handle clear cache button
        if (Tools::isSubmit('submitCurrencyRateClearCache')) {
            // Clear PrestaShop cache
            Tools::clearCache();
            $output .= $this->displayConfirmation($this->l('Cache cleared successfully.'));
        }

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCurrencyRateModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }


    protected function getConfigForm(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Currency Rate Settings'),
                    'icon' => 'icon-money',
                ],
                'input' => [
                    // === GENERAL SETTINGS ===
                    [
                        'type' => 'html',
                        'name' => 'general_header',
                        'html_content' => '<h3>' . $this->l('General Settings') . '</h3><hr>',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable module'),
                        'name' => 'CURRENCYRATE_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Enable/disable currency rate display on your store'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Enabled')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Disabled')]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Debug Mode'),
                        'name' => 'CURRENCYRATE_DEBUG_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Enable detailed logging for troubleshooting (logs cache hits/misses and API calls)'),
                        'values' => [
                            ['id' => 'debug_on', 'value' => 1, 'label' => $this->l('Enabled')],
                            ['id' => 'debug_off', 'value' => 0, 'label' => $this->l('Disabled')]
                        ],
                    ],
                    // === API SETTINGS ===
                    [
                        'type' => 'html',
                        'name' => 'api_header',
                        'html_content' => '<h3>' . $this->l('API Configuration') . '</h3><hr>',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('API Provider'),
                        'name' => 'CURRENCYRATE_API_PROVIDER',
                        'desc' => $this->l('Select currency exchange rate API provider'),
                        'options' => [
                            'query' => [
                                ['id' => 'nbp', 'name' => 'NBP (Polish National Bank)'],
                                ['id' => 'custom', 'name' => 'Custom'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Timeout'),
                        'name' => 'CURRENCYRATE_API_TIMEOUT',
                        'class' => 'fixed-width-sm',
                        'suffix' => 'seconds',
                        'desc' => $this->l('Maximum time to wait for API response (recommended: 10-30 seconds)'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Retry Attempts'),
                        'name' => 'CURRENCYRATE_API_RETRY_ATTEMPTS',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Number of retry attempts on API failure (recommended: 2-5)'),
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Select currencies to track'),
                        'name' => 'CURRENCYRATE_CURRENCIES',
                        'desc' => $this->l('Choose which currencies to fetch from NBP API (Polish National Bank)'),
                        'values' => [
                            'query' => [
                                ['id' => 'USD', 'name' => 'USD - US Dollar', 'val' => '1'],
                                ['id' => 'EUR', 'name' => 'EUR - Euro', 'val' => '1'],
                                ['id' => 'GBP', 'name' => 'GBP - British Pound', 'val' => '1'],
                                ['id' => 'CHF', 'name' => 'CHF - Swiss Franc', 'val' => '1'],
                                ['id' => 'CZK', 'name' => 'CZK - Czech Koruna', 'val' => '1'],
                                ['id' => 'SEK', 'name' => 'SEK - Swedish Krona', 'val' => '1'],
                                ['id' => 'NOK', 'name' => 'NOK - Norwegian Krone', 'val' => '1'],
                                ['id' => 'DKK', 'name' => 'DKK - Danish Krone', 'val' => '1'],
                                ['id' => 'JPY', 'name' => 'JPY - Japanese Yen', 'val' => '1'],
                                ['id' => 'CAD', 'name' => 'CAD - Canadian Dollar', 'val' => '1'],
                                ['id' => 'AUD', 'name' => 'AUD - Australian Dollar', 'val' => '1'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    // === CACHE & AUTO-UPDATE ===
//                    [
//                        'type' => 'html',
//                        'name' => 'cache_header',
//                        'html_content' => '<h3>' . $this->l('Cache & Automatic Updates') . '</h3><hr>',
//                    ],
//                    [
//                        'type' => 'text',
//                        'label' => $this->l('Cache lifetime'),
//                        'name' => 'CURRENCYRATE_CACHE_LIFETIME',
//                        'class' => 'fixed-width-sm',
//                        'suffix' => 'hours',
//                        'desc' => $this->l('How long to cache API responses (recommended: 12-24 hours)'),
//                    ],
//                    [
//                        'type' => 'switch',
//                        'label' => $this->l('Enable auto-update'),
//                        'name' => 'CURRENCYRATE_AUTO_UPDATE',
//                        'is_bool' => true,
//                        'desc' => $this->l('Automatically update rates via cron job'),
//                        'values' => [
//                            ['id' => 'auto_on', 'value' => 1, 'label' => $this->l('Yes')],
//                            ['id' => 'auto_off', 'value' => 0, 'label' => $this->l('No')]
//                        ],
//                    ],
//                    [
//                        'type' => 'text',
//                        'label' => $this->l('Update interval'),
//                        'name' => 'CURRENCYRATE_UPDATE_INTERVAL',
//                        'class' => 'fixed-width-sm',
//                        'suffix' => 'hours',
//                        'desc' => $this->l('How often to update rates automatically (recommended: 24 hours)'),
//                    ],
//                    [
//                        'type' => 'text',
//                        'label' => $this->l('Historical data period'),
//                        'name' => 'CURRENCYRATE_HISTORY_DAYS',
//                        'class' => 'fixed-width-sm',
//                        'suffix' => 'days',
//                        'desc' => $this->l('Number of days to keep historical exchange rates (required: 30 days)'),
//                    ],
//                    [
//                        'type' => 'switch',
//                        'label' => $this->l('Fetch historical data on install'),
//                        'name' => 'CURRENCYRATE_FETCH_HISTORY_ON_INSTALL',
//                        'is_bool' => true,
//                        'desc' => $this->l('Automatically fetch 30 days of historical data when module is installed'),
//                        'values' => [
//                            ['id' => 'fetch_on', 'value' => 1, 'label' => $this->l('Yes')],
//                            ['id' => 'fetch_off', 'value' => 0, 'label' => $this->l('No')]
//                        ],
//                    ],

                    // === DISPLAY OPTIONS ===
                    [
                        'type' => 'html',
                        'name' => 'display_header',
                        'html_content' => '<h3>' . $this->l('Display Options') . '</h3><hr>',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show on product page'),
                        'name' => 'CURRENCYRATE_SHOW_ON_PRODUCT',
                        'is_bool' => true,
                        'desc' => $this->l('Display product prices in different currencies on product page'),
                        'values' => [
                            ['id' => 'show_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'show_off', 'value' => 0, 'label' => $this->l('No')]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Product page position'),
                        'name' => 'CURRENCYRATE_PRODUCT_POSITION',
                        'desc' => $this->l('Where to display currency rates on product page'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayRightColumnProduct', 'name' => $this->l('Right column')],
                                ['id' => 'displayLeftColumnProduct', 'name' => $this->l('Left column')],
                                ['id' => 'displayFooterProduct', 'name' => $this->l('Product footer')],
                                ['id' => 'displayProductAdditionalInfo', 'name' => $this->l('Additional info')],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show historical table'),
                        'name' => 'CURRENCYRATE_SHOW_HISTORY_TABLE',
                        'is_bool' => true,
                        'desc' => $this->l('Display 30-day historical exchange rates table on dedicated page'),
                        'values' => [
                            ['id' => 'history_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'history_off', 'value' => 0, 'label' => $this->l('No')]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Items per page'),
                        'name' => 'CURRENCYRATE_ITEMS_PER_PAGE',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Number of rows per page in historical data table (pagination)'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable table sorting'),
                        'name' => 'CURRENCYRATE_ENABLE_SORTING',
                        'is_bool' => true,
                        'desc' => $this->l('Allow users to sort historical data table by date, currency, or rate'),
                        'values' => [
                            ['id' => 'sort_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'sort_off', 'value' => 0, 'label' => $this->l('No')]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Decimal places'),
                        'name' => 'CURRENCYRATE_DECIMAL_PLACES',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Number of decimal places to display for exchange rates (recommended: 4)'),
                    ],


                ],
                'submit' => [
                    'title' => $this->l('Save Configuration'),
                    'class' => 'btn btn-default pull-right',
                ],
                'buttons' => [
                    [
                        'type' => 'submit',
                        'title' => $this->l('Update Rates Now'),
                        'icon' => 'process-icon-refresh',
                        'name' => 'submitCurrencyRateUpdate',
                        'class' => 'btn btn-default pull-right',
                    ],
                    [
                        'type' => 'submit',
                        'title' => $this->l('Fetch Historical Data'),
                        'icon' => 'process-icon-download',
                        'name' => 'submitCurrencyRateFetchHistory',
                        'class' => 'btn btn-default pull-right',
                    ],
                    [
                        'type' => 'submit',
                        'title' => $this->l('Clear Cache'),
                        'icon' => 'process-icon-eraser',
                        'name' => 'submitCurrencyRateClearCache',
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues(): array
    {
        return array(
            // General
            'CURRENCYRATE_ENABLED' => Configuration::get('CURRENCYRATE_ENABLED', true),
            'CURRENCYRATE_DEBUG_MODE' => Configuration::get('CURRENCYRATE_DEBUG_MODE', false),

            // Currencies
            'CURRENCYRATE_CURRENCIES_USD' => Configuration::get('CURRENCYRATE_CURRENCIES_USD', true),
            'CURRENCYRATE_CURRENCIES_EUR' => Configuration::get('CURRENCYRATE_CURRENCIES_EUR', true),
            'CURRENCYRATE_CURRENCIES_GBP' => Configuration::get('CURRENCYRATE_CURRENCIES_GBP', true),
            'CURRENCYRATE_CURRENCIES_CHF' => Configuration::get('CURRENCYRATE_CURRENCIES_CHF', false),
            'CURRENCYRATE_CURRENCIES_CZK' => Configuration::get('CURRENCYRATE_CURRENCIES_CZK', false),
            'CURRENCYRATE_CURRENCIES_SEK' => Configuration::get('CURRENCYRATE_CURRENCIES_SEK', false),

            // API
            'CURRENCYRATE_API_PROVIDER' => Configuration::get('CURRENCYRATE_API_PROVIDER', 'nbp'),
            'CURRENCYRATE_API_TIMEOUT' => Configuration::get('CURRENCYRATE_API_TIMEOUT', 30),
            'CURRENCYRATE_API_RETRY_ATTEMPTS' => Configuration::get('CURRENCYRATE_API_RETRY_ATTEMPTS', 3),


            // Cache & Updates
            //@todo: Implement cache lifetime and auto-update
//            'CURRENCYRATE_CACHE_LIFETIME' => Configuration::get('CURRENCYRATE_CACHE_LIFETIME', 24),
//            'CURRENCYRATE_AUTO_UPDATE' => Configuration::get('CURRENCYRATE_AUTO_UPDATE', true),
//            'CURRENCYRATE_UPDATE_INTERVAL' => Configuration::get('CURRENCYRATE_UPDATE_INTERVAL', 24),
//            'CURRENCYRATE_HISTORY_DAYS' => Configuration::get('CURRENCYRATE_HISTORY_DAYS', 30),

            // Display
            'CURRENCYRATE_SHOW_ON_PRODUCT' => Configuration::get('CURRENCYRATE_SHOW_ON_PRODUCT', true),
            'CURRENCYRATE_ITEMS_PER_PAGE' => Configuration::get('CURRENCYRATE_ITEMS_PER_PAGE', 10),

            // Advanced
            'CURRENCYRATE_NOTIFICATION_EMAIL' => Configuration::get('CURRENCYRATE_NOTIFICATION_EMAIL',
                Configuration::get('PS_SHOP_EMAIL')),
            'CURRENCYRATE_DECIMAL_PLACES' => Configuration::get('CURRENCYRATE_DECIMAL_PLACES', 4),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess(): void
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        // Show success message
        $this->context->controller->confirmations[] = $this->l('Settings updated successfully.');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        // Register CSS
        $this->context->controller->registerStylesheet(
            'module-currencyrate-front-css',
            'modules/' . $this->name . '/views/css/front.css',
            [
                'media' => 'all',
                'priority' => 200,
            ]
        );

        // Register JS
        $this->context->controller->registerJavascript(
            'module-currencyrate-front-js',
            'modules/' . $this->name . '/views/js/front.js',
            [
                'position' => 'bottom',
                'priority' => 200,
            ]
        );
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookDisplayRightColumnProduct()
    {
        /* Place your code here. */
    }

    /**
     * Add link to main menu
     */
    public function hookDisplayTop($params)
    {

        if (!Configuration::get('CURRENCYRATE_ENABLED')) {
            return '';
        }

        $link = $this->context->link->getModuleLink('currencyrate', 'display');

        $this->context->smarty->assign([
            'currencyrate_link' => $link,
            'currencyrate_title' => $this->l('Currency Rates'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/nav-link.tpl');
    }
}
