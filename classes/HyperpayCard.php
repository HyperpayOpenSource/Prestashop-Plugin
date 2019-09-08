<?php

class HyperpayCard extends ObjectModel
{
    public $id_customer;

    public $payment_method;

    public $registration_id;

    public $bin;

    public $last_4_digits;

    public $holder;

    public $expiry_month;

    public $expiry_year;

    public $date_add;

    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'hyperpay_card',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'payment_method' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'registration_id' => array('type' => self::TYPE_STRING),

            'bin' => array('type' => self::TYPE_STRING),
            'last_4_digits' => array('type' => self::TYPE_STRING),
            'holder' => array('type' => self::TYPE_STRING),
            'expiry_month' => array('type' => self::TYPE_STRING),
            'expiry_year' => array('type' => self::TYPE_STRING),

            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        )
    );

    /**
     * check if user already have the registration id for the payment method
     *
     * @param number $customer_id
     * @param string $registration_id
     * @param string $payment_method
     *
     * @return boolean
     */
    public static function isRegistrationIDExists($customer_id, $registration_id, $payment_method)
    {
        $sql = new DbQuery();
        $sql->select('id');
        $sql->from('hyperpay_card');
        $sql->where('id_customer = ' . pSQL($customer_id));
        $sql->where('registration_id = "' . pSQL($registration_id) . '"');
        $sql->where('payment_method = "' . pSQL($payment_method) . '"');
        $card_id = Db::getInstance()->getValue($sql);
        return $card_id != false;
    }

    /**
     * gets customer saved cards
     *
     * @param number $customer_id
     * @param string $payment_methodid_customer
     *
     * @return array
     */
    public static function getCustomerCards($customer_id, $payment_method = "")
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('hyperpay_card');
        $sql->where('id_customer = ' . pSQL($customer_id));
        if (strlen($payment_method) > 0) {
            $sql->where('payment_method = "' . pSQL($payment_method) . '"');
        }
        $cards = Db::getInstance()->executeS($sql);
        return $cards;
    }
}