<?php
const PAYMENT_BRANDS =
[
    'VISA' => 'VISA',
    'MASTER' => 'MASTER',
    'PAYPAL' => 'PAYPAL',
    'AMEX' => 'AMEX',
    'MADA' => 'MADA',
    'STC_PAY' => 'STC_PAY',
    'APPLEPAY' => 'APPLEPAY',
    'AUTODETECT' => 'VISA MASTER AMEX JCB',
    'JCB' => 'JCB',
    'CLICK_TO_PAY' => 'CLICK_TO_PAY VISA MASTER AMEX JCB',
    'VALU' => 'VALU',
    'GOOGLEPAY' => 'GOOGLEPAY',
    'JAYWAN' => 'JAYWAN'
];

const PAYMENT_ACTIONS =  [
    'id' => 'value', // key that have the value in query array
    'name' => 'label', // key that have the label in query arrays
    'query' => [
        [
            'value' => 'DB',
            'label' => 'Debit'
        ],
        [
            'value' => 'PA',
            'label' => 'Pre-Authorization'
        ]
    ]
];

const YES_NO_OPTIONS = [
    [
        'value' => 1,
        'label' => 'Yes'
    ],
    [
        'value' => 0,
        'label' => 'No'
    ]
];



const CONFIG = [
    'HYPERPAY_MODE' => [
        'type' => 'select',
        'size' => 0,
        'options' => [
            'id' => 'value', // key that have the value in query array
            'name' => 'label', // key that have the label in query array
            'query' => [
                [
                    'value' => null,
                    'label' => '--Select an option--'
                ],
                [
                    'value' => 'INTERNAL',
                    'label' => 'Integrator Test'
                ],
                [
                    'value' => 'EXTERNAL',
                    'label' => 'Connector Test'
                ],
                [
                    'value' => 'LIVE',
                    'label' => 'Live'
                ]
            ]
        ],
    ],
    'HYPERPAY_TEST_URL' => 'https://eu-test.oppwa.com/v1/',
    'HYPERPAY_LIVE_URL' => 'https://eu-prod.oppwa.com/v1/',
    'HYPERPAY_ACCESS_TOKEN' => [
        'required' => true,
    ],
    'HYPERPAY_RISK_CHANNEL_ID' => [
        'required' => false,
    ],
    'HYPERPAY_STYLE' => [
        'type' => 'select',
        'size' => 0,
        'options' => [
            'id' => 'value', // key that have the value in query array
            'name' => 'label', // key that have the label in query array
            'query' => [
                [
                    'value' => 'card',
                    'label' => 'Card'
                ],
                [
                    'value' => 'plain',
                    'label' => 'Plain'
                ],
                [
                    'value' => 'none',
                    'label' => 'None'
                ]
            ]
        ]
    ],
    'HYPERPAY_CSS' => [
        'required' => false,
        'type' => 'textarea'
    ],
    'HYPERPAY_DEFAULT_STATUS' => [
        'type' => 'accepted-status',
        'label' => 'Default Status'
    ],
    'payment_methods' =>  [
        'AUTODETECT' => [
            'HYPERPAY_METHOD_AUTODETECT_NAME' => 'Auto Detect',
            'HYPERPAY_METHOD_AUTODETECT_TITLE' => 'HyperPay Auto Detect',
            'HYPERPAY_METHOD_AUTODETECT_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_AUTODETECT_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_AUTODETECT_ENTITY_ID' => '',
            'HYPERPAY_METHOD_AUTODETECT_CURRENCY' => [
                'type' => 'currency',
            ],
        ],
        'VISA' => [
            'HYPERPAY_METHOD_VISA_NAME' => 'Visa',
            'HYPERPAY_METHOD_VISA_TITLE' => 'HyperPay Visa',
            'HYPERPAY_METHOD_VISA_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_VISA_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_VISA_ENTITY_ID' => '',
            'HYPERPAY_METHOD_VISA_CURRENCY' => [
                'type' => 'currency',
            ],
        ],
        'JCB' => [
            'HYPERPAY_METHOD_JCB_NAME' => 'JCB',
            'HYPERPAY_METHOD_JCB_TITLE' => 'HyperPay JCB',
            'HYPERPAY_METHOD_JCB_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_JCB_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_JCB_ENTITY_ID' => '',
            'HYPERPAY_METHOD_JCB_CURRENCY' => [
                'type' => 'currency',
            ],
        ],
        'MASTER' => [
            'HYPERPAY_METHOD_MASTER_NAME' => 'Master Card',
            'HYPERPAY_METHOD_MASTER_TITLE' => 'HyperPay Master Card',
            'HYPERPAY_METHOD_MASTER_ENABLED' =>  [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_MASTER_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_MASTER_ENTITY_ID' => '',
            'HYPERPAY_METHOD_MASTER_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'PAYPAL' => [
            'HYPERPAY_METHOD_PAYPAL_NAME' => 'Paypal',
            'HYPERPAY_METHOD_PAYPAL_TITLE' => 'HyperPay Paypal',
            'HYPERPAY_METHOD_PAYPAL_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_PAYPAL_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_PAYPAL_ENTITY_ID' => '',
            'HYPERPAY_METHOD_PAYPAL_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'AMEX' => [
            'HYPERPAY_METHOD_AMEX_NAME' => 'Amex',
            'HYPERPAY_METHOD_AMEX_TITLE' => 'HyperPay Amex',
            'HYPERPAY_METHOD_AMEX_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_AMEX_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_AMEX_ENTITY_ID' => '',
            'HYPERPAY_METHOD_AMEX_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'MADA' => [
            'HYPERPAY_METHOD_MADA_NAME' => 'Mada',
            'HYPERPAY_METHOD_MADA_TITLE' => 'HyperPay Mada',
            'HYPERPAY_METHOD_MADA_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_MADA_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_MADA_ENTITY_ID' => '',
            'HYPERPAY_METHOD_MADA_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'STC_PAY' => [
            'HYPERPAY_METHOD_STC_PAY_NAME' => 'STCPay',
            'HYPERPAY_METHOD_STC_PAY_TITLE' => 'HyperPay STCPay',
            'HYPERPAY_METHOD_STC_PAY_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_STC_PAY_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_STC_PAY_ENTITY_ID' => '',
            'HYPERPAY_METHOD_STC_PAY_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'APPLEPAY' => [
            'HYPERPAY_METHOD_APPLEPAY_NAME' => 'Apple Pay',
            'HYPERPAY_METHOD_APPLEPAY_TITLE' => 'HyperPay Apple Pay',
            'HYPERPAY_METHOD_APPLEPAY_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_APPLEPAY_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],
            'HYPERPAY_METHOD_APPLEPAY_ENTITY_ID' => '',
            'HYPERPAY_METHOD_APPLEPAY_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'CLICK_TO_PAY' => [
            'HYPERPAY_METHOD_CLICK_TO_PAY_NAME' => 'CLICK_TO_PAY',
            'HYPERPAY_METHOD_CLICK_TO_PAY_TITLE' => 'HyperPay Click to Pay',
            'HYPERPAY_METHOD_CLICK_TO_PAY_ENABLED' => [
                'type' => 'switch',
                'label'=>'Enabled',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_CLICK_TO_PAY_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'label'=>'Action',
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_CLICK_TO_PAY_ENTITY_ID' => '',
            'HYPERPAY_METHOD_CLICK_TO_PAY_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'VALU' => [
            'HYPERPAY_METHOD_VALU_NAME' => 'VALU',
            'HYPERPAY_METHOD_VALU_TITLE' => 'HyperPay VALU',
            'HYPERPAY_METHOD_VALU_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_VALU_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_VALU_ENTITY_ID' => '',
            'HYPERPAY_METHOD_VALU_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ],
        'GOOGLEPAY' => [
            'HYPERPAY_METHOD_GOOGLEPAY_NAME' => 'Google Pay',
            'HYPERPAY_METHOD_GOOGLEPAY_TITLE' => 'HyperPay Google Pay',
            'HYPERPAY_METHOD_GOOGLEPAY_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_GOOGLEPAY_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],
            'HYPERPAY_METHOD_GOOGLEPAY_ENTITY_ID' => '',
            'HYPERPAY_METHOD_GOOGLEPAY_CURRENCY' =>  [
                'type' => 'currency'
            ],
            'HYPERPAY_METHOD_GOOGLEPAY_MERCHANT_ID' => '',
        ],
        'JAYWAN' => [
            'HYPERPAY_METHOD_JAYWAN_NAME' => 'JAYWAN',
            'HYPERPAY_METHOD_JAYWAN_TITLE' => 'HyperPay JAYWAN',
            'HYPERPAY_METHOD_JAYWAN_ENABLED' => [
                'type' => 'switch',
                'values' => YES_NO_OPTIONS,
            ],
            'HYPERPAY_METHOD_JAYWAN_ACTION' =>  [
                'type' => 'select',
                'size' => 0,
                'options' => PAYMENT_ACTIONS,
            ],

            'HYPERPAY_METHOD_JAYWAN_ENTITY_ID' => '',
            'HYPERPAY_METHOD_JAYWAN_CURRENCY' =>  [
                'type' => 'currency'
            ],
        ]
    ]

];
