{*
* 2007-2021 PrestaShop
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
*	@author PrestaShop SA <contact@prestashop.com>
*	@copyright	2007-2021 PrestaShop SA
*	@license		http://opensource.org/licenses/afl-3.0.php	Academic Free License (AFL 3.0)
*	International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel product-tab" style="padding:10px; float:left;width:100%; display:block;margin-bottom: .75rem;border: 1px solid #e5e5e5;border-radius: .25rem;">

  <div class="form-group form-group-products form-group-stripepro">
    <input type="hidden" name="payout_submit" value="1" />
   		<label class="control-label col-lg-4" for="simple_product" style="float:left;color: #6c868e;">
        <span title="{l s='By enabling this, payout will add the selected subscription on this product order.' mod='payout'}" class="label-tooltip" data-toggle="tooltip" title="">
			 {l s='Enable Recurring Payments' mod='payout'}:
             </span>
		</label>
		<div class="col-lg-8" style="float:left;">
			 <span class="switch prestashop-switch fixed-width-lg">
				<input name="subscription" id="subscription_on" value="1" {if $subscription_info.subscription}checked="checked"{/if} type="radio">
				<label for="subscription_on" class="radioCheck">
					Yes
				</label>
				<input name="subscription" id="subscription_off" value="0" {if !$subscription_info.subscription}checked="checked"{/if} type="radio">
				<label for="subscription_off" class="radioCheck">
					No
				</label>
				<a class="slide-button btn"></a>
			</span>
		</div>
	</div>
  
    
     <div class="form-group pull-left" style="width:100%;;float:left;">
		<label class="control-label col-lg-4" for="frequency" style="float:left;color: #6c868e;">
        <span title="{l s='' mod='payout'}" class="label-tooltip" data-toggle="tooltip" title="">
			 {l s='Default Recurring plan' mod='payout'}:
             </span>
		</label>
		<div class="col-lg-8" style="float:left;">
			<select name="frequency" id="frequency" class="form-control">
                            <option value="">{l s='Please select...' mod='payout'}</option>
                            <option {if $subscription_info.frequency eq weekly} selected {/if} value="weekly">Weekly</option>
                            <option {if $subscription_info.frequency eq monthly} selected {/if} value="monthly">Monthly</option>
                            <option {if $subscription_info.frequency eq yearly} selected {/if} value="yearly">Yearly</option>
               
		</select>
		</div>
    </div>
    <div class="form-group pull-left" style="width:100%;float:left;">
    <div class="col-lg-4" style="float:left;"></div>
    <div class="col-lg-8" style="float:left;">
     
        </div>
        </div>
</div>
