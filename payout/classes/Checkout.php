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

class Checkout
{
    /**
     * Verify input data a return as array with required and optional attributes.
     *
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    public function create($data)
    {
        if (! is_array($data)) {
            throw new Exception('Payout error: Wrong checkout parameters.');
        }

        $checkout_required = array(
            'amount',
            'currency',
            'customer',
            'external_id',
            'redirect_url'
        );

        foreach ($checkout_required as $required_attribute) {
            if (! key_exists($required_attribute, $data)) {
                throw new Exception("Payout error: Missing required parameter \"$required_attribute\".");
            }
        }

        $customer_required = array(
            'first_name',
            'last_name',
            'email'
        );

        foreach ($customer_required as $required_attribute) {
            if (! key_exists($required_attribute, $data['customer'])) {
                throw new Exception("Payout error: Missing required parameter \"$required_attribute\".");
            }
        }
        
        $checkout_data = array(
            'amount'       => number_format($data['amount'] * 100, 0, '.', ''), // Amount in cents
            'currency'     => $data['currency'],
            'customer'     => [
                'first_name' => $data['customer']['first_name'],
                'last_name'  => $data['customer']['last_name'],
                'email'      => $data['customer']['email']
            ],
            'external_id'  => $data['external_id'],
            'nonce'        => '',
            'redirect_url' => $data['redirect_url'],
            'signature'    => ''
        );

        if (isset($data['metadata'])) {
            $checkout_data['metadata'] = $data['metadata'];
        }
        if (isset($data['billing_address'])) {
            $checkout_data['billing_address'] = $data['billing_address'];
        }
        if (isset($data['shipping_address'])) {
            $checkout_data['shipping_address'] = $data['shipping_address'];
        }
        if (isset($data['products'])) {
            $checkout_data['products'] = $data['products'];
        }
        if (isset($data['idempotency_key'])) {
            $checkout_data['idempotency_key'] = $data['idempotency_key'];
        }

        if (isset($data['mode'])) {
            $checkout_data['mode'] = $data['mode'];
        }
        if (isset($data['recurrent_token'])) {
            $checkout_data['recurrent_token'] = $data['recurrent_token'];
        }
        
        return $checkout_data;
    }
}
