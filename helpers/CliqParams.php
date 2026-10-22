<?php

class CliqParams
{
    /**
     * CliQ parameters of a server to server payment, the connector reads the payment page language from the locale
     *
     * @param string $iso language iso code of the cart
     *
     * @return array
     */
    public static function build($iso)
    {
        return [
            'customParameters[locale]' => strpos((string) $iso, 'ar') === 0 ? 'ar' : 'en',
        ];
    }
}
