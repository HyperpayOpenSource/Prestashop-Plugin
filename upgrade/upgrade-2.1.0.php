<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Add the Tabby and CliQ settings and the awaiting confirmation order state on existing shops
 *
 * @param Hyperpay $module
 *
 * @return boolean
 */
function upgrade_module_2_1_0($module)
{
    foreach (SERVER_TO_SERVER_BRANDS as $payment) {
        foreach (CONFIG['payment_methods'][$payment] as $key => $value) {
            if (Configuration::get($key) !== false) {
                continue;
            }

            $defaultValue = is_array($value) ? (isset($value['value']) ? $value['value'] : '') : $value;
            if (!Configuration::updateValue($key, $defaultValue)) {
                return false;
            }
        }
    }

    return $module->installPendingOrderState();
}
