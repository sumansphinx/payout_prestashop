<?php
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
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

        /** for update payment **/   
        $notification_data = Tools::file_get_contents('php://input');
        

        $webhook_data = json_decode($notification_data, true);
        $external_id = $webhook_data['external_id'];
        if ($webhook_data['type'] == 'checkout.succeeded') 
        {
            $cart_ids = explode("-", $external_id);
            $cart_id = $cart_ids[0];
            $order_id = Order::getOrderByCartId((int) $cart_id);
            $valBefore_order_payment= Configuration::get('PS_ORDER_TRANSECTION_BEFORE_PAYMENT');
            if($valBefore_order_payment==1)
            {  
               $payment_status = Configuration::get('PS_OS_PAYMENT');
               $order=new Order($order_id);
               $history = new OrderHistory();
               $history->id_order = (int)$order->id;
               $history->changeIdOrderState($payment_status, (int)($order->id)); //order status=2 Payment  Accepted
           
            }
        }
      
        /** For Webhook url */
        if (!Tools::getIsset('cart_id')) {
            $notification_data = Tools::file_get_contents('php://input');
            $webhook_data = json_decode($notification_data, true);
            $external_id = $webhook_data['external_id'];
            
            if ($webhook_data['type'] == 'payu_token.created') {
                $user_token = $webhook_data['data']['token_value'];
                $user_token = $this->module->encryptToken($user_token);
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
                //get frequency for next recurring date
                $frequency_sql = 'select frequency from ' . _DB_PREFIX_ . 'product where id_product = 
                (select id_product from ' . _DB_PREFIX_ . 'payout_subscription_product where 
                id_external="'.$external_id.'")';
                $frequency_data = Db::getInstance()->executeS($frequency_sql);
                $frequency = $frequency_data[0]['frequency'];
                
                $next_recurring_date = $this->module->getNextRecurringDate($frequency);
                $sql_update = 'update ' . _DB_PREFIX_ . 'payout_subscription_product set 
                last_payment_amount=' . $last_payment_amount . ', last_payment_status="' . $last_payment_status . '", 
                status="' . $status . '", updated_at="' . date("Y-m-d H:i:s") . '",
                next_recurring_date="' . $next_recurring_date . '" where id_external="' . $external_id . '"';
                Db::getInstance()->execute($sql_update);

                $order_id = Order::getOrderByCartId((int) $cart_id);
                if (!$order_id) {
                    $cart = new Cart((int) $cart_id);
                    $customer = new Customer((int) $cart->id_customer);
                    $secure_key = $customer->secure_key;
                    $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
                    $message = null; // add comment to show in BO.
                    $module_name = $this->module->displayName;
                    $currency_id = $cart->id_currency;
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
