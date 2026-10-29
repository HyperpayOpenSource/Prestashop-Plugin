<?php

include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayPayment.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Request.php');

class HyperpayResultModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * Shopper return of a server to server payment, the session may be lost
     * after the brand payment page so the cart is loaded from the return url
     *
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $paymentMethod = Tools::getValue('method');
        if (!in_array($paymentMethod, SERVER_TO_SERVER_BRANDS, true) || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $settingsKey = "HYPERPAY_METHOD_{$paymentMethod}";

        $cart = new Cart((int) Tools::getValue('id_cart'));
        if (!Validate::isLoadedObject($cart)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        $key = (string) Tools::getValue('key');
        if (!Validate::isLoadedObject($customer) || $key === '' || !hash_equals((string) $customer->secure_key, $key)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->context->cart = $cart;
        $this->context->customer = $customer;
        $this->context->currency = new Currency((int) $cart->id_currency);
        $currency = $this->context->currency;

        // the shopper may come back more than once (refresh, back button)
        $this->redirectIfOrdered($cart, $customer);

        $resourcePath = (string) Tools::getValue('resourcePath');
        if (!preg_match('#^/v1/[A-Za-z0-9/._-]+$#', $resourcePath)) {
            $paymentStatus = ['result' => ['code' => '', 'description' => "didn't get a response or got an invalid response"]];
        } else {
            $paymentStatus = Request::getResourceStatus($settingsKey, $resourcePath);
        }

        // a resourcePath of another cart's payment must not pay this cart
        $merchantTransactionId = isset($paymentStatus['merchantTransactionId']) ? (string) $paymentStatus['merchantTransactionId'] : '';
        if ((int) strtok($merchantTransactionId, 'I') !== (int) $cart->id) {
            $paymentStatus['result'] = ['code' => '', 'description' => 'payment does not belong to this cart'];
        }

        $code = isset($paymentStatus['result']['code']) ? $paymentStatus['result']['code'] : '';
        $paymentId = isset($paymentStatus['id']) ? $paymentStatus['id'] : '';
        $status = $code !== '' ? Request::serverToServerResult($code) : 'fail';

        $idProcessedOrder = $paymentId !== '' ? $this->processedPaymentOrderId($paymentId) : 0;
        if ($idProcessedOrder) {
            $this->redirectToConfirmation($cart, $customer, $idProcessedOrder);
        }

        // Use the amount from Hyperpay's response as the authoritative amount (PS docs: do not use Cart->getOrderTotal())
        $hyperpayAmount = isset($paymentStatus['amount']) ? (float) $paymentStatus['amount'] : 0;
        $hyperpayResponseCurrency = isset($paymentStatus['currency']) ? $paymentStatus['currency'] : Configuration::get("{$settingsKey}_CURRENCY");

        // Convert Hyperpay's returned amount back to the cart currency for validateOrder()
        $hyperpayConfigCurrency = new Currency(Currency::getIdByIsoCode($hyperpayResponseCurrency));
        $total = (float) Tools::convertPriceFull($hyperpayAmount, $hyperpayConfigCurrency, $currency);

        if ($status != 'fail') {
            $this->redirectIfOrdered($cart, $customer);

            $idOrderState = $status == 'success' ? Configuration::get('HYPERPAY_DEFAULT_STATUS') : Configuration::get('HYPERPAY_OS_AWAITING');
            $this->module->validateOrder($cart->id, $idOrderState, $total, Configuration::get("{$settingsKey}_TITLE"), NULL, ['transaction_id' => $paymentId], (int) $currency->id, false, $customer->secure_key);
        } else {
            PrestaShopLogger::addLog(
                "HyperPay $paymentMethod payment $paymentId failed for cart {$cart->id}: $code " . (isset($paymentStatus['result']['description']) ? $paymentStatus['result']['description'] : ''),
                3,
                null,
                'Cart',
                (int) $cart->id,
                true
            );
        }

        $payment = new HyperpayPayment();
        $payment->id_order = $this->module->currentOrder;
        $payment->id_cart = $cart->id;
        $payment->payment_id = $paymentId;
        $payment->payment_type = isset($paymentStatus['paymentType']) ? $paymentStatus['paymentType'] : '';
        $payment->amount = $hyperpayAmount;
        $payment->currency = $hyperpayResponseCurrency;
        $payment->payment_method = $paymentMethod;
        $payment->total_paid = $hyperpayAmount;
        $payment->payment_status = $status;
        $payment->total_prestashop = $total;
        $payment->save();

        if ($status != 'fail') {
            $this->redirectToConfirmation($cart, $customer, $this->module->currentOrder);
        }

        $this->context->smarty->assign([
            'params' => Request::extendedErrors($paymentStatus),
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:hyperpay/views/templates/front/payment_return.tpl');
        } else {
            $this->setTemplate('payment_return16.tpl');
        }
    }

    /**
     * Send the shopper to the order confirmation when the cart already became an order
     *
     * @param Cart $cart
     * @param Customer $customer
     *
     * @return void
     */
    private function redirectIfOrdered(Cart $cart, Customer $customer)
    {
        $idOrder = method_exists('Order', 'getIdByCartId') ? Order::getIdByCartId($cart->id) : Order::getOrderByCartId($cart->id);

        if ($idOrder) {
            $this->redirectToConfirmation($cart, $customer, $idOrder);
        }
    }

    /**
     * Order of a payment that was already stored as succeeded or pending
     *
     * @param string $paymentId
     *
     * @return int
     */
    private function processedPaymentOrderId($paymentId)
    {
        $sql = new DbQuery();
        $sql->select('id_order');
        $sql->from('hyperpay_payment');
        $sql->where("payment_id = '" . pSQL($paymentId) . "'");
        $sql->where("payment_status IN ('success', 'pending')");
        $sql->where('id_order > 0');

        return (int) Db::getInstance()->getValue($sql);
    }

    private function redirectToConfirmation(Cart $cart, Customer $customer, $idOrder)
    {
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . (int) $idOrder . '&key=' . $customer->secure_key);
    }
}
