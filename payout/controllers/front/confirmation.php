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
   
    public function postProcess()
    {
        // $notification_json = Tools::file_get_contents('php://input');
        // $notification = json_decode($notification_json);
        // $logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
        // $logger->setFilename(_PS_ROOT_DIR_."/log/debug.log");
        // $logger->logDebug($notification_json);
      
        if (!Tools::getIsset('cart_id')) {
            $this->setTemplate('module:payout/views/templates/front/blank.tpl');
            return;
        }

        if (!($this->module instanceof Payout)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }
        
        $secure_key = Context::getContext()->customer->secure_key;
        $cart_id = Tools::getValue('cart_id');
        
        //For instant payment notification and converting the cart into valid order
        // $cart= Context::getContext()->cart;
        //$cart_id = Context::getContext()->cart->id;
        $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
        $message        = null; // add comment to show in BO.
        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->cookie->id_currency;
        $cart = new Cart((int)$cart_id);
        if ($cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 ||
            !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $customer = new Customer((int)$cart->id_customer);
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
        $order_id = Order::getOrderByCartId((int)$cart->id);
           
           
        if ($order_id && ($secure_key == $customer->secure_key)) {
            /**
             * The order has been placed so we redirect the customer on the confirmation page.
             */
            $module_id = $this->module->id;
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $module_id .
                '&id_order=' . $order_id . '&key=' . $secure_key
            );
            //die();
        } else {
            /*
             * An error occured and is shown on a new page.
             */
            $this->errors[] = $this->module->l(
                'An error occured. Please contact the merchant to have more informations'
            );
            $this->context->smarty->assign([
                'errors' => $this->errors,
            ]);
            
            $this->setTemplate('module:payout/views/templates/front/error.tpl');
        }
    }
}
