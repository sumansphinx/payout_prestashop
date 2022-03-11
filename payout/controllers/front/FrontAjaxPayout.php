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

class PayoutFrontAjaxPayoutModuleFrontController extends FrontController
{
    /**
     * Disable the subscription for a order id of a customer
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */

    public function initContent()
    {
        $to_return = false;
        if (Tools::getValue('action') == '') {
            $this->ajaxDie(json_encode($to_return));
        }

        $id_subscription = (int) Tools::getValue('id');
        $type = Tools::getValue('action');
        if ($type == 'enableSubscription') {
            if (Tools::getValue('frequency') == 'weekly') {
                $next_recurring_date = date('Y-m-d H:i:s', strtotime('+7 days'));
            } elseif (Tools::getValue('frequency') == 'monthly') {
                $next_recurring_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            } else {
                $next_recurring_date = date('Y-m-d H:i:s', strtotime('+365 days'));
            }
            $sql = "UPDATE `"._DB_PREFIX_."payout_subscription_product` SET `status`='pending',
                    `next_recurring_date`='".$next_recurring_date."' WHERE 
                    `id_payout_subscription_product`=". $id_subscription;
        } else {
            $sql = "UPDATE `"._DB_PREFIX_."payout_subscription_product` SET `status`='stopped' WHERE 
            `id_payout_subscription_product`=". $id_subscription;
        }
        $customer = Context::getContext()->customer;
        if ($customer->isLogged() === true) {
            if (Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql)) {
                $to_return = true;
            }
        }

        $this->ajaxDie(json_encode($to_return));
    }
}
