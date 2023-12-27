<?php

use Tygh\Registry;
use Tygh\Http;
use Tygh\Session;

try {

    $order_id = $_REQUEST['MerchantOrderID'];
    $UnipayOrderID = $_REQUEST['UnipayOrderID'];
    $status = $_REQUEST['Status'];
    $reason = $_REQUEST['Reason'];
    $amount = $_REQUEST['Amount'];
    $hash = $_REQUEST['Hash'];

    $merchant_id = Registry::get('addons.upayments_payment.merchant_id');
    $secret_key = Registry::get('addons.upayments_payment.secret_key');

    $CalculateHash = $UnipayOrderID . '|' . $order_id . '|' . $status . '|' . $secret_key;

    $CalculateHash = md5($CalculateHash);

    if ($hash != $CalculateHash) {

        if (empty($reason)) {
            $reason = 'this_is_not_upayments_response';
        }

        throw new Exception($reason, 500);
    }

    $upayments_settings = fn_upayments_payment_get_upayments_settings();

    $order_info = fn_get_order_info($order_id, true);

    $cart = & Tygh::$app['session']['cart'];

    $order_info = fn_get_order_info($_REQUEST['order_id'], true);

    switch ($status) {
        case "COMPLETED":
            fn_change_order_status($order_id, $upayments_settings['upayments_statuses']['COMPLETED'], '');
            break;
        case "CANCELED":
            fn_change_order_status($order_id, $upayments_settings['upayments_statuses']['CANCELED'], '');
            break;
        default:
            fn_change_order_status($order_id, $upayments_settings['upayments_statuses']['NOT_FINISHED'], '');
    }

} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), $e->getCode();
}

exit();
