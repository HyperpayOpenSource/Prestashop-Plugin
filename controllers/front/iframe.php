<?php

include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Request.php');

class HyperpayIframeModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {

        $paymentMethod = CONFIG['payment_methods'][Tools::getValue('method')]  ? Tools::getValue('method') : false;
        $settingsKey = "HYPERPAY_METHOD_" . $paymentMethod;

        // validate cart
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active || !$cart->nbproducts()) {
            Tools::redirect('');
        }

        // validate customer
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('');

        if (!$paymentMethod || !Configuration::get("{$settingsKey}_ENABLED")) {
            Tools::redirect('index.php?controller=order');
        }

        $cardStyle = Configuration::get("HYPERPAY_STYLE");
        $cardCss = Configuration::get("HYPERPAY_CSS");

        parent::initContent();

        $responseData = json_decode(Request::prepareCheckout($settingsKey, $paymentMethod));

        $testMode = Configuration::get("HYPERPAY_MODE");

        $nonce = random_int(0, 99999);
        // check if test or live
        if ($testMode == "LIVE") {
            $url = Configuration::get("HYPERPAY_LIVE_URL");
        } else {
            $url = Configuration::get("HYPERPAY_TEST_URL");
        }

        $cspMeta = '<meta http-equiv="Content-Security-Policy" content="' .
            'style-src \'self\' ' . $url . ' \'unsafe-inline\'; ' .
            'frame-src \'self\' ' . $url . '; ' .
            'script-src \'self\' ' . $url . ' https://pay.google.com/gp/p/ui/pay \'nonce-' . $nonce . '\'; ' .
            'connect-src \'self\' ' . $url . '; ' .
            'img-src \'self\' ' . $url . ';">';

        // Assign it to smarty so the hook can see it
        $this->context->smarty->assign('hyperpay_csp_tag', $cspMeta);

        $this->context->smarty->assign([
            'src' => $this->context->link->getModuleLink($this->module->name, 'validation', ['method' => $paymentMethod], true),
            'checkoutId' => isset($responseData->id) ? $responseData->id : null,
            'integrity' => isset($responseData->integrity) ? $responseData->integrity : null,
            'cardStyle' => $cardStyle,
            'cardCss' => $cardCss,
            'brands' => PAYMENT_BRANDS[$paymentMethod],
            'locale' => $this->context->language->iso_code,
            'originUrl' => $url,
            'preview' => '',
            'NONCE_ID' => $nonce,
            'entityId' => Configuration::get("{$settingsKey}_ENTITY_ID"),
            'googlePayMerchantId' => Configuration::get("{$settingsKey}_GOOGLE_PAY_MERCHANT_ID") ?? '',
        ]);


        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:hyperpay/views/templates/front/card.tpl');
        } else {
            $this->setTemplate('card16.tpl');
        }
    }
}
