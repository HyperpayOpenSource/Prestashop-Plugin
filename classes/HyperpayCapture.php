<?php

class HyperpayCapture extends ObjectModel
{
    public $id_order;

    public $capture_id;

    public $capture_amount;

    public $result;

    public $payment_method;

    public $date_add;

    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'hyperpay_capture',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'capture_id' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'capture_amount' => array('type' => self::TYPE_FLOAT),
            'payment_method' => array('type' => self::TYPE_STRING),
            'result' => array('type' => self::TYPE_STRING),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        )
    );
}