<?php
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Helper.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCard.php');

class Request
{

    public static function getPaymentStatus($settingsKey, $id)
    {
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");

        $testMode = Configuration::get("HYPERPAY_MODE");

        // check if test or live
        if ($testMode == "LIVE") {
            $url = Configuration::get("HYPERPAY_LIVE_URL");
        } else {
            $url = Configuration::get("HYPERPAY_TEST_URL");
        }
        $url = "{$url}checkouts/$id/payment?entityId=$entityID";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $accessToken = Configuration::get("HYPERPAY_ACCESS_TOKEN");
        if ($accessToken != '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization:Bearer $accessToken"
            ]);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE"); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);
        return $responseData;
    }

    public static function prepareCheckout($settingsKey, $paymentMethod)
    {
        $merchantTransactionId = Context::getContext()->cart->id . '_' . time();
        $amount = Context::getContext()->cart->getOrderTotal();
        $currency = Configuration::get("{$settingsKey}_CURRENCY");
        $convertedAmount = HPHelper::convertPrice($amount, $settingsKey);
        $paymentType = Configuration::get("{$settingsKey}_ACTION");
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");

        $testMode = Configuration::get("HYPERPAY_MODE");

        // check if test or live
        if ($testMode == "LIVE") {
            $url = Configuration::get("HYPERPAY_LIVE_URL");
        } else {
            $url = Configuration::get("HYPERPAY_TEST_URL");
            // round the amount because test environment doesn't handle fractions well for some reason
            $convertedAmount = round($convertedAmount);
        }
        $url = "{$url}checkouts";
        $data = "entityId=$entityID" .
            "&amount=$convertedAmount" .
            "&currency=$currency" .
            "&paymentType=$paymentType" .
            "&merchantTransactionId=$merchantTransactionId";

        $data .= '&customParameters[branch_id]=1';
        $data .= '&customParameters[teller_id]=1';
        $data .= '&customParameters[device_id]=1';
        $data .= '&customParameters[bill_number]=' . $merchantTransactionId;
        $data .= '$customParameters[plugin]=prestashop';

        if ($testMode != "LIVE") {
            $data .= "&testMode=$testMode";
        }


        $customerCards = HyperpayCard::getCustomerCards(Context::getContext()->customer->id, $paymentMethod) ?: [];

        if (!empty($customerCards)) {
            $customerCards = array_map(function ($card, $index) {
                return "registrations[$index].id={$card['registration_id']}";
            }, $customerCards, array_keys($customerCards));


            $customerCards = implode("&", $customerCards);

            $data .= "&" . $customerCards;
        }

        $data .= Request::getRequestAdditionalInfo($settingsKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $accessToken = Configuration::get("HYPERPAY_ACCESS_TOKEN");
        if ($accessToken != '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization:Bearer $accessToken"
            ]);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE"); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);
        return $responseData;
    }

    private static function getRequestAdditionalInfo($settingsKey)
    {
        $billingAddress = new Address(Context::getContext()->cart->id_address_invoice);
        $shippingAddress = new Address(Context::getContext()->cart->id_address_delivery);
        $customer = Context::getContext()->customer;

        $connector = Configuration::get("{$settingsKey}_CONNECTOR");

        $data = '';

        $firstNameShipping = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->firstname));
        $surNameShipping = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->lastname));
        $countryShipping = (new Country($shippingAddress->id_country))->iso_code;
        $telShipping = preg_replace('/\s/', '', $shippingAddress->phone ?: $shippingAddress->phone_mobile);
        $postCodeShipping = preg_replace('/\s/', '', $shippingAddress->postcode);
        $streetShipping = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->address1));
        $cityShipping = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->city));
        $stateBilling = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->city));

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($stateBilling) == false)) {
            $data .= "&billing.state=" . $stateBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($cityShipping) == false)) {
            $data .= "&shipping.city=" . $cityShipping;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($countryShipping) == false)) {
            $data .= "&shipping.country=" . $countryShipping;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($postCodeShipping) == false)) {
            $data .= "&shipping.postcode=" . $postCodeShipping;
        }
        if (!($connector == 'migs' && HPHelper::isThisEnglishText($firstNameShipping) == false)) {
            $data .= "&shipping.customer.givenName=" . $firstNameShipping;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($surNameShipping) == false)) {
            $data .= "&shipping.customer.surname=" . $surNameShipping;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($telShipping) == false)) {
            $data .= "&shipping.customer.phone=" . $telShipping;
        }
        if (!($connector == 'migs' && HPHelper::isThisEnglishText($streetShipping) == false)) {
            $data .= "&shipping.street1=" . $streetShipping;
            $data .= "&shipping.street2=" . $streetShipping;
        }


        $firstNameBilling = preg_replace('/\s/', '', str_replace("&", "", $billingAddress->firstname));
        $surNameBilling = preg_replace('/\s/', '', str_replace("&", "", $billingAddress->lastname));
        $countryBilling = (new Country($billingAddress->id_country))->iso_code;
        $telBilling = preg_replace('/\s/', '', $billingAddress->phone ?: $billingAddress->phone_mobile);
        $postCodeBilling = preg_replace('/\s/', '', $billingAddress->postcode);
        $streetBilling = preg_replace('/\s/', '', str_replace("&", "", $billingAddress->address1));
        $cityBilling = preg_replace('/\s/', '', str_replace("&", "", $billingAddress->city));
        $stateBilling = preg_replace('/\s/', '', str_replace("&", "", $shippingAddress->city));

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($stateBilling) == false)) {
            $data .= "&billing.state=" . $stateBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($cityBilling) == false)) {
            $data .= "&billing.city=" . $cityBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($countryBilling) == false)) {
            $data .= "&billing.country=" . $countryBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($firstNameBilling) == false)) {
            $data .= "&customer.givenName=" . $firstNameBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($telBilling) == false)) {
            $data .= "&customer.phone=" . $telBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($postCodeBilling) == false)) {
            $data .= "&billing.postcode=" . $postCodeBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($surNameBilling) == false)) {
            $data .= "&customer.surname=" . $surNameBilling;
        }

        if (!($connector == 'migs' && HPHelper::isThisEnglishText($streetBilling) == false)) {
            $data .= "&billing.street1=" . $streetShipping;
            $data .= "&billing.street2=" . $streetShipping;
        }

        if (isset($customer)) {
            $customerEmail = preg_replace('/\s/', '', str_replace("&", "", $customer->email));
            $data .= "&customer.email=" . $customerEmail;
        } else {
            $data .= "customer.email=''";
        }

        return $data;
    }


    public static function sendRefundRequest(HyperpayPayment $payment, $amount)
    {
        return Request::sendBackOfficeRequest('RF', $payment, $amount);
    }

    public static function sendCaptureRequest(HyperpayPayment $payment, $amount)
    {
        return Request::sendBackOfficeRequest('CP', $payment, $amount);
    }

    private static function sendBackOfficeRequest($operation, HyperpayPayment $payment, $amount)
    {
        $settingsKey = "HYPERPAY_METHOD_{$payment->payment_method}";

        $currency = Configuration::get("{$settingsKey}_CURRENCY");
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");

        $testMode = Configuration::get("HYPERPAY_MODE");

        // check if test or live
        if ($testMode == "LIVE") {
            $url = Configuration::get("HYPERPAY_LIVE_URL");
        } else {
            $url = Configuration::get("HYPERPAY_TEST_URL");
            // round the amount because test environment doesn't handle fractions well for some reason
            $amount = round($amount);
        }
        $url = "{$url}payments/{$payment->payment_id}";
        $data = "entityId=$entityID" .
            "&amount=$amount" .
            "&currency=$currency" .
            "&paymentType=$operation";

        if ($testMode != "LIVE") {
            $data .= "&testMode=$testMode";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $accessToken = Configuration::get("HYPERPAY_ACCESS_TOKEN");
        if ($accessToken != '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization:Bearer $accessToken"
            ]);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE"); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);
        return $responseData;
    }
}
