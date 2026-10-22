<?php
include_once(_PS_MODULE_DIR_ . 'hyperpay/helpers/Helper.php');

class TabbyParams
{
    /**
     * Tabby parameters of a server to server payment: customer, addresses, cart items and buyer history
     *
     * @param Cart $cart
     * @param bool $testMode
     * @param string $settingsKey used to convert the cart amounts to the configured currency
     *
     * @return array
     *
     * @throws Exception when the customer or billing data is not valid
     */
    public static function build(Cart $cart, $testMode, $settingsKey)
    {
        $customer = new Customer($cart->id_customer);
        $billingAddress = new Address($cart->id_address_invoice);
        $deliveryAddress = new Address($cart->id_address_delivery);
        $deliveryCountry = new Country($deliveryAddress->id_country);
        $deliveryState = $deliveryAddress->id_state ? (new State($deliveryAddress->id_state))->name : $deliveryAddress->city;

        $data = Request::customerBillingArray($cart);

        $data['customer.mobile'] = $billingAddress->phone_mobile ?: $billingAddress->phone;
        $data['shipping.street1'] = trim($deliveryAddress->address1);
        $data['shipping.city'] = trim($deliveryAddress->city);
        $data['shipping.state'] = trim($deliveryState);
        $data['shipping.country'] = strtoupper($deliveryCountry->iso_code);
        $data['shipping.postcode'] = trim($deliveryAddress->postcode);
        $data['shipping.cost'] = HPHelper::convertPrice($cart->getOrderTotal(false, Cart::ONLY_SHIPPING), $settingsKey);
        $data['taxAmount'] = HPHelper::convertPrice($cart->getOrderTotal(true, Cart::BOTH) - $cart->getOrderTotal(false, Cart::BOTH), $settingsKey);
        $data['locale'] = (new Language($cart->id_lang))->iso_code;
        $data['customParameters[description]'] = "Cart #{$cart->id}";
        $data['customParameters[discount_amount]'] = HPHelper::convertPrice($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS), $settingsKey);

        if ($testMode) {
            $data['customer.email'] = 'card.success@tabby.ai';
            $data['customer.mobile'] = '0500000001';
        }

        $data = array_merge($data, TabbyParams::cartItems($cart, $settingsKey));

        $previousOrders = array_values(array_filter(Order::getCustomerOrders($customer->id) ?: [], function ($order) use ($cart) {
            return (int) $order['id_cart'] !== (int) $cart->id;
        }));

        $data = array_merge($data, TabbyParams::buyerHistory($customer, $previousOrders));

        $data['customParameters[attachment]'] = json_encode([
            'body' => [
                'payment_history_simple' => TabbyParams::paymentHistorySimple($customer, $previousOrders),
            ],
            'content_type' => 'application/vnd.tabby.v1+json',
        ], JSON_UNESCAPED_SLASHES);

        return $data;
    }

    /**
     * cart.items[i].* parameters, the price is the unit price before product reductions
     * and the discount is the product reduction as a percentage of it
     *
     * @param Cart $cart
     * @param string $settingsKey
     *
     * @return array
     */
    private static function cartItems(Cart $cart, $settingsKey)
    {
        $data = [];
        $link = Context::getContext()->link;

        foreach (array_values($cart->getProducts()) as $index => $product) {
            $quantity = max(1, (int) (isset($product['cart_quantity']) ? $product['cart_quantity'] : $product['quantity']));
            $idProductAttribute = isset($product['id_product_attribute']) ? (int) $product['id_product_attribute'] : 0;

            $priceWithReduction = (float) (isset($product['price_with_reduction_without_tax']) ? $product['price_with_reduction_without_tax'] : $product['price']);
            $priceWithoutReduction = (float) (isset($product['price_without_reduction_without_tax'])
                ? $product['price_without_reduction_without_tax']
                : Product::getPriceStatic(
                    (int) $product['id_product'],
                    false,
                    $idProductAttribute,
                    6,
                    null,
                    false,
                    false,
                    $quantity,
                    false,
                    (int) $cart->id_customer,
                    (int) $cart->id,
                    (int) $cart->id_address_delivery
                ));
            $price = max($priceWithReduction, $priceWithoutReduction);
            $discount = $price > 0 ? ($price - $priceWithReduction) / $price * 100 : 0;

            $data["cart.items[$index].name"] = Tools::substr($product['name'], 0, 255);
            $data["cart.items[$index].price"] = HPHelper::convertPrice($price, $settingsKey);
            $data["cart.items[$index].quantity"] = $quantity;
            $data["cart.items[$index].discount"] = number_format(min(max($discount, 0), 100), 2, '.', '');
            $data["cart.items[$index].sku"] = !empty($product['reference']) ? $product['reference'] : (string) $product['id_product'];
            $data["cart.items[$index].description"] = Tools::substr(trim(strip_tags(isset($product['description_short']) ? $product['description_short'] : '')), 0, 255);
            $data["cart.items[$index].productUrl"] = $link->getProductLink((int) $product['id_product'], null, null, null, (int) $cart->id_lang, null, $idProductAttribute);

            // carts fall back to "<iso>-default" when the product has no image
            if (!empty($product['id_image']) && preg_match('/^\d+(-\d+)?$/', $product['id_image'])) {
                $data["cart.items[$index].imageUrl"] = $link->getImageLink($product['link_rewrite'], $product['id_image']);
            }

            $data["cart.items[$index].type"] = !empty($product['category']) ? $product['category'] : 'general';
        }

        return $data;
    }

    /**
     * Tabby risk-scoring params built from the customer account and the last 10 previous orders
     *
     * @param Customer $customer
     * @param array $previousOrders rows of Order::getCustomerOrders(), newest first
     *
     * @return array
     */
    private static function buyerHistory(Customer $customer, array $previousOrders)
    {
        $orderStates = [];

        $orderHistory = array_map(function ($order) use (&$orderStates) {
            $idOrderState = (int) $order['current_state'];
            if (!isset($orderStates[$idOrderState])) {
                $orderStates[$idOrderState] = new OrderState($idOrderState);
            }

            return [
                'purchased_at' => gmdate('Y-m-d\TH:i:s\Z', strtotime($order['date_add'])),
                'amount' => number_format((float) $order['total_paid_tax_incl'], 2, '.', ''),
                'status' => TabbyParams::mapOrderStatus($orderStates[$idOrderState]),
                'payment_method' => strpos((string) $order['module'], 'cashondelivery') !== false ? 'cod' : 'card',
            ];
        }, array_slice($previousOrders, 0, 10));

        $completedCount = count(array_filter($orderHistory, function ($history) {
            return $history['status'] === 'complete';
        }));

        $buyerHistory = [
            'customParameters[buyer_history_registered_since]' => gmdate('Y-m-d\TH:i:s\Z', strtotime($customer->date_add)),
            'customParameters[buyer_history_loyalty_level]' => (string) $completedCount,
            'customParameters[buyer_history_wishlist_count]' => '0',
            'customParameters[buyer_history_is_social_networks_connected]' => '0',
            'customParameters[buyer_history_is_phone_number_verified]' => '0',
            'customParameters[buyer_history_is_email_verified]' => '0',
        ];

        // HyperPay rejects an empty order_history, so first-time buyers omit it
        if ($orderHistory) {
            $buyerHistory['customParameters[order_history]'] = json_encode($orderHistory, JSON_UNESCAPED_SLASHES);
        }

        return $buyerHistory;
    }

    /**
     * Map a PrestaShop order state to a Tabby order history status
     *
     * @param OrderState $orderState
     *
     * @return string
     */
    private static function mapOrderStatus(OrderState $orderState)
    {
        $idOrderState = (int) $orderState->id;

        if (in_array($idOrderState, [(int) Configuration::get('PS_OS_CANCELED'), (int) Configuration::get('PS_OS_ERROR')], true)) {
            return 'canceled';
        }

        if ($idOrderState === (int) Configuration::get('PS_OS_REFUND')) {
            return 'refunded';
        }

        if ($idOrderState === (int) Configuration::get('PS_OS_DELIVERED')) {
            return 'complete';
        }

        if ($orderState->shipped || $orderState->paid) {
            return 'processing';
        }

        if (Validate::isLoadedObject($orderState) && !$orderState->logable) {
            return 'new';
        }

        return 'unknown';
    }

    /**
     * First and last paid purchase of the customer, excluding the current cart
     *
     * @param Customer $customer
     * @param array $previousOrders rows of Order::getCustomerOrders()
     *
     * @return array
     */
    private static function paymentHistorySimple(Customer $customer, array $previousOrders)
    {
        $paidDates = array_map(function ($order) {
            return strtotime($order['date_add']);
        }, array_filter($previousOrders, function ($order) {
            return (int) $order['valid'] === 1;
        }));

        $history = [
            'unique_account_identifier' => (string) $customer->id,
            'paid_before_flag' => (bool) $paidDates,
        ];

        if ($paidDates) {
            $history['date_of_first_paid_purchase'] = gmdate('Y-m-d', min($paidDates));
            $history['date_of_last_paid_purchase'] = gmdate('Y-m-d', max($paidDates));
        }

        return $history;
    }
}
