
{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1>{l s='Currency Exchange Rates' mod='currencyrate'}</h1>
{/block}

{block name='page_content'}
    <div class="currencyrate-page">

        {* Current Rates Section *}
        <section class="currencyrate-current mb-5">
            <h2 class="h3 mb-3">{l s='Current Exchange Rates' mod='currencyrate'}</h2>
            <p class="text-muted mb-4">{l s='All rates are relative to PLN (Polish Złoty) - Updated from NBP API' mod='currencyrate'}</p>

            {if $current_rates && $current_rates|@count > 0}
                <div class="row">
                    {foreach from=$current_rates item=rate}
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h3 class="card-title h4">{$rate.currency}</h3>
                                    <p class="h2 text-primary my-3">{$rate.rate}</p>
                                    <small class="text-muted">
                                        {l s='PLN per' mod='currencyrate'} {$rate.currency}<br>
                                        <small>{$rate.formatted_date}</small>
                                    </small>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            {else}
                <div class="alert alert-warning" role="alert">
                    <i class="material-icons">warning</i>
                    {l s='No current rates available. Please check your configuration or try refreshing.' mod='currencyrate'}
                </div>
            {/if}
        </section>

        <hr class="my-5">

        {* Historical Rates Section *}
        <section class="currencyrate-historical">
            <h2 class="h3 mb-3">{l s='Historical Exchange Rates (Last 30 Days)' mod='currencyrate'}</h2>

            {if $historical_rates && $historical_rates|@count > 0}
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="currencyrate-table">
                        <thead class="thead-light">
                        <tr>
                            <th class="sortable" data-sort="date">
                                {l s='Date' mod='currencyrate'}
                                <i class="material-icons sort-icon">unfold_more</i>
                            </th>
                            <th class="sortable" data-sort="currency">
                                {l s='Currency' mod='currencyrate'}
                                <i class="material-icons sort-icon">unfold_more</i>
                            </th>
                            <th class="sortable" data-sort="rate">
                                {l s='Exchange Rate' mod='currencyrate'}
                                <i class="material-icons sort-icon">unfold_more</i>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$historical_rates item=rate}
                            <tr>
                                <td>{$rate.formatted_date}</td>
                                <td><strong>{$rate.currency}</strong></td>
                                <td>{$rate.rate} PLN</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>

                {* Pagination *}
                {if $total_pages > 1}
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            {* Previous button *}
                            {if $current_page > 1}
                                <li class="page-item">
                                    <a class="page-link" href="{$base_url}&page={$current_page - 1}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">{l s='Previous' mod='currencyrate'}</span>
                                    </a>
                                </li>
                            {else}
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            {/if}

                            {* Page numbers *}
                            {for $i=1 to $total_pages}
                                {if $i == $current_page}
                                    <li class="page-item active">
                                        <span class="page-link">{$i}</span>
                                    </li>
                                {else}
                                    <li class="page-item">
                                        <a class="page-link" href="{$base_url}&page={$i}">{$i}</a>
                                    </li>
                                {/if}
                            {/for}

                            {* Next button *}
                            {if $current_page < $total_pages}
                                <li class="page-item">
                                    <a class="page-link" href="{$base_url}&page={$current_page + 1}" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">{l s='Next' mod='currencyrate'}</span>
                                    </a>
                                </li>
                            {else}
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            {/if}
                        </ul>
                    </nav>

                    {* Pagination info *}
                    <p class="text-center text-muted">
                        {l s='Showing page' mod='currencyrate'} {$current_page} {l s='of' mod='currencyrate'} {$total_pages}
                        <br>
                        <small>({l s='Total records:' mod='currencyrate'} {$total_items})</small>
                    </p>
                {/if}
            {else}
                <div class="alert alert-info" role="alert">
                    <i class="material-icons">info</i>
                    {l s='No historical rates available. Please check your configuration or try refreshing.' mod='currencyrate'}
                </div>
            {/if}
        </section>

        {* Debug info (only in dev mode) *}
        {if isset($smarty.const._PS_MODE_DEV_) && $smarty.const._PS_MODE_DEV_}
            <div class="alert alert-secondary mt-5">
                <strong>Debug Info:</strong>
                <ul class="mb-0">
                    <li>Current rates count: {$current_rates|@count}</li>
                    <li>Historical rates count: {$historical_rates|@count}</li>
                    <li>Total items: {$total_items}</li>
                    <li>Items per page: {$items_per_page}</li>
                    <li>Current page: {$current_page}/{$total_pages}</li>
                    <li>Module dir: {$module_dir}</li>
                </ul>
            </div>
        {/if}
    </div>
{/block}

{block name='page_footer'}
    <div class="text-center mt-5">
        <p class="text-muted">
            <i class="material-icons" style="vertical-align: middle;">info_outline</i>
            {l s='Exchange rates are updated from NBP API (Polish National Bank)' mod='currencyrate'}
        </p>
        <p class="text-muted">
            <small>
                {l s='Data is cached to optimize performance. Click "Refresh Rates" to get the latest data.' mod='currencyrate'}
            </small>
        </p>
    </div>
{/block}
