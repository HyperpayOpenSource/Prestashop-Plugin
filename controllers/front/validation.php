<?php

include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayPayment.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCard.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Request.php');

class HyperpayValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $errors = [];

        // validate cart
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // validate customer
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');


        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        $paymentMethod = Tools::getValue('method');
        $settingsKey = "HYPERPAY_METHOD_{$paymentMethod}";
        foreach (Module::getPaymentModules() as $module) {
            // Check payment method is defined and enabled in module
            if ($module['name'] == 'hyperpay' && CONFIG['payment_methods'][$paymentMethod] && Configuration::get("{$settingsKey}_ENABLED")) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $errors['Payment Method'] = 'This payment method is not available.';
        }

        $currency = $this->context->currency;

        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        // after all checks are successfull we send the get payment status
        // also all these checks need to be done before  creating the iframe
        $paymentStatus =  json_decode(Request::getPaymentStatus($settingsKey, Tools::getValue('id')), true);

        $status = "";
        if ($paymentStatus == null || !isset($paymentStatus['result']) || !isset($paymentStatus['result']['code'])) {
            $status = 'fail';
            $errors['description']  = is_string($paymentStatus) ? $paymentStatus : "didn't get a response or got an invalid response";
        } elseif (
            preg_match('/^(000\.400\.0|000\.400\.100)/', $paymentStatus['result']['code'])
            || preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $paymentStatus['result']['code'])
        ) {
            $status = 'success';
        } else {
            $errors['code'] = $paymentStatus['result']['code'];
            $errors['description'] = isset($paymentStatus['result']) && isset($paymentStatus['result']['description']) ? $paymentStatus['result']['description'] : "missing description";
            $status = 'fail';

            if (isset($paymentStatus['card']['bin'])) {
              $blackBins = $this->getMadaBlackBins();
              $searchBin = $paymentStatus['card']['bin'];
              if (in_array($searchBin,$blackBins)) {
                if ($this->context->language->iso_code == 'ar') {
                  $errors['description'] = 'عذرا! يرجى اختيار خيار الدفع "مدى" لإتمام عملية الشراء بنجاح.';
                }else{
                  $errors['description'] = 'Sorry! Please select "mada" payment option in order to be able to complete your purchase successfully.';
                }

            }
          }
          
        }

        if ($status == 'success') {
            if (
                isset($paymentStatus) &&
                isset($paymentStatus['registrationId']) &&
                !HyperpayCard::isRegistrationIDExists($this->context->customer->id, $paymentStatus['registrationId'], $paymentMethod)
            ) {
                $newCard = new HyperpayCard();
                $newCard->id_customer = $this->context->customer->id;
                $newCard->registration_id = $paymentStatus['registrationId'];
                $newCard->payment_method = $paymentMethod;
                if (!empty($paymentStatus['card'])) {
                    $newCard->bin = !empty($paymentStatus['card']['bin'])  ? $paymentStatus['card']['bin'] : '';
                    $newCard->last_4_digits = !empty($paymentStatus['card']['last4Digits']) ? $paymentStatus['card']['last4Digits'] : '';
                    $newCard->holder = !empty($paymentStatus['card']['holder']) ? $paymentStatus['card']['holder'] : '';
                    $newCard->expiry_month = !empty($paymentStatus['card']['expiryMonth']) ? $paymentStatus['card']['expiryMonth'] : '';
                    $newCard->expiry_year = !empty($paymentStatus['card']['expiryYear']) ? $paymentStatus['card']['expiryYear'] : '';
                }
                $newCard->save();
            }

            $this->module->validateOrder($cart->id,  Configuration::get('HYPERPAY_DEFAULT_STATUS'), $total, Configuration::get("{$settingsKey}_TITLE"), NULL, [], (int) $currency->id, false, $customer->secure_key);
        }


        $payment = new HyperpayPayment();
        $payment->id_order = $this->module->currentOrder;
        $payment->id_cart = $cart->id;
        $payment->payment_id = isset($paymentStatus['id']) ? $paymentStatus['id'] : '';
        $payment->payment_type = isset($paymentStatus['paymentType']) ? $paymentStatus['paymentType'] : '';
        $payment->amount = $total;
        $payment->currency = $currency->iso_code;
        $payment->payment_method = $paymentMethod;
        $payment->total_paid = (float) $total;
        $payment->payment_status = $status;
        $payment->total_prestashop = (float) $total;
        $payment->save();

        // in case everything is good and successfull do the following two lines
        // validate order converts the cart to an order and saves it
        if ($status == 'success') {
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
        } else {
            $this->context->smarty->assign([
                'params' => $errors,
            ]);

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $this->setTemplate('module:hyperpay/views/templates/front/payment_return.tpl');
            } else {
                $this->setTemplate('payment_return16.tpl');
            }
        }
    }

    private function getMadaBlackBins()
{
  return array(
    "588845",
    "440647",
    "440795",
    "446404",
    "457865",
    "968208",
    "588846",
    "493428",
    "539931",
    "558848",
    "557606",
    "968210",
    "636120",
    "417633",
    "468540",
    "468541",
    "468542",
    "468543",
    "968201",
    "446393",
    "409201",
    "458456",
    "484783",
    "968205",
    "462220",
    "455708",
    "588848",
    "455036",
    "968203",
    "486094",
    "486095",
    "486096",
    "504300",
    "440533",
    "489318",
    "489319",
    "445564",
    "968211",
    "401757",
    "410685",
    "406996",
    "432328",
    "428671",
    "428672",
    "428673",
    "968206",
    "446672",
    "543357",
    "434107",
    "407197",
    "407395",
    "412565",
    "431361",
    "604906",
    "521076",
    "588850",
    "968202",
    "529415",
    "535825",
    "543085",
    "524130",
    "554180",
    "549760",
    "588849",
    "968209",
    "524514",
    "529741",
    "537767",
    "535989",
    "536023",
    "513213",
    "520058",
    "585265",
    "588983",
    "588982",
    "589005",
    "508160",
    "531095",
    "530906",
    "532013",
    "605141",
    "968204",
    "422817",
    "422818",
    "422819",
    "428331",
    "483010",
    "483011",
    "483012",
    "589206",
    "968207",
    "419593",
    "439954",
    "530060",
    "531196"
  );
}

}
