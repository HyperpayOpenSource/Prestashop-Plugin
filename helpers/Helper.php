<?php

class HPHelper
{
    /**
     * method to check if test passed is English
     *
     * @param  (string) $text to be checked.
     * 
     * @return (bool) true|false.
     */
    public function isThisEnglishText($text)
    {
        return preg_match("/\p{Latin}+/u", $text);
    }

    /**
     *  Convert the amount from the order currency to the config currency
     *
     * @param float $amount
     * @param string $settingsKey
     * @param HyperpayPayment $payment
     *
     * @return void
     */
    public static function convertPrice($amount, $settingsKey, HyperpayPayment $payment = null)
    {
        $currency = Configuration::get("{$settingsKey}_CURRENCY");
        $configCurrency = new Currency(Currency::getIdByIsoCode($currency));
        $userCurrency = $payment ? new Currency(Currency::getIdByIsoCode($payment->currency)) : Context::getContext()->currency;
        return number_format(Tools::convertPriceFull($amount, $userCurrency, $configCurrency), 2, '.', '');
    }
}