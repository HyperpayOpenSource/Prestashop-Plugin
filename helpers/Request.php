<?php
include_once(_PS_MODULE_DIR_ . 'hyperpay/config.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Helper.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/classes/HyperpayCard.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/TabbyParams.php');
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/CliqParams.php');

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
        try {
            $data = "";
            foreach (Request::customerBillingArray(Context::getContext()->cart) as $key => $value) {
                $data .= "&$key=" . urlencode($value);
            }

            return $data;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Customer and billing parameters of the cart, cleaned and validated
     *
     * @param Cart $cart
     *
     * @return array
     *
     * @throws Exception when a required field is missing or invalid, the message is shown to the customer
     */
    public static function customerBillingArray(Cart $cart)
    {
        $billingAddress = new Address($cart->id_address_invoice);
        $isoCode = Context::getContext()->language->iso_code;

        $customer = new Customer($cart->id_customer);

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

            return $clean;
        };

        $data = [];

        // 1. Email Validation
        if (!isset($customer->email) || !Validate::isEmail($customer->email)) {
            throw new Exception($isoCode  == 'ar' ? "البريد الإلكتروني مطلوب" : "A valid customer email is required.");
        }
        $data['customer.email'] = $customer->email;
        $data['customer.givenName'] = $customer->firstname;
        $data['customer.surname'] = $customer->lastname;

        // 2. Billing Address Validation
        $data['billing.street1'] = $getRequired($billingAddress->address1, 'Street');
        $data['billing.city'] = $getRequired($billingAddress->city, 'City');
        $data['billing.postcode'] = $getRequired($billingAddress->postcode, 'Postcode');

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
        $data['billing.country'] = strtoupper($country->iso_code);

        // 4. State Validation
        if ($billingAddress->id_state) {
            $state = new State($billingAddress->id_state);
            $data['billing.state'] = $state->name;
        } else {
            // Hyperpay often requires a state value; fallback to city name if state is not set
            $data['billing.state'] = $billingAddress->city;
        }

        return $data;
    }

    /**
     * Scheme and host of the configured gateway url, resource paths returned by the gateway are relative to it
     *
     * @return string
     */
    public static function baseUrl()
    {
        $url = Configuration::get("HYPERPAY_MODE") == "LIVE"
            ? Configuration::get("HYPERPAY_LIVE_URL")
            : Configuration::get("HYPERPAY_TEST_URL");

        $parts = parse_url($url);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $parts['scheme'] . '://' . $parts['host'] . $port;
    }

    /**
     * Parameters of a server to server payment (POST /v1/payments)
     *
     * @param string $settingsKey
     * @param string $brand
     * @param Cart $cart
     * @param string $returnUrl
     *
     * @return array
     *
     * @throws Exception when the customer data is not valid for the brand
     */
    public static function serverToServerParams($settingsKey, $brand, Cart $cart, $returnUrl)
    {
        $testMode = Configuration::get("HYPERPAY_MODE") != "LIVE";
        $merchantTransactionId = $cart->id . 'I' . random_int(11111111, 99999999);

        $data = [
            'entityId' => Configuration::get("{$settingsKey}_ENTITY_ID"),
            'amount' => HPHelper::convertPrice($cart->getOrderTotal(true, Cart::BOTH), $settingsKey),
            'currency' => Configuration::get("{$settingsKey}_CURRENCY"),
            'paymentType' => 'DB',
            'merchantTransactionId' => $merchantTransactionId,
            'paymentBrand' => $brand,
            'shopperResultUrl' => $returnUrl,
            'customParameters[branch_id]' => '1',
            'customParameters[teller_id]' => '1',
            'customParameters[device_id]' => '1',
            'customParameters[bill_number]' => $merchantTransactionId,
            'customParameters[plugin]' => 'prestashop',
        ];

        if ($testMode) {
            $data['testMode'] = 'EXTERNAL';
        }

        if ($brand === 'TABBY') {
            $data = array_merge($data, TabbyParams::build($cart, $testMode, $settingsKey));
        } elseif ($brand === 'CLIQ') {
            $data = array_merge($data, CliqParams::build((new Language($cart->id_lang))->iso_code));
        }

        return $data;
    }

    /**
     * Start a server to server payment, the response holds the redirect to the brand payment page
     *
     * @param string $settingsKey
     * @param string $brand
     * @param Cart $cart
     * @param string $returnUrl
     *
     * @return array decoded gateway response
     */
    public static function serverToServerPayment($settingsKey, $brand, Cart $cart, $returnUrl)
    {
        try {
            $data = Request::serverToServerParams($settingsKey, $brand, $cart, $returnUrl);
        } catch (Exception $e) {
            return ['result' => ['code' => '', 'description' => $e->getMessage()]];
        }

        $url = Configuration::get("HYPERPAY_MODE") == "LIVE"
            ? Configuration::get("HYPERPAY_LIVE_URL")
            : Configuration::get("HYPERPAY_TEST_URL");

        return Request::sendServerToServerRequest("{$url}payments", http_build_query($data));
    }

    /**
     * Get the status of a server to server payment from the resource path the gateway redirected back with
     *
     * @param string $settingsKey
     * @param string $resourcePath
     *
     * @return array decoded gateway response
     */
    public static function getResourceStatus($settingsKey, $resourcePath)
    {
        $entityID = Configuration::get("{$settingsKey}_ENTITY_ID");

        return Request::sendServerToServerRequest(Request::baseUrl() . $resourcePath . '?entityId=' . urlencode($entityID));
    }

    /**
     * Gateway error messages to show to the customer, keyed by the invalid field
     *
     * @param array $res decoded gateway response
     *
     * @return array
     */
    public static function extendedErrors(array $res)
    {
        $errors = [];

        if (isset($res['resultDetails']['ExtendedDescription']) && is_string($res['resultDetails']['ExtendedDescription'])) {
            $extended = json_decode(urldecode($res['resultDetails']['ExtendedDescription']), true);

            if (isset($extended['details']) && is_array($extended['details'])) {
                foreach ($extended['details'] as $index => $detail) {
                    if (isset($detail['error'])) {
                        $field = isset($detail['field']) ? $detail['field'] : $index;
                        $errors[isset($errors[$field]) ? "$field ($index)" : $field] = $detail['error'];
                    }
                }
            } elseif (isset($extended['message']) && is_string($extended['message'])) {
                $errors['description'] = $extended['message'];
            }
        }

        if (!$errors) {
            if (!empty($res['result']['code'])) {
                $errors['code'] = $res['result']['code'];
            }
            $errors['description'] = !empty($res['result']['description'])
                ? $res['result']['description']
                : "didn't get a response or got an invalid response";
        }

        return $errors;
    }

    /**
     * Map a gateway result code to the outcome of a server to server payment
     *
     * @param string $code
     *
     * @return string success|pending|fail
     */
    public static function serverToServerResult($code)
    {
        if (
            preg_match('/^(000\.400\.0|000\.400\.100)/', $code)
            || preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $code)
        ) {
            return 'success';
        }

        return preg_match(S2S_PENDING_PATTERN, $code) ? 'pending' : 'fail';
    }

    /**
     * Send a server to server request, GET when no body is given
     *
     * @param string $url
     * @param string|null $body url encoded body
     *
     * @return array decoded response, or a result description holding the transport error
     */
    private static function sendServerToServerRequest($url, $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . Configuration::get("HYPERPAY_ACCESS_TOKEN")
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Configuration::get("HYPERPAY_MODE") == "LIVE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $responseData = curl_exec($ch);
        $error = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);

        $response = $error ? null : json_decode($responseData, true);

        if (!is_array($response)) {
            return ['result' => ['code' => '', 'description' => $error ?: "didn't get a response or got an invalid response"]];
        }

        return $response;
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
