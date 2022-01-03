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

class PayoutValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        if (!($this->module instanceof Payout)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        /*
         * Get Order data .
         */
        $context = Context::getContext();
        
        $customer    = $context->customer;
        $cart_id     = $cart->id;
        $customer_id = $customer->id;

        
        /*
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart     = new Cart((int)$cart_id);
        Context::getContext()->customer = new Customer((int)$customer_id);
        Context::getContext()->currency = new Currency((int)Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int)Context::getContext()->customer->id_lang);

        $this->getStandardCheckoutFormFields($context);
    }

    /**
     * This is where we compile data posted by the form to Payout
     * @return array
     */
    public function getStandardCheckoutFormFields($context)
    {
        
        $clientId          = Configuration::get('PAYOUT_CLIENT_ID');
        $secret            = Configuration::get('PAYOUT_SECRET');
        $sandbox           = Configuration::get('PAYOUT_MODE');
        $productAttributes = array();

        $config = array(
            'client_id'     => $clientId,
            'client_secret' => $secret,
            'sandbox'       => $sandbox
        );

        require_once(dirname(__FILE__) . '/../../classes/init.php');

        $payout = new Client($config);

        $customer = $context->customer;
        $cart     = $context->cart;
        $currency = $this->context->currency;
        $total    = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $url = $this->context->link->getModuleLink('payout', 'confirmation', ['cart_id' => $cart->id]);
       /********** format billing and shipping Address **********/
        $external_id =  $cart->id.'-'.time();
        $delivery_address      = $cart->id_address_delivery;
        $invoice_address       = $cart->id_address_invoice;
        $dAddress              = new Address($delivery_address);
        $iAddress              = new Address($invoice_address);
        $billing_country_code  = new Country($iAddress->id_country);
        $bcc                   = $billing_country_code->iso_code;
        $shipping_country_code = new Country($dAddress->id_country);
        $scc                   = $shipping_country_code->iso_code;
        $billing_address       = array(
            'name'           => $iAddress->firstname . ' ' . $iAddress->lastname,
            'address_line_1' => $iAddress->address1,
            'address_line_2' => $iAddress->address2,
            'postal_code'    => $iAddress->postcode,
            'country_code'   => $bcc,
            'city'           => $iAddress->city
        );

        $shipping_address = array(
            'name'           => $dAddress->firstname . ' ' . $dAddress->lastname,
            'address_line_1' => $dAddress->address1,
            'address_line_2' => $dAddress->address2,
            'postal_code'    => $dAddress->postcode,
            'country_code'   => $scc,
            'city'           => $dAddress->city
        );

        $products = $cart->getProducts(true);
        $subscription_flag = 0;
        foreach ($products as $product) {
            $sqls = 'select subscription, frequency from ' . _DB_PREFIX_ . 'product where 
            id_product='.$product["id_product"];
             $validate_subscription_data = Db::getInstance()->ExecuteS($sqls);
            if ($validate_subscription_data[0]['subscription'] != 0) {
                $subscription_flag = 1;
            }
            $productAttributes[] = array(
                'name'       => $product['name'],
                'unit_price' => round($product['price_with_reduction'], 2),
                'quantity'   => $product['cart_quantity'],

            );
            
            if ($subscription_flag == 1) {
                $nextRecurringDate = $this->module->getNextRecurringDate($validate_subscription_data[0]['frequency']);
                $to_store_in_subscription = array();
                $to_store_in_subscription['id_customer'] = $customer->id;
                $to_store_in_subscription['id_product'] = $product['id_product'];
                $to_store_in_subscription['id_product_attribute'] = $product['id_product_attribute'];
                $to_store_in_subscription['quantity'] = $product['cart_quantity'];
                $to_store_in_subscription['frequency'] = $validate_subscription_data[0]['frequency'];
                $to_store_in_subscription['id_currency'] = $currency->id;
                $to_store_in_subscription['id_lang'] = $context->language->id;
                $to_store_in_subscription['id_external'] = $external_id;
                $to_store_in_subscription['status'] = "initiated";
                $to_store_in_subscription['last_payment_status'] = "initiated";
                $to_store_in_subscription['last_payment_amount'] = round($product['price_with_reduction'], 2);
                $to_store_in_subscription['created_at'] = date("Y-m-d H:i:s");
                $to_store_in_subscription['updated_at'] = date("Y-m-d H:i:s");
                $to_store_in_subscription['last_recurring_date'] = date("Y-m-d H:i:s");
                $to_store_in_subscription['next_recurring_date'] = $nextRecurringDate;
                Db::getInstance()->insert('payout_subscription_product', $to_store_in_subscription);
            }
        }
        $checkout_data = array(
            'amount'           => $total,
            'currency'         => $currency->iso_code,
            'customer'         => [
                'first_name' => $customer->firstname,
                'last_name'  => $customer->lastname,
                'email'      => $customer->email
            ],
            'billing_address'  => $billing_address,
            'shipping_address' => $shipping_address,
            'products'         => $productAttributes,
            'external_id'      => $external_id,
            'redirect_url'     => $url

        );
        if ($subscription_flag !=0) {
            $checkout_data['mode'] = 'store_card';
        }
        
        
        $response = $payout->createCheckout($checkout_data);
        $checkoutUrl = $response->checkout_url;
        Tools::redirect($checkoutUrl);
        exit(0);
    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }
}
