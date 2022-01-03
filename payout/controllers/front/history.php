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

//use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class PayoutHistoryModuleFrontController extends ModuleFrontController
{
    
    public function __construct()
    {
        $this->name = 'payout';
        parent::__construct();
    }

    /**
     * Assign template vars related to page content.
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $product = $this->getSubscriptionProduct();

        if (count($product) <= 0) {
            $this->warning[] = $this->trans('There is no product for subscription.', [], 'Shop.Notifications.Warning');
        }

        $ajaxUrl = Context::getContext()->link->getModuleLink($this->name, 'FrontAjaxPayout', [], true);

        $this->context->smarty->assign([
            'products' => $product,
            'payout_ajax_front_controller' => $ajaxUrl,
        ]);
        
        parent::initContent();
        $this->setTemplate('module:payout/views/templates/front/history.tpl');
    }

    public function getSubscriptionProduct()
    {
        $id_lang = (int) $this->context->language->id;
        $customer_orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT psp.*, pl.name FROM `' . _DB_PREFIX_ . 'payout_subscription_product` psp LEFT JOIN `
            ' . _DB_PREFIX_ . 'product_lang` pl ON (psp.`id_product` = pl.`id_product`) WHERE
            pl.`id_lang` = '. $id_lang .' AND psp.`id_customer` = ' . (int) $this->context->customer->id .'
            ORDER BY psp.`next_recurring_date` DESC'
        );

        if (!$customer_orders) {
            return [];
        }

        return $customer_orders;
    }

    
    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();

        $breadcrumb['links'][] = [
            'title' => $this->trans('Subscription History', [], 'Shop.Theme.Customeraccount'),
            'url' => $this->context->link->getPageLink('history'),
        ];

        return $breadcrumb;
    }
}
