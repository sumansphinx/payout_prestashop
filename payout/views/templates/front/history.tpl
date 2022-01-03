{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
 
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Product Subscription history' mod='payout'}
{/block}

{block name='page_content'}
  <h6>{l s='Here are the subscription product you\'ve placed since your account was created.' mod='payout'}</h6>
  <span id="message"></span>
  {if $products}
    <table class="table table-striped table-bordered table-labeled hidden-sm-down">
      <thead class="thead-default">
        <tr>
          <th>{l s='Name' mod='payout'}</th>
          <th>{l s='Next Order Date' mod='payout'}</th>
          <th>{l s='Last Order Date' mod='payout'}</th>
          <th>{l s='Frequency' mod='payout'}</th>
          <th class="hidden-md-down">{l s='Status' mod='payout'}</th>
          <th>{l s='Action' mod='payout'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$products item=product}
          <tr>
            <th scope="row">{$product.name|escape:'html':'UTF-8'}</th>
            <td>{$product.next_recurring_date|escape:'html':'UTF-8'}</td>
            <td class="text-xs-right">{if $product.last_recurring_date} {$product.last_recurring_date|escape:'html':'UTF-8'} {else} -{/if}</td>
            <td class="text-xs-right">
              {ucwords($product.frequency)|escape:'html':'UTF-8'}
            </td>
            <td class="text-xs-right">
              {ucwords($product.status)|escape:'html':'UTF-8'}
            </td>
            <td class="text-sm-center order-actions">
              {if $product.status == 'stopped'}
                <label class="btn btn-primary payout_subscription_label" data-action="enableSubscription" data-frequency="{$product.frequency|escape:'html':'UTF-8'}" data-id="{$product.id_payout_subscription_product|escape:'html':'UTF-8'}" title="Enable">{l s='Start Subscription?' mod='payout'}
                </label>
              {else}
                <label class="btn btn-warning payout_subscription_label" data-action="disableSubscription" data-frequency="{$product.frequency|escape:'html':'UTF-8'}" data-id="{$product.id_payout_subscription_product|escape:'html':'UTF-8'}" title="Disable">{l s='Stop Subscription?' mod='payout'}
                </label>
              {/if}
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}
  {literal}
  <script type="text/javascript">
      var payout_ajax_front_controller = "{/literal}{$payout_ajax_front_controller|escape:'htmlall':'UTF-8'}{literal}";
      payout_ajax_front_controller = payout_ajax_front_controller.replace(/\amp;/g,'');
  </script>
  {/literal}
{/block}
