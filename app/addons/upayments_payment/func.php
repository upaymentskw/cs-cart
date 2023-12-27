<?php

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Http;

if (!defined('AREA')) {
    die('Access denied');
}

function fn_upayments_payment_send_request($data = [],$gateway_url) {
	// call rest api
	error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order # ".$order_info['order_id'].' -- CURL DATA '. $data , 3, "upayments.log");
    $result = '';
    do {
        $retry = false;
        $fields_string = http_build_query($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL,$gateway_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());

		$server_output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);


        if($httpcode === 401) // unauthorized
            $retry = true;
        else
			$result = json_decode($server_output,true);
    } while ($retry);

    return $result;
}

function fn_upayments_payment_update_upayments_settings($settings) {

    if (isset($settings['upayments_statuses'])) {
        $settings['upayments_statuses'] = serialize($settings['upayments_statuses']);
    }

    foreach ($settings as $setting_name => $setting_value) {
        Settings::instance()->updateValue($setting_name, $setting_value);
    }

    //Get company_ids for which we should update logos. If root admin click 'update for all', get all company_ids
    if (isset($settings['upayments_logo_update_all_vendors']) && $settings['upayments_logo_update_all_vendors'] == 'Y') {
        $company_ids = db_get_fields('SELECT company_id FROM ?:companies');
        $company_id = array_shift($company_ids);
    } elseif (!Registry::get('runtime.simple_ultimate')) {
        $company_id = Registry::get('runtime.company_id');
    } else {
        $company_id = 1;
    }
    //Use company_id as pair_id
    fn_attach_image_pairs('upayments_logo', 'upayments_logo', $company_id);
    if (isset($company_ids)) {
        foreach ($company_ids as $logo_id) {
            fn_clone_image_pairs($logo_id, $company_id, 'upayments_logo');
        }
    }
}

function fn_upayments_payment_get_upayments_settings($lang_code = DESCR_SL) {
    $upayments_settings = Settings::instance()->getValues('upayments_payment', 'ADDON');

    if (!empty($upayments_settings['general']['upayments_statuses'])) {
        $upayments_settings['general']['upayments_statuses'] = unserialize($upayments_settings['general']['upayments_statuses']);
    }

    $upayments_settings['general']['main_pair'] = fn_get_image_pairs(fn_upayments_payment_upayments_get_logo_id(), 'upayments_logo', 'M', false, true, $lang_code);

    $upayments_settings['general']['callback_url'] = fn_url('upayments_callback', 'C');


    return $upayments_settings['general'];
}

function fn_upayments_payment_upayments_get_logo_id() {
    if (Registry::get('runtime.simple_ultimate')) {
        $logo_id = 1;
    } elseif (Registry::get('runtime.company_id')) {
        $logo_id = Registry::get('runtime.company_id');
    } else {
        $logo_id = 0;
    }

    return $logo_id;
}

function fn_upayments_delete_payment_processors() {
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('upayments_payment_processor.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('upayments_payment_processor.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('upayments_payment_processor.php')");
}

function smarty_function_curl_request($params, &$smarty) {
    $knet=false;$credit_card=false;$samsung_pay=false;$google_pay=false;$apple_pay=false;$amex=false;
    $whitelabeled = false;

    $curl = curl_init();
    $merchant_api_key = Registry::get('addons.upayments_payment.merchant_api_key');
    $test_mode = Registry::get('addons.upayments_payment.test_mode');
    if($test_mode == 'Y'){
        $url='https://sandboxapi.upayments.com/api/v1/check-merchant-api-key';
    } else {
        $url='https://apiv2api.upayments.com/api/v1/check-merchant-api-key';
    }

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_USERAGENT => getUserAgent(),
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
        "apiKey": "'.$merchant_api_key.'"
    }',
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response, true);
    if($data['status'] == true){
        if($test_mode == 'Y'){
            $url_payment='https://sandboxapi.upayments.com/api/v1/check-payment-button-status';
        } else {
            $url_payment='https://apiv2api.upayments.com/api/v1/check-payment-button-status';
        }
        $whitelabeled = $data['data']['isWhiteLabel'];
        $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url_payment,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_USERAGENT => getUserAgent(),
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Authorization: Bearer '.$merchant_api_key
    ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $payment_methods = json_decode($response, true);
    if($payment_methods['status'] == true){
        $p = $payment_methods['data']['payButtons'];
            if($p['knet'] == true){
                $knet=true;
            }if($p['credit_card'] == true){
                $credit_card=true;
            }if($p['samsung_pay'] == true){
                $samsung_pay=true;
            }if($p['apple_pay'] == true){
                $apple_pay = true;
            }if($p['google_pay'] == true){
                $google_pay = true;
            }if($p['amex'] == true){
                $amex = true;
            }

    }

    }
    // Assign the response to a Smarty variable
    $smarty->assign('whitelabeled', $whitelabeled);
    $smarty->assign('payment_methods', $response);
    $smarty->assign('knet', $knet);
    $smarty->assign('credit_card', $credit_card);
    $smarty->assign('samsung_pay', $samsung_pay);
    $smarty->assign('apple_pay', $apple_pay);
    $smarty->assign('google_pay', $google_pay);
    $smarty->assign('amex', $amex);

    // Return an empty string (Smarty functions are not meant to output content directly)
    return '';
}

function getSiteName()
{
	return __("CSCart", "upayments");
}

function getUserAgent(){
	$userAgent = 'UpaymentsCSCartPlugin/2.0.0';
	$test_mode = Registry::get('addons.upayments_payment.test_mode');
    if($test_mode == 'Y'){
		$userAgent = 'SandboxUpaymentsCSCartPlugin/2.0.0';
	}
	return $userAgent;
}

?>
