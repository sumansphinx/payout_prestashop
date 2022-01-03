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

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payout_subscription_product` (
    `id_payout_subscription_product` int(11) NOT NULL AUTO_INCREMENT,
    `id_order` int(11) NOT NULL DEFAULT 1,
    `id_customer` int(11) NOT NULL DEFAULT 1,
    `user_token` text DEFAULT NULL,
    `id_product` int(11) NOT NULL DEFAULT 1,
    `id_product_attribute` int(11) NOT NULL DEFAULT 1,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `status` varchar(30) DEFAULT "",
    `frequency` varchar(30) DEFAULT "",
    `last_payment_amount`  varchar(30) DEFAULT "",
    `last_payment_status`  varchar(30) DEFAULT "",
    `id_currency`  int(11) NOT NULL DEFAULT 1,
    `id_lang`  int(11) NOT NULL DEFAULT 1,
    `id_external` varchar(50) DEFAULT NULL,
    `last_recurring_date` datetime DEFAULT NULL,
    `next_recurring_date` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY  (`id_payout_subscription_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payout_subscription_logs` (
    `id_payout_subscription_logs` int(11) NOT NULL AUTO_INCREMENT,
    `id_payout_subscription_product` int(11) NOT NULL DEFAULT 1,
    `id_order` int(11) NOT NULL DEFAULT 1,
    `payment_amount` varchar(30) DEFAULT "",
    `id_payment_transaction` text DEFAULT "",
    `id_external` varchar(50) DEFAULT NULL,
    `payment_status` varchar(30) DEFAULT "",
    `currency`  varchar(30) DEFAULT "",
    `web_request_data` text DEFAULT NULL,
    `webhook_response_data` text DEFAULT NULL,
    `web_response_data` text DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    PRIMARY KEY  (`id_payout_subscription_logs`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payout_log` (
  `id_log` bigint(20) NOT NULL,
  `response_data` text NOT NULL,
  `cart_id` int(11) NOT NULL,
  `external_id` varchar(50) DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP 
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
