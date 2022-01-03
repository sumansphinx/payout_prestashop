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

class PayoutCronModuleFrontController extends ModuleFrontController
{

    /**
    * Constructor for the gateway.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * script for recurrence.
    */
    public function display()
    {
        // Get all product for subscription
        $productData = $this->getProductData();

        if (count($productData) > 0) {
            $context = Context::getContext();
            foreach ($productData as $pd) {
                $customer_id = $pd['id_customer'];
                $this->updateProductStatus($pd['id_payout_subscription_product'], 'processing');
                if (is_null($this->context->cart->id)) {
                    $this->context->cart->add();
                    $this->context->cookie->__set('id_cart', $this->context->cart->id);
                }

                if ($this->context->cookie->id_cart) {
                    $cart = new Cart($this->context->cookie->id_cart);
                }
                $cart->id_customer = (int)($customer_id);
                $cart->id_address_delivery = (int) (Address::getFirstCustomerAddressId($cart->id_customer));
                $cart->id_address_invoice = $cart->id_address_delivery;
                $cart->id_lang = (int)($pd['id_lang']);
                $cart->id_currency = (int)($pd['id_currency']);
                $cart->id_carrier = 1;
                $cart->recyclable = 0;
                $cart->gift = 0;
                $id_prod = $pd['id_product'];
                $id_address_delivery = $cart->id_address_delivery;
                $customization = '';
                //$productToAdd = new Product((int)($id_prod), true, (int)($pd['id_lang']));
                $context->cart->updateQty(
                    (int)($pd["quantity"]),
                    (int)($id_prod),
                    (int)($pd["id_product_attribute"]),
                    $customization,
                    'up',
                    (int)($id_address_delivery)
                );
                $cart->update();
                

                $clientId = Configuration::get('PAYOUT_CLIENT_ID');
                $secret   = Configuration::get('PAYOUT_SECRET');
                $sandbox = Configuration::get('PAYOUT_MODE');
                $productAttributes = array();

                $config = array(
                    'client_id'     => $clientId,
                    'client_secret' => $secret,
                    'sandbox'       => $sandbox
                );

                require_once(dirname(__FILE__) . '/../../classes/init.php');

                $payout = new Client($config);
                
                $context->customer = new Customer((int)$customer_id);
                $context->currency = new Currency((int)$cart->id_currency);
                $context->language = new Language((int)$cart->id_lang);
                $customer = $context->customer;
                $currency = $context->currency;
                $total    = (float)$cart->getOrderTotal(true, Cart::BOTH);
                
                $url= $this->context->link->getModuleLink('payout', 'confirmation', ['cart_id' => $cart->id]);
                
                /********** format billing and shipping Address **********/
                $dAddress              = new Address($cart->id_address_delivery);
                $iAddress              = new Address($cart->id_address_invoice);
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
                //$subscription_flag = 0;
                foreach ($products as $product) {
                    $productAttributes[] = array(
                        'name'       => $product['name'],
                        'unit_price' => round($product['price_with_reduction'], 2),
                        'quantity'   => $product['cart_quantity'],

                    );
                }
                $user_token = $this->module->decryptToken($pd['user_token']);
                $checkout_data = array(
                    'amount'           => $total,
                    'currency'         => $currency->iso_code,
                    'customer'         => [
                        'first_name' => $customer->firstname,
                        'last_name'  => $customer->lastname,
                        'email'      => $customer->email
                    ],
                    'billing_address'   => $billing_address,
                    'shipping_address'  => $shipping_address,
                    'products'          => $productAttributes,
                    'external_id'       => $cart->id.'-'.time(),
                    'redirect_url'      => $url,
                    'mode'              => 'recurrent',
                    'recurrent_token'   => $user_token,
                    'recurrent_log_id'  => $pd['id_payout_subscription_product'],
                    'recurrent_order_id'=> $pd['id_order'],
                );
                
                $this->updateExternalId($pd['id_payout_subscription_product'], $checkout_data['external_id']);
               
                $payout->createCheckout($checkout_data);

                $context->cookie->id_cart = null;

                echo "done";
            }
        } else {
            echo "No Product for subscription";
        }
        exit();
    }

    /**
    * Get All Products Details.
    */
    public function getProductData()
    {
        $result = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'payout_subscription_product WHERE
            next_recurring_date < "' . date('Y-m-d H:i:s') .'" and status = "pending"');
        if ($result) {
            return $result;
        } else {
            return [];
        }
    }

    /**
    * Update Product Status in subscription table.
    */
    public function updateProductStatus($id, $status)
    {
        $sql = 'update '._DB_PREFIX_.'payout_subscription_product set status="'.$status.'" where 
        id_payout_subscription_product='.$id;
        Db::getInstance()->execute($sql);
    }

    /**
    * Update External Id in subscription table.
    */
    public function updateExternalId($id, $id_external)
    {
        $sql = 'update '._DB_PREFIX_.'payout_subscription_product set id_external="'.$id_external.'" where 
        id_payout_subscription_product='.$id;
        Db::getInstance()->execute($sql);
    }
}
