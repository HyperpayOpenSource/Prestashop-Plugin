<?php

class HyperpayPayment extends ObjectModel
{
    public $id_order;

    public $id_cart;

    public $payment_id;

    public $payment_type;

    public $amount;

    public $currency;

    public $payment_method;

    public $total_paid;

    public $payment_status;

    public $total_prestashop;

    public $date_add;

    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'hyperpay_payment',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'payment_id' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'payment_type' => array('type' => self::TYPE_STRING),
            'amount' => array('type' => self::TYPE_FLOAT),
            'currency' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'payment_method' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'total_paid' => array('type' => self::TYPE_FLOAT),
            'payment_status' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'total_prestashop' => array('type' => self::TYPE_FLOAT),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        )
    );

    public static function loadByOrderId($id_order)
    {
        $sql = new DbQuery();
        $sql->select('id');
        $sql->from('hyperpay_payment');
        $sql->where('id_order = ' . pSQL($id_order));
        $id_hyperpay_payment = Db::getInstance()->getValue($sql);
        return new self($id_hyperpay_payment);
    }
}