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
    public static function isThisEnglishText($text)
    {
        return preg_match('/^[^\\p{L}]*[A-Za-z][A-Za-z\\s\\d[:punct:]]*$/u', $text) === 1;
    }

    /**
     *  Convert the amount from the order currency to the config currency
     *
     * @param float $amount
     * @param string $settingsKey
     * @param HyperpayPayment $payment
     *
     * @return string
     */
    public static function convertPrice($amount, $settingsKey, HyperpayPayment $payment = null)
    {
        $currency = Configuration::get("{$settingsKey}_CURRENCY");
        $configCurrency = new Currency(Currency::getIdByIsoCode($currency));
        $userCurrency = $payment ? new Currency(Currency::getIdByIsoCode($payment->currency)) : Context::getContext()->currency;
        return number_format(Tools::convertPriceFull($amount, $userCurrency, $configCurrency), 2, '.', '');
    }
}