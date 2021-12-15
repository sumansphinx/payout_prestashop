<?php
/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class PayoutConfirmationModuleFrontController extends ModuleFrontController
{
    
    public function printr($data)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
    public function postProcess()
    {
        if (!($this->module instanceof Payout)) {
                Tools::redirect('index.php?controller=order&step=1');
                return;
        }
            
            
        /** For Webhook url */
            
        if (Tools::getIsset('cart_id')) {
            $cart_id = Tools::getValue('cart_id');
            
            $order_id = Order::getOrderByCartId((int)$cart_id);
            
            $cart = new Cart((int)$cart_id);
            $customer = new Customer((int)$cart->id_customer);
            $secure_key = Context::getContext()->customer->secure_key;
            if ($order_id && ($secure_key == $customer->secure_key)) {
                // The order has been placed so we redirect the customer on the confirmation page.
                $module_id = $this->module->id;
                Tools::redirect(
                    'index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $module_id .
                    '&id_order=' . $order_id . '&key=' . $secure_key
                );
                die();
            } else {
                $cart = new Cart((int)$cart_id);
                //echo $secure_key = Context::getContext()->customer->secure_key;
                //echo $secure_key = Context::getContext()->customer->secure_key;
                $customer = new Customer((int)$cart->id_customer);
                $secure_key = $customer->secure_key;
                $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
                $message        = null; // add comment to show in BO.
                $module_name = $this->module->displayName;
            
                $currency_id = $cart->id_currency;
                if ($cart->id_customer == 0 ||
                $cart->id_address_delivery == 0 ||
                $cart->id_address_invoice == 0 ||
                !$this->module->active) {
                    Tools::redirect('index.php?controller=order&step=1');
                }
    
            
                if (!Validate::isLoadedObject($customer)) {
                    Tools::redirect('index.php?controller=order&step=1');
                }
        
    
                $this->module->validateOrder(
                    $cart_id,
                    $payment_status,
                    $cart->getOrderTotal(),
                    $module_name,
                    $message,
                    array(),
                    $currency_id,
                    false,
                    $secure_key
                );
                /**
                * If the order has been validated we try to retrieve it
                */
                $order_id = Order::getOrderByCartId((int)$cart_id);
                if ($order_id && ($secure_key == $customer->secure_key)) {
                    // The order has been placed so we redirect the customer on the confirmation page.
                    $module_id = $this->module->id;
                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $module_id .
                        '&id_order=' . $order_id . '&key=' . $secure_key
                    );
                    die();
                } else {
                    // An error occured and is shown on a new page.
                    $this->errors[] = $this->module->l(
                        'An error occured. Please contact the merchant to have more informations'
                    );
                    $this->context->smarty->assign([
                        'errors' => $this->errors,
                    ]);

                    $this->setTemplate('module:payout/views/templates/front/error.tpl');
                    return;
                }
            }
        }
    }
}
