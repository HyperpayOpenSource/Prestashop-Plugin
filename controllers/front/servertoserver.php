<?php

include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Request.php');

class HyperpayServertoserverModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        $paymentMethod = Tools::getValue('method');
        $paymentMethod = isset(CONFIG['payment_methods'][$paymentMethod]) && in_array($paymentMethod, SERVER_TO_SERVER_BRANDS, true) ? $paymentMethod : false;
        $settingsKey = "HYPERPAY_METHOD_" . $paymentMethod;

        // validate cart
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active || !$cart->nbProducts()) {
            Tools::redirect('');
        }

        // validate customer
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('');

        if (!$paymentMethod || !Configuration::get("{$settingsKey}_ENABLED")) {
            Tools::redirect('index.php?controller=order');
        }

        parent::initContent();

        $returnUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'result',
            [
                'method' => $paymentMethod,
                'id_cart' => (int) $cart->id,
                'key' => $customer->secure_key,
            ],
            true
        );

        $response = Request::serverToServerPayment($settingsKey, $paymentMethod, $cart, $returnUrl);
        $code = isset($response['result']['code']) ? $response['result']['code'] : '';

        if (preg_match(S2S_PENDING_PATTERN, $code) && !empty($response['redirect']['url'])) {
            $this->context->smarty->assign([
                'redirectUrl' => $response['redirect']['url'],
                'redirectMethod' => isset($response['redirect']['method']) && Tools::strtoupper($response['redirect']['method']) === 'GET' ? 'GET' : 'POST',
                'redirectFields' => $this->redirectFields($response['redirect']),
            ]);

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $this->setTemplate('module:hyperpay/views/templates/front/redirect.tpl');
            } else {
                $this->setTemplate('redirect16.tpl');
            }
            return;
        }

        PrestaShopLogger::addLog(
            "HyperPay $paymentMethod payment initiation failed for cart {$cart->id}: $code " . (isset($response['result']['description']) ? $response['result']['description'] : ''),
            3,
            null,
            'Cart',
            (int) $cart->id,
            true
        );

        $this->context->smarty->assign([
            'params' => Request::extendedErrors($response),
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:hyperpay/views/templates/front/payment_return.tpl');
        } else {
            $this->setTemplate('payment_return16.tpl');
        }
    }

    /**
     * Hidden fields of the redirect form: the gateway parameters and the redirect url query,
     * a GET form drops the action query so it has to be sent as fields
     *
     * @param array $redirect
     *
     * @return array list of ['name' => ..., 'value' => ...]
     */
    private function redirectFields(array $redirect)
    {
        $fields = [];

        if (!empty($redirect['parameters']) && is_array($redirect['parameters'])) {
            foreach ($redirect['parameters'] as $parameter) {
                if (isset($parameter['name'])) {
                    $fields[] = ['name' => $parameter['name'], 'value' => isset($parameter['value']) ? $parameter['value'] : ''];
                }
            }
        }

        $query = parse_url($redirect['url'], PHP_URL_QUERY);
        if ($query) {
            foreach (explode('&', $query) as $pair) {
                $parts = explode('=', $pair, 2);
                $fields[] = ['name' => urldecode($parts[0]), 'value' => isset($parts[1]) ? urldecode($parts[1]) : ''];
            }
        }

        return $fields;
    }
}
