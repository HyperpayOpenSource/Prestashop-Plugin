<?php
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Helper.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCard.php');

class Request
{
    /**
     * Get Payment Status from Hyperpay
     */
    public static function getPaymentStatus($settingsKey, $id)
    {
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");
        $testMode = Configuration::get("HYPERPAY_MODE");

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);

        return $responseData;
    }

    /**
     * Prepare Checkout and validate user data
     */
    public static function prepareCheckout($settingsKey, $paymentMethod)
    {
        $merchantTransactionId = Context::getContext()->cart->id . '_' . time();
        $amount = Context::getContext()->cart->getOrderTotal();
        $currency = Configuration::get("{$settingsKey}_CURRENCY");
        $convertedAmount = HPHelper::convertPrice($amount, $settingsKey);
        $paymentType = Configuration::get("{$settingsKey}_ACTION");
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");

        $testMode = Configuration::get("HYPERPAY_MODE");

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
        $data .= '&customParameters[plugin]=prestashop'; // Fixed typo here
        $data .= '&integrity=true'; // Fixed typo here
        if($paymentMethod === 'AANI'){
            $billingAddress = new Address(Context::getContext()->cart->id_address_invoice);
            $data .="&customer.mobile=" . urlencode($billingAddress->phone);      
        }
        if ($testMode != "LIVE") {
            $data .= "&testMode=EXTERNAL";
            $data .= "&customParameters[3DS2_enrolled]=true";
        }

        // Handle saved cards
        $customerCards = HyperpayCard::getCustomerCards(Context::getContext()->customer->id, $paymentMethod) ?: [];

        if (!empty($customerCards)) {
            $customerCardsStr = array_map(function ($card, $index) {
                return "registrations[$index].id={$card['registration_id']}";
            }, $customerCards, array_keys($customerCards));

            $data .= "&" . implode("&", $customerCardsStr);
        }

        // Get and Validate Additional Info
        $additionalInfo = Request::getRequestAdditionalInfo($settingsKey);

        if (is_array($additionalInfo) && isset($additionalInfo['error'])) {
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => $additionalInfo['error']
            ]));
        }

        $data .= $additionalInfo;

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);

        return $responseData;
    }

    /**
     * Validates and cleans billing/customer info
     */
    private static function getRequestAdditionalInfo($settingsKey)
    {
        $billingAddress = new Address(Context::getContext()->cart->id_address_invoice);
        $context = Context::getContext();
        $isoCode = $context->language->iso_code;

        $customer = Context::getContext()->customer;

        // Validation helper to ensure data exists and is clean
        $getRequired = function ($value, $fieldName) use ($isoCode) {
            $clean = trim(str_replace("&", "", $value));

            if (empty($clean)) {
                throw new Exception(
                    sprintf(
                        $isoCode == 'ar'
                            ? "الحقل مطلوب %s"
                            : "Missing required billing field: %s",
                        $fieldName
                    )
                );
            }

            return urlencode($clean);
        };

        try {
            $data = "";

            // 1. Email Validation
            if (!isset($customer->email) || !Validate::isEmail($customer->email)) {
                throw new Exception($isoCode  == 'ar' ? "البريد الإلكتروني مطلوب" : "A valid customer email is required.");
            }
            $data .= "&customer.email=" . urlencode($customer->email);
            $data .= "&customer.givenName=" . urlencode($customer->firstname);
            $data .= "&customer.surname=" . urlencode($customer->lastname);

            // 2. Billing Address Validation
            $data .= "&billing.street1=" . $getRequired($billingAddress->address1, 'Street');
            $data .= "&billing.city="    . $getRequired($billingAddress->city, 'City');
            $data .= "&billing.postcode=" . $getRequired($billingAddress->postcode, 'Postcode');

            if (!$customer->firstname || !$customer->lastname) {
                throw new Exception($isoCode  == 'ar' ? "الاسم الأول والأخير مطلوبان" : "Customer first name and last name are required.");
            }

            if (!(HPHelper::isThisEnglishText($customer->firstname))) {
                throw new Exception($isoCode  == 'ar' ? "فقط الأحرف الإنجليزية مسموح بها في حقل الاسم الأول" : "only English characters are allowed in first name field.");
            }

            if (!(HPHelper::isThisEnglishText($customer->lastname))) {
                throw new Exception($isoCode  == 'ar' ? "فقط الأحرف الإنجليزية مسموح بها في حقل الاسم الأخير" : "only English characters are allowed in last name field.");
            }

            // 3. Country Validation (ISO Alpha-2)
            $country = new Country($billingAddress->id_country);
            if (!$country->iso_code || strlen($country->iso_code) !== 2) {
                throw new Exception($isoCode  == 'ar' ? "البلد يجب أن يكون في تنسيق ISO Alpha-2" : "Billing country must be in ISO Alpha-2 format.");
            }
            $data .= "&billing.country=" . strtoupper($country->iso_code);

            // 4. State Validation
            if ($billingAddress->id_state) {
                $state = new State($billingAddress->id_state);
                $data .= "&billing.state=" . urlencode($state->name);
            } else {
                // Hyperpay often requires a state value; fallback to city name if state is not set
                $data .= "&billing.state=" . urlencode($billingAddress->city);
            }

            return $data;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
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

        if ($testMode == "LIVE") {
            $url = Configuration::get("HYPERPAY_LIVE_URL");
        } else {
            $url = Configuration::get("HYPERPAY_TEST_URL");
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
                "Authorization: Bearer $accessToken"
            ]);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $testMode == "LIVE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            $responseData = '"' . curl_error($ch) . '"';
        }
        curl_close($ch);

        return $responseData;
    }
}
