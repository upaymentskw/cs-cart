<?php

use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update' && $_REQUEST['addon'] == 'upayments_payment' && (!empty($_REQUEST['upayments_settings']) || !empty($_REQUEST['upayments_logo_image_data']))) {
        $upayments_settings = isset($_REQUEST['upayments_settings']) ? $_REQUEST['upayments_settings'] : array();

        fn_upayments_payment_update_upayments_settings($upayments_settings);
    }
}

if ($mode == 'update') {
    if ($_REQUEST['addon'] == 'upayments_payment') {
        Tygh::$app['view']->assign('upayments_settings', fn_upayments_payment_get_upayments_settings());
    }
}
