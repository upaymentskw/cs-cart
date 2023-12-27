<?php
use Tygh\Registry;
use Tygh\Http;
use Tygh\Session;

// app/addons/upayments_payment/payments/upayments_payment_processor.php
// Preventing direct access to the script, because it must be included by the "include" directive. The "BOOTSTRAP" constant is declared during system initialization.
defined('BOOTSTRAP') or die('Access denied');

// Here are two different contexts for running the script.
if (defined('PAYMENT_NOTIFICATION')) {
	$order_id = $_REQUEST["cs_order_id"];
	$payment_id = "";
	$pos = strpos($order_id, "?payment_id");
	if ($pos !== false)
	{
		$payment_id = substr($order_id, $pos + strlen("?payment_id") + 1);
		$order_id = (int)substr($order_id, 0, $pos);
	}
	$status = stripslashes($_REQUEST['result']);
	$status = strtolower($status);

    /**
     * Receiving and processing the answer
     * from third-party services and payment systems.
     */
	if(!empty($_REQUEST)){
		//check mode : complete,cancel or notify
		if($mode == 'notify' && !empty($_REQUEST['cs_order_id'])){
			$upayments_settings = fn_upayments_payment_get_upayments_settings();

			$cart = &Tygh::$app['session']['cart'];
			$order_info = fn_get_order_info($_REQUEST['cs_order_id'], true);

			if($order_info['status'] != 'C'){
				if($status == "captured" || $status == "success"){
					error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order# ".$order_id.' PAID ', 3, "upayments.log");
					fn_change_order_status($order_id, 'P', '');
					fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], true);
				}else if ($status == "canceled" || $status == "cancelled"){
					error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order# ".$order_id.' CANCELED ', 3, "upayments.log");
					fn_change_order_status($order_id, 'N', '');
					fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], false);
				}else{
					error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order# ".$order_id.' FAILED/NOT CAPTURED ', 3, "upayments.log");
					fn_change_order_status($order_id, 'F', '');
					fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], false);
				}
			}
			fn_order_placement_routines('route', $_REQUEST['cs_order_id']);
		}else if ($mode == 'complete' && !empty($_REQUEST['cs_order_id'])){
			$upayments_settings = fn_upayments_payment_get_upayments_settings();

			$order_id = $_REQUEST['cs_order_id'];
			$cart = &Tygh::$app['session']['cart'];
			$order_info = fn_get_order_info($_REQUEST['cs_order_id'], true);

			if($order_info['status'] == 'P' || $order_info['status'] == 'C')
				fn_order_placement_routines('repay', $_REQUEST['cs_order_id'], 'A');
			else
				fn_change_order_status($order_id, 'P', '');
				fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], true);
				fn_order_placement_routines('route', $_REQUEST['cs_order_id']);


		}elseif ($mode == 'cancel') {
			if ($status == "canceled" || $status == "cancelled"){
				error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order# ".$order_id.' CANCELED ', 3, "upayments.log");
				fn_change_order_status($order_id, 'N', '');
				fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], false);
				fn_order_placement_routines('route', $_REQUEST['cs_order_id']);
			}else {
				error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order# ".$order_id.' FAILED/NOT CAPTURED ', 3, "upayments.log");
				fn_change_order_status($order_id, 'F', '');
				fn_finish_payment($_REQUEST['cs_order_id'], ['status'=>$status,'order_id'=>$_REQUEST['order_id'],'track_id'=>$_REQUEST['track_id']], false);
				fn_order_placement_routines('route', $_REQUEST['cs_order_id']);
			}
		}
	}else
		fn_order_placement_routines('route', $_REQUEST['cs_order_id']);
} else {

    $cart = &Tygh::$app['session']['cart'];
    $auth = \Tygh::$app['session']['auth'];

    try {

        /**
         * Call api once customer click on submit button
         */

		$merchant_api_key = Registry::get('addons.upayments_payment.merchant_api_key');
        $test_mode = Registry::get('addons.upayments_payment.test_mode');
		$payment_test_mode = 0;
        if ($test_mode == 'Y') {
			$gateway_url = "https://sandboxapi.upayments.com/api/v1/charge";
			$getAPIUrlForCreateToken= "https://sandboxapi.upayments.com/api/v1/create-customer-unique-token";
		}else{
			$gateway_url = "https://apiv2api.upayments.com/api/v1/charge";
			$getAPIUrlForCreateToken= "https://apiv2api.upayments.com/api/v1/create-customer-unique-token";
		}

		// Setting of payment gateway
		$upayments_settings = fn_upayments_payment_get_upayments_settings();

        $language = Registry::get('settings.Appearance.frontend_default_language');
        $order_currency = $order_info['secondary_currency'];

        $items = array();
        $product_options = array();
		$product_names = [];
		$product_qty = [];
		$product_price = [];

		// create product details array to pass in api request
        foreach ($order_info['products'] as $product) {
			$variation_names = '';
			if (isset($product['product_options']) && !empty($product['product_options'])) {
				foreach ($product['product_options'] as $opt) {
					if(empty($variation_names))
						$variation_names = $opt['option_name'] . ": " . $opt['variant_name'];
					else
						$variation_names .=', '.$opt['option_name'] . ": " . $opt['variant_name'];
				}
			}

			if(!empty($variation_names))
				$product_name = $product['product'].'('.$variation_names.')';
			else
				$product_name = $product['product'];

			array_push($product_names,$product_name);
			array_push($product_price,$product['price']);
			array_push($product_qty,$product['amount']);
        }

        $order_name = "";
        $order_description = "";
        $taxes = 0;
        foreach ($order_info['taxes'] as $v) {
            foreach ($v['applies']['items']['P'] as $k1 => $v1) {
                $taxes += $v['tax_subtotal'];
            }
        }

        $shipping = 0;
        foreach ($order_info['shipping'] as $v) {
            $shipping += $v['rate'];
        }

        $confirm_link = fn_url("payment_notification.complete?payment=upayments_payment_processor&cs_order_id=" . $order_id . '&id', AREA, 'current');
        $cancel_link = fn_url("payment_notification.cancel?payment=upayments_payment_processor&cs_order_id=" . $order_id . '&', AREA, 'current');
		$notifyLink = fn_url("payment_notification.notify?payment=upayments_payment_processor&cs_order_id=" . $order_id . '&', AREA, 'current');

		if (empty($order_info['order_id']))
            throw new Exception("Order ID is Empty");

        if (empty($order_info['total']))
            throw new Exception("Order Price is Empty");

		if(empty($order_info['payment_info']['upay_payment_method']))
		    throw new Exception("Select Payment methods from Upayments Payment");

		if(empty($order_info['phone']))
		    throw new Exception("Enter phone number");

		$src=$order_info['payment_info']['upay_payment_method'];$credit_card_token=null;$isSaveCard=true;
		$phone=$order_info['phone'];
		$customer_unq_token = "";
		$phone = trim($phone);
		if (!empty($phone))
		{
			$phone = str_replace(' ', '', $phone); // Replaces all spaces with empty.
            $phone = preg_replace('/[^A-Za-z0-9\-]/','',$phone);
			$customer_unq_token = $phone;
			$params = json_encode(["customerUniqueToken" => $customer_unq_token, ]);

			$curl = curl_init();

			curl_setopt_array($curl, [CURLOPT_URL => $getAPIUrlForCreateToken , CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => getUserAgent(), CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => $params, CURLOPT_HTTPHEADER => ["Accept: application/json", "Content-Type: application/json", "Authorization: Bearer " . $merchant_api_key, ], ]);

			$response = curl_exec($curl);
			{
				$result = json_decode($response, true);
				if ($result["status"] == true)
				{
					$customer_unq_token = $customer_unq_token;
				}
			}
		}

		if($src == 'upayments'){
			$src = 'knet';
		}
		$unique_order_id=$order_info['order_id'];
		$params = json_encode([
			"returnUrl" =>  $confirm_link,
			"cancelUrl" => $cancel_link,
			"notificationUrl" => $notifyLink,
			"product" =>[
							"title" => [getSiteName()],
							"name" => $product_names,
							"price" => $product_price,
							"qty" => $product_qty,
                        ],
			"order" =>[
						"amount" => $order_info['total'],
						"currency" => $order_currency ,
						"id" => $unique_order_id,
					  ],
			"reference" => [
						"id" => "".$order_info['order_id'],
						],
			"customer" => [
						"uniqueId" => $customer_unq_token,
						"name" => $order_info['firstname'],
						"email" => $order_info['email'],
						"mobile" => $order_info['phone'],
						],
			"plugin" => [
						"src" => "cscart",
						],
			"is_whitelabled" => $whitelabled,
			"language" => "en",
			"isSaveCard" => $isSaveCard,
			"paymentGateway" => ["src" => $src,],
			"tokens" => [
						"creditCard" => $credit_card_token,
						"customerUniqueToken" => $customer_unq_token,
						],
			"device" => [
						"browser" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36 OPR/93.0.0.0",
						"browserDetails" => [
										"screenWidth" => "1920",
										"screenHeight" => "1080",
										"colorDepth" => "24",
										"javaEnabled" => "false",
										"language" => "en",
										"timeZone" => "-180",
										"3DSecureChallengeWindowSize" => "500_X_600", ],
						],
			]);
		// get result of called api
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => $gateway_url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_USERAGENT => getUserAgent(),
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $params,
		CURLOPT_HTTPHEADER => array(
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: Bearer '.$merchant_api_key
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$result = json_decode($response,true);
		if (isset($result['status']) && $result['status'] == "true" && isset($result['data']['link'])) {
			$redirectUrl =  $result['data']['link'];
			error_log(PHP_EOL.date('d.m.Y h:i:s') . " Order API request # ".$order_info['order_id'].' -- redirectUrl '. $redirectUrl .' -- Gateway '. "UPayments" , 3, "upayments.log");
			header('Location:  ' . $redirectUrl);
            exit;
		} else{
			throw new Exception( $result['message'] );
		}
    } catch (Exception $exception) {
        fn_set_notification('E', __('Error'), $exception->getMessage());
    }
    fn_print_r("Redirecting User");
}


