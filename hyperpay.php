<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayPayment.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayRefund.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCapture.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Request.php');

include_once 'config.php';

class Hyperpay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'hyperpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.1';
        $this->author = 'Hyperpay';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Hyperpay payment gateway');
        $this->description = $this->l("Use Hyperpay's payment gateway to accept credit cards and depit cards");

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Module Configuration page
     *
     * @return string
     */
    public function getContent()
    {
        $output = null;

        $output .= $this->formSubmission();

        return $output . $this->displayForm();
    }

    /**
     * Handle saving the module config page changes
     *
     * @return void
     */
    private function formSubmission()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {

            // Special check for user id and access token
            if (empty(Tools::getValue('HYPERPAY_ACCESS_TOKEN'))) {
                $output .= $this->displayError($this->l('The the access token should be filled'));
                return $output;
            }

            foreach (CONFIG as $key => $value) {
                if ($key != 'payment_methods') {
                    $formValue = Tools::getValue($key);
                    if (isset($value['required']) ? $value['required'] : true) {
                        if (!empty($formValue)) {
                            Configuration::updateValue($key, $formValue);
                        } else {
                            $message = isset($value['label']) ? $value['label'] : Tools::ucfirst(Tools::strtolower(explode('_', $key)[1])) . ' is required';
                            $output .= $this->displayError($this->l($message));
                        }
                    } else {
                        Configuration::updateValue($key, $formValue);
                    }
                }

                if ($key == 'payment_methods') {
                    foreach ($value as $payment => $paymentConfig) {
                        foreach ($paymentConfig as $paymentConfigKey => $paymentConfigValue) {
                            $formValue = Tools::getValue($paymentConfigKey);
                            if (isset($paymentConfigValue['required']) ? $paymentConfigValue['required'] : false) {
                                if (!empty($formValue)) {
                                    Configuration::updateValue($paymentConfigKey, $formValue);
                                } else {
                                    $message = $payment . ' ' . (isset($paymentConfigValue['label']) ? $paymentConfigValue['label'] : Tools::ucfirst(Tools::strtolower(explode('_', $paymentConfigKey)[3]))) . ' is required';
                                    $output .= $this->displayError($this->l($message));
                                }
                            } else {
                                Configuration::updateValue($paymentConfigKey, $formValue);
                            }
                        }
                    }
                }
            }
            return $output;
        }
    }


    /**
     * Create special fields used in this module
     *
     * @param array $field
     *
     * @return void
     */
    private function specialFields(&$field)
    {
        switch ($field['type']) {

            case 'currency':
                $field['type'] = 'select';
                $field['size'] = 0;
                $currencies = array_map(function ($currency) {
                    return ['value' => $currency['iso_code'], 'label' => $currency['name'] . ' - ' . $currency['iso_code']];
                }, Currency::getCurrencies());

                $field['options'] = [
                    'id' => 'value',
                    'name' => 'label',
                    'query' => $currencies
                ];
                break;

            case 'country':
                $field['type'] = 'select';
                $field['size'] = 0;
                $countries = array_map(function ($country) {
                    return ['value' => $country['iso_code'], 'label' => $country['name'] . ' - ' . $country['iso_code']];
                }, Country::getCountries($this->context->language->id));

                $field['options'] = [
                    'id' => 'value',
                    'name' => 'label',
                    'query' => $countries
                ];
                break;

            case 'accepted-status':
                $field['type'] = 'select';
                $field['size'] = 0;

                $statuses = array_map(function ($status) {
                    return ['value' => $status['id_order_state'], 'label' => $status['name']];
                }, OrderState::getOrderStates($this->context->language->id));

                $field['options'] = [
                    'id' => 'value',
                    'name' => 'label',
                    'query' => $statuses
                ];
                break;
        }
    }

    /**
     * Display the module configuration form
     *
     * @return void
     */
    public function displayForm()
    {

        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        $fieldsForm = [];

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ],
            'input' => []
        ];
        $inputs = [];
        foreach (CONFIG as $key => $value) {
            if ($key == 'payment_methods') {
                foreach ($value as $payment => $paymentConfig) {
                    $paymentInputs = [];

                    foreach ($paymentConfig as $paymentConfigKey => $paymentConfigValue) {
                        if (is_string($paymentConfigValue)) {
                            $field = [
                                'type' => 'text',
                                'label' => Tools::ucfirst(Tools::strtolower(explode('_', $paymentConfigKey)[3])),
                                'name' => $paymentConfigKey,
                                'size' => 20,
                                'required' => false,
                            ];
                        } else {
                            $field = $paymentConfigValue;

                            $field['type'] = isset($paymentConfigValue['type']) ? $paymentConfigValue['type'] : 'text';
                            $field['label'] = isset($paymentConfigValue['label']) ? $paymentConfigValue['label'] : Tools::ucfirst(Tools::strtolower(explode('_', $paymentConfigKey)[3]));
                            $field['name'] = $paymentConfigKey;
                            $field['size'] = isset($paymentConfigValue['size']) ? $paymentConfigValue['size'] : 20;
                            $field['required'] = isset($paymentConfigValue['required']) ? $paymentConfigValue['required'] : false;
                        }
                        $this->specialFields($field);
                        $paymentInputs[] = $field;
                        $helper->fields_value[$paymentConfigKey] = Configuration::get($paymentConfigKey);
                    }
                    $fieldsForm[] = [
                        'form' => [
                            'legend' => [
                                'title' => $payment
                            ],
                            'input' => $paymentInputs
                        ]
                    ];
                }
            } else {
                if (is_string($value)) {
                    $field = [
                        'type' => 'text',
                        'label' => Tools::ucfirst(Tools::strtolower(explode('_', $key)[1])),
                        'name' => $key,
                        'size' => 20,
                        'required' => true,
                    ];
                } else {
                    $field = $value;

                    $field['type'] = isset($value['type']) ? $value['type'] : 'text';
                    $field['label'] = isset($value['label']) ? $value['label'] : Tools::ucfirst(Tools::strtolower(explode('_', $key)[1]));
                    $field['name'] = $key;
                    $field['size'] = isset($value['size']) ? $value['size'] : 20;
                    $field['required'] = isset($value['required']) ? $value['required'] : true;
                }
                $this->specialFields($field);
                $inputs[] = $field;
                $helper->fields_value[$key] = Configuration::get($key);
            }
        }

        $fieldsForm[0]['form']['input'] = $inputs;

        $fieldsForm[] = [
            'form' => [
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];


        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        return $helper->generateForm($fieldsForm). '
        <script type="text/javascript">
            jQuery(function ($) {
              var lang = "<?php echo $lang; ?>";
              var title = ( lang === "ar" ? "بطاقة مدى البنكية" :"mada debit card");
              $("#HYPERPAY_METHOD_MADA_TITLE").val(title);
              $("#HYPERPAY_METHOD_MADA_TITLE").attr("readonly","true");
            });
       </script>'
                ;
    }

    public function createOrderThread($id_order)
    {
        // Create new thred in the order
        $orderThread = new CustomerThread();
        $orderThread->id_shop = $this->context->shop->id;
        $orderThread->id_lang = $this->context->language->id;
        $orderThread->id_contact = 0;
        $orderThread->id_order = $id_order;
        $orderThread->id_customer = $this->context->customer->id;
        $orderThread->status = 'open';
        $orderThread->email = $this->context->customer->email;
        $orderThread->token = Tools::passwdGen(12);
        $orderThread->add();
        return (int)$orderThread->id;
    }

    private function refund($amount, HyperpayPayment $order)
    {

        $convertedAmount = HPHelper::convertPrice($amount, "HYPERPAY_METHOD_{$order->payment_method}", $order);

        $paymentStatus = json_decode(Request::sendRefundRequest($order, $convertedAmount), true);

        if ($paymentStatus == null || !isset($paymentStatus['result']) || !isset($paymentStatus['result']['code'])) {
            $status = 'fail';
            $description = is_string($paymentStatus) ? $paymentStatus : "didn't get a response or got an invalid response";
        } elseif (
            preg_match('/^(000\.400\.0|000\.400\.100)/', $paymentStatus['result']['code'])
            || preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $paymentStatus['result']['code'])
        ) {
            $status = 'success';
            $description = "The amount $amount been refunded successfully";
        } else {
            $status = 'fail';
            $description = isset($paymentStatus['result']) && isset($paymentStatus['result']['description']) ? "We couldn't refund the amount $amount, \"{$paymentStatus['result']['description']}\"" : "The amount could'nt be refunded";
        }

        $refund = new HyperpayRefund();
        $refund->id_order = $order->id_order;
        $refund->refund_id = isset($paymentStatus['id']) ? $paymentStatus['id'] : '';
        $refund->refund_amount = isset($paymentStatus['amount']) ? $paymentStatus['amount'] : -1;
        $refund->payment_method = $order->payment_method;
        $refund->result = $status;

        if (!$refund->save()) {
            $status = 'fail';
            $description .= "\nsomething went wrong couldn't save the transaction";
        }

        return ['success' => $status == 'success', 'description' => $description];
    }

    private function capture(HyperpayPayment $hyperpay_payment)
    {
        $convertedAmount = HPHelper::convertPrice($hyperpay_payment->total_paid, "HYPERPAY_METHOD_{$hyperpay_payment->payment_method}", $hyperpay_payment);

        $paymentStatus = json_decode(Request::sendCaptureRequest($hyperpay_payment, $convertedAmount), true);

        if ($paymentStatus == null || !isset($paymentStatus['result']) || !isset($paymentStatus['result']['code'])) {
            $status = 'fail';
            $description = is_string($paymentStatus) ? $paymentStatus : "didn't get a response or got an invalid response";
        } elseif (
            preg_match('/^(000\.400\.0|000\.400\.100)/', $paymentStatus['result']['code'])
            || preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $paymentStatus['result']['code'])
        ) {
            $status = 'success';

            $hyperpay_payment->payment_status = 'captured';
            $hyperpay_payment->save();
            $description = "The Order been captured successfully";
        } else {
            $description = isset($paymentStatus['result']) && isset($paymentStatus['result']['description']) ? $paymentStatus['result']['description'] : "The payment couldn't be captured";
            $status = 'fail';
        }

        $capture = new HyperpayCapture();
        $capture->id_order = $hyperpay_payment->id_order;
        $capture->capture_id = isset($paymentStatus['id']) ? $paymentStatus['id'] : '';
        $capture->capture_amount = isset($paymentStatus['amount']) ? $paymentStatus['amount'] : -1;
        $capture->payment_method = $hyperpay_payment->payment_method;
        $capture->result = $status;

        if (!$capture->save()) {
            $status = 'fail';
            $description .= "\nsomething went wrong couldn't save the transaction";
        }

        return ['success' => $status == 'success', 'description' => $description];
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        return $this->backofficeOperations(['id_order' => $params['order']->id]);
    }

    /**
     * Dispaly additional Actions in the backoffice and handle them
     *
     * @param array $params
     *
     * @return void
     */
    public function backofficeOperations($params)
    {
        $hyperpay_payment = HyperpayPayment::loadByOrderId($params['id_order']);

        if (!Validate::isLoadedObject($hyperpay_payment)) {
            return false;
        }

        $errors_capture = [];
        $errors_refund = [];
        $html = '';


        if (Tools::isSubmit('submitHyperpayCapture')) {
            // CAPTURE

            if ($hyperpay_payment->payment_status == 'captured') {
                $errors_capture[] = 'The payment already been captured';
            } else {
                // Create new message to log and inform the customer
                $orderMessage = new CustomerMessage();
                $orderMessage->id_customer_thread = $this->createOrderThread($params['id_order']);
                $orderMessage->private = 1;
                $orderMessage->id_order = $params['id_order'];
                $orderMessage->id_customer = $this->context->customer->id;
                $orderMessage->message = '';

                $capture_response = $this->capture($hyperpay_payment);

                if ($capture_response['success']) {
                    $hyperpay_payment->payment_status = 'captured';
                    $orderMessage->message = $capture_response['description'];
                    $hyperpay_payment->save();
                } else {
                    $errors_refund[] = $capture_response['description'];
                }

                if ($orderMessage->message) {
                    $orderMessage->save();
                }
            }
        }

        if (Tools::isSubmit('submitHyperpayRefund')) {
            // REFUND
            $refundAmount = Tools::getValue('refundAmount');
            if (Validate::isPrice($refundAmount)) {
                // Create new message to log and inform the customer
                $orderMessage = new CustomerMessage();
                $orderMessage->id_customer_thread = $this->createOrderThread($params['id_order']);
                $orderMessage->private = 1;
                $orderMessage->id_order = $params['id_order'];
                $orderMessage->id_customer = $this->context->customer->id;
                $orderMessage->message = '';

                $refund_response = $this->refund($refundAmount, $hyperpay_payment);


                if (!$refund_response['success']) {
                    $errors_refund[] = $refund_response['description'];
                    Logger::addLog(json_encode(['payment' => $hyperpay_payment, 'amount' => $refundAmount]));
                } else {
                    $orderMessage->message = $refund_response['description'];
                }

                if ($orderMessage->message) {
                    $orderMessage->save();
                }
            } else {
                $errors_refund[] = "The amount to be refunded should be a valid number";
            }
        }

        $this->context->smarty->assign(
            [
                'params' => $params,
                'errors_refund' => $errors_refund,
                'message' => $orderMessage->message,
            ]
        );

        if (count($params) == 1) {
            $html .= $this->display(__FILE__, 'views/templates/admin/order/refund.tpl');
        }

        if ($hyperpay_payment->payment_type == "PA") {
            $this->context->smarty->assign(
                [
                    'errors_capture' => $errors_capture,
                    'message' => $orderMessage->message,
                ]
            );
            if (count($params) == 1) {
                $html .= $this->display(__FILE__, 'views/templates/admin/order/capture.tpl');
            }
        }
        return $html;
    }

    public function hookPayment($params)
    {
        if (!$this->active)
            return;
        $this->context->smarty->assign(
            [
                'paymentMethods' => $this->getPaymentMethods()
            ]
        );
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    private function getPaymentMethods()
    {
        $paymentOptions = [];

        foreach (CONFIG['payment_methods'] as $payment => $paymentConfig) {
            $settingsKey = "HYPERPAY_METHOD_" . $payment;

            if (Configuration::get("{$settingsKey}_ENABLED")) {
                $cardPaymentOption = [];
                $cardPaymentOption['title'] = $this->l(Configuration::get("{$settingsKey}_TITLE"));
                $cardPaymentOption['logo'] = _PS_BASE_URL_ . _MODULE_DIR_ . "hyperpay/views/imgs/payments_logos/smaller-$payment.svg";
                $cardPaymentOption['link'] =
                    $this->context->link->getModuleLink(
                        $this->name,
                        'iframe',
                        [
                            'method' => $payment
                        ],
                        true
                    );
                if ($payment == 'MADA') {
                    // insert mada at the top of array
                    array_unshift($paymentOptions, $cardPaymentOption);
                } else {
                    $paymentOptions[] = $cardPaymentOption;
                }
            }
        }

        return $paymentOptions;
    }

    /**
     * Create Hyperpay payment options
     *
     * @return void
     */
    public function hookPaymentOptions()
    {
        $paymentOptions = [];

        foreach (CONFIG['payment_methods'] as $payment => $paymentConfig) {
            $settingsKey = "HYPERPAY_METHOD_" . $payment;

            if (Configuration::get("{$settingsKey}_ENABLED")) {
                $cardPaymentOption = new PaymentOption();
                $cardPaymentOption->setCallToActionText($this->l(Configuration::get("{$settingsKey}_TITLE")))
                    ->setAction(
                        $this->context->link->getModuleLink(
                            $this->name,
                            'iframe',
                            [
                                'method' => $payment
                            ],
                            true
                        )
                    )
                    ->setModuleName($this->name);

                    if ($payment == 'MADA') {
                        // insert mada at the top of array and add it's logo
                        array_unshift($paymentOptions, $cardPaymentOption);
                        $cardPaymentOption->setLogo(Media::getMediaPath(_PS_BASE_URL_ . _MODULE_DIR_ . "hyperpay/views/imgs/payments_logos/smaller-$payment.svg"));

                    } else {
                        $paymentOptions[] = $cardPaymentOption;
                    }
            }
        }


        return $paymentOptions;
    }


    /**
     * Install the module
     *
     * @return boolean
     */
    public function install()
    {
        // check if multiple shops
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        // Install default
        if (!parent::install()) {
            return false;
        }

        // install DataBase
        if (!$this->installSQL()) {
            return false;
        }

        // Register Hooks
        if (!$this->registerHooks()) {
            return false;
        }

        // Add default configurations
        if (!$this->prepareConfig()) {
            return false;
        }
        return true;
    }

    /**
     * set module config with default values
     *
     * @return boolean
     */
    private function prepareConfig()
    {
        foreach (CONFIG as $key => $value) {
            $defaultValue = '';
            if ($key != 'payment_methods') {
                if (is_array($value)) {
                    $defaultValue = isset($value['value']) ? $value['value'] : '';
                } else {
                    $defaultValue = $value;
                }
                if (!Configuration::updateValue($key, $defaultValue)) {
                    return false;
                }
            }

            if ($key == 'payment_methods') {
                foreach ($value as $payment => $paymentConfig) {
                    foreach ($paymentConfig as $paymentConfigKey => $paymentConfigValue) {
                        if (is_array($paymentConfigValue)) {
                            $defaultValue = isset($value['value']) ? $value['value'] : '';
                        } else {
                            $defaultValue = $paymentConfigValue;
                        }
                        if (!Configuration::updateValue($paymentConfigKey, $defaultValue)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * Register module hooks
     *
     * @return boolean
     */
    private function registerHooks()
    {
        if (
            !$this->registerHook('paymentOptions')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayOrderConfirmation')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('actionOrderStatusUpdate')
            || !$this->registerHook('header')
            || !$this->registerHook('actionObjectCurrencyAddAfter')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('displayFooterProduct')
            || !$this->registerHook('actionBeforeCartUpdateQty')
            || !$this->registerHook('displayReassurance')
            || !$this->registerHook('displayInvoiceLegalFreeText')
            || !$this->registerHook('actionAdminControllerSetMedia')
            || !$this->registerHook('displayMyAccountBlock')
            || !$this->registerHook('displayCustomerAccount')
            || !$this->registerHook('displayShoppingCartFooter')
            || !$this->registerHook('displayPaymentByBinaries')
            || !$this->registerHook('displayBackOfficeOrderActions')
            || !$this->registerHook('actionOrderSlipAdd')
            || !$this->registerHook('payment')
            || !$this->registerHook('displayAdminOrderContentOrder')
        ) {
            return false;
        }

        return true;
    }


    /**
     * Remove module config
     *
     * @return boolean
     */
    private function deleteConfig()
    {
        foreach (CONFIG as $key => $value) {
            if ($key != 'payment_methods' && !Configuration::deleteByName($key)) {
                return false;
            }

            if ($key == 'payment_methods') {
                foreach ($value as $payment => $paymentConfig) {
                    foreach ($paymentConfig as $paymentConfigKey => $paymentConfigValue) {
                        if (!Configuration::deleteByName($paymentConfigKey)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Uninstall DataBase table
     * @return boolean if install was successfull
     */
    private function uninstallSQL()
    {
        $sql = array();
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "hyperpay_payment`";
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "hyperpay_capture`";
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "hyperpay_refund`";
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "hyperpay_cards`";

        foreach ($sql as $q) {
            if (!DB::getInstance()->execute($q)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove the module and it's configurations
     *
     * @return boolean
     */
    public function uninstall()
    {
        if (!$this->deleteConfig()) {
            return false;
        }

        //Uninstall DataBase
        // if (!$this->uninstallSQL()) {
        //     return false;
        // }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Create module tables
     *
     * @return void
     */
    private function installSQL()
    {
        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hyperpay_payment` (
              `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              `id_order` INT(11),
              `id_cart` INT(11),

              `payment_id` varchar(255) NOT NULL,
              `payment_type` varchar(255) NOT NULL,
              `amount` varchar(255) NOT NULL,
              `currency` varchar(255) NOT NULL,

              `payment_method` VARCHAR(255),
              `total_paid` FLOAT(11),
              `payment_status` VARCHAR(255),
              `total_prestashop` FLOAT(11),

              `date_add` DATETIME,
              `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_;

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hyperpay_capture` (
            `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `id_order` INT(11),
            `capture_id` VARCHAR(255),
            `payment_method` VARCHAR(255),
            `capture_amount` FLOAT(11),
            `result` VARCHAR(255),
            `date_add` DATETIME,
            `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_;

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hyperpay_refund` (
            `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `id_order` INT(11),
            `refund_id` VARCHAR(255),
            `payment_method` VARCHAR(255),
            `refund_amount` FLOAT(11),
            `result` VARCHAR(255),
            `date_add` DATETIME,
            `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_;

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "hyperpay_card` (
            `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `id_customer` INT(11),
            `payment_method` varchar(255),

            `registration_id` varchar(255) NOT NULL,

            `bin` varchar(255)  NULL,
            `last_4_digits` varchar(255)  NULL,
            `holder` varchar(255)  NULL,
            `expiry_month` varchar(255)  NULL,
            `expiry_year` varchar(255)  NULL,


            `date_add` DATETIME,
            `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_;

        foreach ($sql as $q) {
            if (!DB::getInstance()->execute($q)) {
                return false;
            }
        }

        return true;
    }
}
