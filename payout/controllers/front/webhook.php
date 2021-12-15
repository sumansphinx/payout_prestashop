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

class PayoutWebhookModuleFrontController extends ModuleFrontController
{

    
    public function postProcess()
    {
        if (!($this->module instanceof Payout)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }


        /** For Webhook url */
        if (!Tools::getIsset('cart_id')) {
            $notification_data = Tools::file_get_contents('php://input');
            $webhook_data = json_decode($notification_data, true);
            $external_id = $webhook_data['external_id'];
            if ($webhook_data['type'] == 'payu_token.created') {
                $user_token = $webhook_data['data']['token_value'];
                $data_to_update = 'update ' . _DB_PREFIX_ . 'payout_subscription_product set 
                user_token="' . $user_token . '" where id_external="' . $external_id . '"';
                Db::getInstance()->execute($data_to_update);
                $validateLog = Db::getInstance()->executeS('select * from ' . _DB_PREFIX_ . 'payout_log where
                external_id = "' . $external_id . '" and type="card"');
                if (isset($validateLog) && count($validateLog) > 0) {
                    //update Log
                    $sqllog = "update " . _DB_PREFIX_ . "payout_log set `response_data`='" . $notification_data . "' 
                    where external_id='" . $external_id . "' and type='card'";
                    Db::getInstance()->execute($sqllog);
                } else {
                    Db::getInstance()->insert('payout_log', array(
                        'response_data' => $notification_data,
                        'type' => 'card',
                        'external_id' => $external_id
                    ));
                }
            }
            if ($webhook_data['type'] == 'checkout.succeeded') {
                $cart_ids = explode("-", $external_id);
                $cart_id = $cart_ids[0];
                //Add Data into payoutlog
                $sql = 'select * from ' . _DB_PREFIX_ . 'payout_log where external_id = "' . $external_id . '" 
                and type="product"';
                $validateLog = Db::getInstance()->executeS($sql);
                if (isset($validateLog) && count($validateLog) > 0) {
                    //update Log
                    $sql = "update " . _DB_PREFIX_ . "payout_log set `response_data`='" . $notification_data . "' 
                    where external_id='" . $external_id . "' and type='product'";
                    Db::getInstance()->execute($sql);
                } else {
                    Db::getInstance()->insert('payout_log', array(
                        'response_data' => $notification_data,
                        'type' => 'product',
                        'external_id' => $external_id
                    ));
                }

                $payment_status = $webhook_data['data']['payment']['status'];
                $last_payment_amount = $webhook_data['data']['amount'];
                $last_payment_status = $webhook_data['data']['payment']['status'];
                $status = "pending";
                $sql_update = 'update ' . _DB_PREFIX_ . 'payout_subscription_product set 
                last_payment_amount=' . $last_payment_amount . ', last_payment_status="' . $last_payment_status . '", 
                status="' . $status . '", updated_at="' . date("Y-m-d H:i:s") . '" 
                where id_external="' . $external_id . '"';
                Db::getInstance()->execute($sql_update);

                $order_id = Order::getOrderByCartId((int) $cart_id);
                if (!$order_id) {
                    $cart = new Cart((int) $cart_id);
                    //echo $secure_key = Context::getContext()->customer->secure_key;
                    //echo $secure_key = Context::getContext()->customer->secure_key;
                    $customer = new Customer((int) $cart->id_customer);
                    $secure_key = $customer->secure_key;
                    $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
                    $message = null; // add comment to show in BO.
                    $module_name = $this->module->displayName;
                    $currency_id = $cart->id_currency;
                    if ($cart->id_customer == 0 ||
                            $cart->id_address_delivery == 0 ||
                            $cart->id_address_invoice == 0 ||
                            !$this->module->active) {
                        //Tools::redirect('index.php?controller=order&step=1');
                    }

                    if (!Validate::isLoadedObject($customer)) {
                        //Tools::redirect('index.php?controller=order&step=1');
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
                }
            }

            echo "Ok";
            exit();
        }
    }
}
