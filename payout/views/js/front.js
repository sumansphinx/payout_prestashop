/**
* 2007-2022 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
$(window).ready(function(){
    $(".payout_subscription_label").click(function(){
        var id_subscription = $(this).attr("data-id");
        var action = $(this).attr("data-action");
        var frequency = $(this).attr("data-frequency");
        $.ajax({
            data: 'POST',
            dataType: 'JSON',
            url: payout_ajax_front_controller,
            data: {
                ajax: true,
                action: action,
                id: id_subscription,
                frequency: frequency
            },
            success: function (data) {
                if(data){
                    window.location.href = window.location.href;
                }else{
                    console.log("There is some issue.");
                }
            },
            error: function (err) {
                console.log(err);
            }
        });  
    });
});