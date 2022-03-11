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

if (! defined('_PS_VERSION_')) {
    exit;
}

class Payout extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name          = 'payout';
        $this->tab           = 'payments_gateways';
        $this->version       = '1.0.0';
        $this->author        = 'Payout';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Payout Payment');
        
        $this->description = $this->l('Pay Via Payout Payment');

        $this->confirmUninstall = $this->l('Are you sure?');

        $this->limited_countries = array('sk', 'SK', 'bby', 'BBY', 'bre', 'BRE', 'bro', 'BRO', 'in', 'IN', 'us', 
        'US', 'gb', 'GB', 'cze', 'CZE', 'cz', 'CZ', 'hun', 'HUN', 'hu', 'HU', 'pol', 'POL', 'pl', 'PL', 'hrv', 'HRV', 
        'hr', 'HR', 'rou', 'ROU', 'ro', 'RO');

        $this->limited_currencies = array('EUR', 'CZK', 'PLN', 'KES', 'HUF', 'HRK', 'RON');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */

   Public function createNewStatus()
   {
        

        $last_id=$this->Orderstatusid();
      
        if($last_id!='')
        {
            return $last_id;
        }

        $statusVal='PAYOUT_PENDING_PAYMENT';
        $orderStateObj = new OrderState();
        $orderStateObj->send_email = 0;
        $orderStateObj->module_name =  $statusVal;
        $orderStateObj->invoice = 0;
        $orderStateObj->color = '#4169E1';
        $orderStateObj->logable = 0;
        $orderStateObj->shipped = 0;
        $orderStateObj->unremovable = 1;
        $orderStateObj->delivery = 0;
        $orderStateObj->hidden = 0;
        $orderStateObj->paid = 0;
        $orderStateObj->pdf_delivery = 0;
        $orderStateObj->pdf_invoice = 0;
        $orderStateObj->deleted = 0;
        $languageval= Language::getLanguages(true);
        foreach ($languageval as $language) {

            $orderStateObj->name[$language['id_lang']] = 'Payout Pending Payment';
        }
      $orderStateObj->add();
      $last_id=$this->Orderstatusid();
      
      return  $last_id;

   }




   public function  Orderstatusid()
   {
    $last_id = Db::getInstance()->getValue('SELECT MAX(id_order_state) FROM '._DB_PREFIX_.'order_state where module_name="PAYOUT_PENDING_PAYMENT"');

    return $last_id; 
    //select id_order_state from ps_order_state  where module_name='PAYOUT_PENDING_PAYMENT'
    
   }

    

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');

            return false;
        }


       $last_id=$this->createNewStatus();

        Configuration::updateValue('PS_PAYOUNT_PENDING_STATUS', $last_id);
        //$this->Orderstatusid();

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module is not available in your country');

            return false;
        }

        
       
        //Configuration::updateValue('PS_PAYOUNT_PENDING_STATUS', false);
        Configuration::updateValue('PAYOUT_LIVE_MODE', false);
        Configuration::updateValue('PS_ORDER_TRANSECTION_BEFORE_PAYMENT', false);
        
        Configuration::updateValue(
            'PAYOUT_NOTIFY_URL',
            $this->context->link->getModuleLink(
                'payout',
                'webhook',
                []
            )
        );
        include_once($this->local_path.'sql/install.php');
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionAdminPerformanceControllerSaveAfter') &&
            $this->alterTable('add') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayReassurance') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayProductAdditionalInfo') &&
            $this->registerHook('hookPaymentReturn') &&
            $this->registerHook('displayCustomerAccount');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PAYOUT_LIVE_MODE');
        Configuration::deleteByName('PAYOUT_NOTIFY_URL');
        Configuration::deleteByName('PS_ORDER_TRANSECTION_BEFORE_PAYMENT');

        Configuration::deleteByName('PS_PAYOUNT_PENDING_STATUS');


        include_once($this->local_path.'sql/uninstall.php');
       // $this->alterTable('remove');
        return parent::uninstall();
    }




    /***
     * Alter table to add subscription
     */

    public function alterTable($method)
    {
        $sql = '';
        switch ($method) {
            case 'add':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product  ADD `frequency` TEXT NULL DEFAULT NULL 
                , ADD `subscription` INT NOT NULL DEFAULT "0"';
                break;
            case 'remove':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product DROP COLUMN `frequency`, 
                DROP COLUMN  `subscription`';
                break;
        }
        if ($sql !="") {
            if (!Db::getInstance()->Execute($sql)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create a tabbed interface in the product creation section of the backoffice
     * @param type $params
     * @return type
     */
        
    public function hookDisplayAdminProductsExtra($params)
    {
        $product_id = $params['id_product'];
        $subsc_info = array();
        $result = Db::getInstance()->ExecuteS('SELECT frequency, subscription FROM '._DB_PREFIX_.'product 
                                                WHERE id_product = ' . (int)$product_id);
        $subsc_info['frequency'] ='';
        $subsc_info['subscription'] =0;
        if ($result && count($result) > 0) {
            $subsc_info['frequency'] =$result[0]['frequency'];
            $subsc_info['subscription'] =$result[0]['subscription'];
        }

        $this->context->smarty->assign(array(
                    'subscription_info' => $subsc_info,
                    'languages' => $this->context->controller->_languages,
                    'default_language' => (int)Configuration::get('PS_LANG_DEFAULT')
                ));
                    
        return $this->display(__FILE__, '/views/templates/admin/product_configure.tpl');
    }

    /*
    * Add the js file in the product creation and updation page
    */
    public function hookActionAdminControllerSetMedia($params)
    {
        // add necessary javascript to products back office
    }
        
    /*
    * Update the recurring configuration from the product page
    */
    
    public function hookActionProductUpdate($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        $subscription_status = Tools::getValue("subscription");
        $frequency = Tools::getValue("frequency");
        $sql = 'update '._DB_PREFIX_.'product set frequency="'.$frequency.'", subscription='.$subscription_status.
                ' where id_product='.$id_product;
        if (!Db::getInstance()->execute($sql)) {
            $this->context->controller->_errors[] = Tools::displayError(
                'Error: An error occurred while processing payment'
            );
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        //return $this->display(__FILE__, 'views/templates/hook/displayReoccuranceData.tpl');
    }

    public function hookDisplayProductAdditionalInfo()
    {
        $id_product = (int) Tools::getValue("id_product");
        $sql = 'select frequency, subscription from ' . _DB_PREFIX_ . 'product where id_product = '.$id_product;
        $subscription_data = Db::getInstance()->executeS($sql);
        
        
        if ($subscription_data[0]['subscription']==1) {
            $this->smarty->assign(
                array(
                    'frequency'  => $subscription_data[0]['frequency'],
                )
            );
            return $this->display(__FILE__, 'views/templates/hook/displayReoccuranceData.tpl');
        }
        return false;
    }
     
    public function hookDisplayReassurance($params)
    {
        //To display content in reassurance section
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        
        
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPayoutModule')) == true) {
            $this->postProcess();
        }
        $cronURL = $this->context->link->getModuleLink('payout', 'cron', []);
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('cron_url', $cronURL);
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(
            array(
                'id_order'  => $order->id,
                'reference' => $order->reference,
                'params'    => $params,
                'total'     => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
            )
        );
        
        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        
        if (! $this->active) {
            return;
        }
        
        if (! $this->checkCurrency($params['cart'])) {
            return;
        }
        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Pay via Payout'))
               ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true));
        return [
            $option
        ];
    }

    //compatibility for prestashop 1.6
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
           
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
    
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }
    
    public function checkCurrency($cart)
    {
        $currency_order    = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookActionAdminPerformanceControllerSaveAfter()
    {
        /* Place your code here. */
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (_PS_VERSION_ < 1.7) {
            if ($this->active == false) {
                return;
            }
        
            $order = $params['objOrder'];
            if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
                $this->smarty->assign('status', 'ok');
            }

            $this->smarty->assign(
                array(
                    'id_order'  => $order->id,
                    'reference' => $order->reference,
                    'params'    => $params,
                    'total'     => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                )
            );
            return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
        }
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $helper->module                   = $this;
        $helper->default_form_language    = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'submitPayoutModule';
        $helper->currentIndex  = $this->context->link->getAdminLink('AdminModules', false)
                                 . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                                 . $this->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     * 
     */
    protected function getConfigForm()
    {


        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'  => 'text',
                        'required' => true,
                        'readonly' => true,
                        'name'  => 'PAYOUT_NOTIFY_URL',
                        'label' => $this->l('Notify Url'),
                        'value' => $this->context->link->getModuleLink('payout', 'webhook', [])
                    ),
                    
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Enable Sanbox'),
                        'name'    => 'PAYOUT_MODE',
                        'is_bool' => true,
                        'desc'    => $this->l('Enable Sandbox Mode'),
                        'values'  => array(
                            array(
                                'id'    => 'enabled',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id'    => 'disabled',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),

                       array(
                        'type'    => 'switch',
                        'label'   => $this->l('Order Creation before Payment'),
                        'name'    => 'PS_ORDER_TRANSECTION_BEFORE_PAYMENT',
                        'is_bool' => true,
                        'desc'    => $this->l('Order Creation before Payment'),
                        'values'  => array(
                            array(
                                'id'    => 'enabled',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id'    => 'disabled',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),


                    array(
                        'col'   => 3,
                        'type'  => 'text',
                        'required' => true,
                        'desc'  => $this->l('Client Id'),
                        'name'  => 'PAYOUT_CLIENT_ID',
                        'label' => $this->l('Client Id'),
                    ),
                    array(
                        'type'  => 'text',
                        'required' => true,
                        'name'  => 'PAYOUT_SECRET',
                        'label' => $this->l('Secret'),
                    ),
                    
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
    'PS_ORDER_TRANSECTION_BEFORE_PAYMENT' => Configuration::get('PS_ORDER_TRANSECTION_BEFORE_PAYMENT','0'),
            'PAYOUT_ACCOUNT_EMAIL' => Configuration::get('PAYOUT_ACCOUNT_EMAIL', 'contact@payout.one'),
            'PAYOUT_MODE'          => Configuration::get('PAYOUT_MODE', null),
            'PAYOUT_NOTIFY_URL'    => Configuration::get('PAYOUT_NOTIFY_URL', null),
            'PAYOUT_CLIENT_ID'     => Configuration::get('PAYOUT_CLIENT_ID', null),
            'PAYOUT_SECRET'        => Configuration::get('PAYOUT_SECRET', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add Subscription page at customer Account.
     */
    public function hookDisplayCustomerAccount()
    {
            $context = Context::getContext();
            $id_customer = $context->customer->id;
            $url = Context::getContext()->link->getModuleLink($this->name, 'history', [], true);
            $this->context->smarty->assign([
                'front_controller' => $url,
                'id_customer' => $id_customer,
                'ps_version' => $this->version,
            ]);

            return $this->display(dirname(__FILE__), '/views/templates/front/customerAccount.tpl');
    }
    
    public function getNextRecurringDate($frequency)
    {
        switch ($frequency) {
            case 'weekly':
                $next_date = date("Y-m-d H:i:s", strtotime("+7 day"));
                break;
            case 'monthly':
                $next_date = date("Y-m-d H:i:s", strtotime("+30 day"));
                break;
            case 'yearly':
                $next_date = date("Y-m-d H:i:s", strtotime("+365 day"));
                break;
            default:
                $next_date = date("Y-m-d H:i:s", strtotime("+7 day"));
        }
        return $next_date;
    }
    /**
     * Encrypt function
     *
     * @param [type] $token
     * @return void
     */
    public function encryptToken($token)
    {
        $ciphering = "BF-CBC";
        $options = 0;
        $encryption_iv = $this->encdecCode();
        $encryption_key = openssl_digest(php_uname(), 'MD5', true);
        $encryption = openssl_encrypt($token, $ciphering, $encryption_key, $options, $encryption_iv);
        return $encryption;
    }

    /**
     * Decrypt Token
     *
     * @param [type] $token
     * @return void
     */
    public function decryptToken($token)
    {
        $ciphering = "BF-CBC";
        $options = 0;
        $decryption_iv = $this->encdecCode();
        $decryption_key = openssl_digest(php_uname(), 'MD5', true);
        $decryption = openssl_decrypt($token, $ciphering, $decryption_key, $options, $decryption_iv);
        return $decryption;
    }

    /**
     * Generate decryption iv from secret
     */
    public function encdecCode()
    {
        $config = require _PS_CACHE_DIR_ . 'appParameters.php';
        $secret_key = $config['parameters']['secret'];
        return Tools::substr($secret_key, 0, 8);
    }
}
