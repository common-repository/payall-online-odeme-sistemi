<?php

/**
 * Plugin Name: PayALL Online Odeme Sistemi
 * Plugin URI: https://wordpress.org/plugins/payall-online-odeme-sistemi/
 * Description: Payment by Credit Card, Debit Card and E-Money Card
 * Version: 2.3
 * Author: payall.com.tr
 * Author URI: http://www.payall.com.tr/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: payall
 * Domain Path: /languages
 */

/* Define the database prefix */
global $wpdb;

/* Install Function */
register_activation_hook(__FILE__, 'payallplugin_activate');

function payallplugin_activate()
{
	global $wpdb;
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'payall` (
	  `order_id` int(10) unsigned NOT NULL,
	  `customer_id` int(10) unsigned NOT NULL,
	  `payall_id` varchar(64) NULL,
	  `amount` decimal(10,4) NOT NULL,
	  `amount_paid` decimal(10,4) NOT NULL,
	  `installment` int(2) unsigned NOT NULL DEFAULT 1,
	  `cardholdername` varchar(60) NULL,
	  `cardnumber` varchar(25) NULL,
	  `cardexpdate` varchar(8) NULL,
	  `createddate` datetime NOT NULL,
	  `ipaddress` varchar(16) NULL,
	  `status_code` tinyint(1) DEFAULT 1,
	  `result_code` varchar(60) NULL,
	  `result_message` varchar(256) NULL,
	  `mode` varchar(16) NULL,
	  `shared_payment_url` varchar(256) NULL,
	  KEY `order_id` (`order_id`),
	  KEY `customer_id` (`customer_id`)
	) DEFAULT CHARSET=utf8;';
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	return dbDelta($sql);
}



function payall_script() {
	wp_register_style('form', plugins_url('css/form.css', __FILE__),array(),'1.0.2');
	wp_enqueue_style('form');

	wp_register_script('payment', plugins_url('/js/jquery.payment.min.js',__FILE__), array('jquery'));
	wp_enqueue_script('payment');
	
	wp_register_script('creditCardValidator', plugins_url('/js/jquery.creditCardValidator.js',__FILE__), array('jquery'));
	wp_enqueue_script('creditCardValidator');
	
	wp_register_script('validate', plugins_url('/js/jquery.validate.min.js',__FILE__), array('jquery'));
    wp_enqueue_script('validate');
}

/* Plugin script and style */
add_action( 'wp_enqueue_scripts', 'payall_script' );
/* Plugin Load */
add_action('plugins_loaded', 'init_payall_gateway_class', 0);
//add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

function payall_load_plugin_textdomain() {
    load_plugin_textdomain( 'payall', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

function init_payall_gateway_class()
{
	if (!class_exists('WC_Payment_Gateway'))
		return;

	class payall extends WC_Payment_Gateway
	{
	
		function __construct()
		{
			$this->id = __( 'payall', 'payall' );
			$this->method_title = __( 'PayALL Online Payment System', 'payall' );
			$this->method_description = __( 'Credit Card, Debit Cart, and Pay By eMoney Card', 'payall');
			$this->title = __( 'Credit Card, Debit Cart, and Pay By eMoney Card', 'payall');
			$this->icon = null;
			$this->has_fields = true;
			$this->supports = array('default_credit_card_form');
			$this->init_form_fields();
			$this->init_settings();
			$this->version = 2.0;
			
			foreach ($this->settings as $setting_key => $value)
				$this->$setting_key = $value;
			//Register the style
			add_action('admin_enqueue_scripts', array($this, 'payall_register_admin_styles'));
			add_action('woocommerce_receipt_' . $this->id, array($this, 'payall_receipt_page'));
			add_action('woocommerce_thankyou_' . $this->id, array($this, 'payall_receipt_page'));			

			add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'payall_getinstalments' ) );
			//add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'getinstalments' ) );

			if (is_admin()) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}
			
			//$payall_settings = get_option("woocommerce_payall_settings");			
			//$this->payall_apikey = $payall_settings["payall_apikey"];
		}		

		

		public function payall_register_admin_styles()
		{
			wp_register_style('admin', plugins_url('css/admin.css', __FILE__));
			wp_enqueue_style('admin');
		}

		public function admin_options()
		{
			echo esc_html( __('PayALL Payment Settings','payall'));
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';

			include(dirname(__FILE__).'/includes/PayallFooter.php');
		}

		/* 	Admin Panel Fields */

		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Add-on Active', 'payall'),
					'label' => __('Plugin Active?', 'payall'),
					'type' => 'checkbox',
					'default' => 'yes',
				),
				'environment' => array(
					'title' => __('Environment', 'payall'),
					'type' => 'select',
					'desc_tip' => 'Production ortamında gerçek ödemeler yapılır. Test ortamı entegrasyon süreci içindir. Debug modu developerlar içindir.',
					'default' => 'P',
					'options' => array(
						'P' => __('Production Mod', 'payall'),
						'T' => __('Test Mod', 'payall'),
						'D' => __('Debug Mod', 'payall')
					),
				),
				'client_id' => array(
					'title' => 'Client Id',
					'type' => 'text',
					'desc_tip' => 'PayALL tarafından atanan ecommerce client id bilgisi.',
				),
				'client_secret' => array(
					'title' => 'Client Secret',
					'type' => 'text',
					'desc_tip' => 'PayALL tarafından atanan ecommerce client secret bilgisi.',
				),
				'client_salt' => array(
					'title' => 'Client Salt',
					'type' => 'text',
					'desc_tip' => 'PayALL tarafından atanan ecommerce client salt bilgisi.',
				),
				'mode' => array(
					'title' => __('Payment Method','payall'),
					'type' => 'select',
					'desc_tip' => __('Payment Method?','payall'),
					'default' => 'form3d',
					'options' => array(
						'shared3d' => 'PayALL Ortak Ödeme Sayfası',
						'form3d' => 'Form ile 3D Ödeme'
					),
				),
				'installmentenabled' => array(
					'title' => __('Installment?','payall'),
					'type' => 'select',
					'desc_tip' => __('Installment?','payall'),
					'default' => '0',
					'options' => array(
						'0' => __('No', 'payall'),
						'1' => __('Yes', 'payall'),
					),
				)
			);
		}

// End init_form_fields()

		public function process_payment($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
      
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
         
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }			

            return array(
                'result' => 'success',
                'redirect' => $checkout_payment_url,
            );
			
		}

//END process_payment

		function fn_payall_parseprocessordata()
		{			
			$processor_data = get_option("woocommerce_payall_settings"); 

			$clientId = $processor_data['client_id'];
			$clientSecret = $processor_data['client_secret'];
			$clientSalt = $processor_data['client_salt'];
						
			$mode = $processor_data['mode'];

			$instalmentEnabled = $processor_data['installmentenabled'] == '1';

			$environment = $processor_data['environment'];
			$apiUrlsArr = array(
				'P' => 'https://merchant.payall.com.tr/v2',
				'T' => 'https://merchanttest.payall.com.tr/v2',
				'D' => 'http://192.168.51.211:8001/v1'
			);

			$apiUrl = $apiUrlsArr[$environment];

			$payall_processordata = array(
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'client_salt' => $clientSalt,
				'api_url' => $apiUrl,
				'instalment_enabled' => $instalmentEnabled,
				'mode' => $mode
			);

			return $payall_processordata;
		}

		function fn_payall_getpaywithcardurl($payall_settings)
		{
                $actionUrl = $payall_settings['api_url'] . '/api/paymentlink/PayWithCard';
    		return $actionUrl;
		}

		public function credit_card_form($args = array(), $fields = array())
		{
			printf( esc_html__('You can pay with any credit card.','payall'));
		}

		public function createSecret($key)
		{
			return sha1('payall' . $key);
		}

		function payall_getAccessToken($payall_settings)
		{
			$apiUrl = $payall_settings["api_url"];
			$clientId = $payall_settings["client_id"];
			$clientSecret = $payall_settings["client_secret"];	

			$accessTokenRequest = new PayallAccessTokenRequest();
			$accessTokenRequest->clientId = $clientId;
			$accessTokenRequest->clientSecret = $clientSecret;
			$accessTokenRequest->apiUrl = $apiUrl;
			$accessTokenResponse = PayallAccessTokenRequest::Execute($accessTokenRequest);

			return $accessTokenResponse;
		}

		function payall_getinstalments()
		{
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallRestHttpCaller.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallAccessTokenRequest.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallFindInstalmentsRequest.php');			

			$requestType = sanitize_text_field($_POST['requesttype']);
			
			if($requestType == 'getinstalments')
			{
				$binnumber = sanitize_text_field($_POST['binnumber']);
				$payall_id = sanitize_text_field($_POST['payall_id']);
				
				$payall_settings = $this->fn_payall_parseprocessordata();

				$apiUrl = $payall_settings["api_url"];
				$clientId = $payall_settings["client_id"];
				$clientSecret = $payall_settings["client_secret"];	
				
				$accessTokenResponse = $this->payall_getAccessToken($payall_settings);

				$findInstalmentRequest = new PayallFindInstalmentsRequest();
				$findInstalmentRequest->transactionLinkId = $payall_id;
				$findInstalmentRequest->cardBinNumber = $binnumber;
				$findInstalmentRequest->apiUrl = $apiUrl;
				$findInstalmentRequest->accessToken = $accessTokenResponse;

				$findInstalmentResponse = PayallFindInstalmentsRequest::Execute($findInstalmentRequest);

				$findInstalmentResponseJson = json_decode($findInstalmentResponse);

				echo wp_send_json($findInstalmentResponseJson->InstallmentList, 200);
			}
			exit;
		}

		function payall_generatePaymentLink($order_id)
		{			
			global $woocommerce;
			get_currentuserinfo();
			$order = new WC_Order($order_id);

			require_once( plugin_dir_path(__FILE__) . 'includes/PayallRestHttpCaller.php');
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallGeneratePaymentLinkRequest.php');			
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallHelper.php');			
			require_once( plugin_dir_path(__FILE__) . 'includes/PayallAccessTokenRequest.php');					

			$user_meta = get_user_meta(get_current_user_id());
			$user_id = get_current_user_id();
			$amount = $order->order_total;
			$payall_settings = $this->fn_payall_parseprocessordata();
			
			$mode = $payall_settings["mode"];
			$apiUrl = $payall_settings["api_url"];
			$clientId = $payall_settings["client_id"];
			$clientSecret = $payall_settings["client_secret"];									
			$instalmentEnabled = $payall_settings["instalment_enabled"];

			$instalmentCount = 0;
			if($instalmentEnabled)
			{
				$instalmentCount = 20;
			}					

			$record = array();

			$product_details = array();			
			$order_items = $order->get_items();
			
			foreach( $order_items as $product ) {	
				$product_details += [$product['name'] => $product['total']];				
			}
		
			try {				
				$accessTokenResponse = $this->payall_getAccessToken($payall_settings);
			} catch (Exception $e) {
				$record['result_code'] = 'ERROR';
				$record['result_message'] = $e->getMessage();
				$record['status_code'] = 1;
				return $record;
			}

			$request = new PayallGeneratePaymentLinkRequest();
			$request->amount = $amount;
			$request->expireDate = date("Y-m-d H:i");
			$request->successUrl = $order->get_checkout_payment_url(true);
			$request->errorUrl = $order->get_checkout_payment_url(true);			
			$request->orderId = $order_id;
			$request->orderInfo = $product_details;
			$request->instalmentCount = $instalmentCount;
			$request->apiUrl = $apiUrl;
			$request->accessToken = $accessTokenResponse;

			try {				
				$response = PayallGeneratePaymentLinkRequest::Execute($request); 				
			} catch (Exception $e) {
				$record['result_code'] = 'ERROR';
				$record['result_message'] = $e->getMessage();
				$record['status_code'] = 1;
				return $record;
			}

			$jsonArr = json_decode($response, true);

			$record['status_code'] = $jsonArr['BaseWebApiResponse']['ReturnStatus'];
			$record['result_code'] = $jsonArr['BaseWebApiResponse']['ReturnStatus'];
			$record['result_message'] = PayallHelper::turkishreplace( $jsonArr['BaseWebApiResponse']['FriendlyResponse']);
			$record['payall_id'] = $jsonArr['TransactionLinkId'];
			
			if ($mode =='shared3d')
			{							
				$record['shared_payment_url'] = $jsonArr['PaymentLink'];
			}
			else 
			{
				$record['paywithcard_url'] = $this->fn_payall_getpaywithcardurl($payall_settings);
			}

			return $record;
		}

		function fn_payall_gethashofpayment($payall_settings, $orderId, $returnStatus, $netAmount)
		{
    		$strConcat =  $orderId.$payall_settings['client_salt'].$returnStatus.$netAmount;
			$sig = hash_hmac('sha256', $strConcat, $payall_settings['client_secret'], true);
			$hash = base64_encode($sig);
			return $hash;
		}	

		function payall_receipt_page($orderid)
		{
			global $woocommerce;
			$error_message = false;
			$order = new WC_Order($orderid);
			$cc_form_key = $this->createSecret($orderid);
			$status = $order->get_status();
			
			if($status != 'pending')
			{				
				return 'ok';
			}

			$TransactionLinkId = null;

			if(isset($_POST['TransactionLinkId']))
			{
				$TransactionLinkId = sanitize_text_field($_POST['TransactionLinkId']);
			}

			$payall_settings = $this->fn_payall_parseprocessordata();
			$mode = $payall_settings["mode"];
			$apiUrl = $payall_settings["api_url"];

			if ($TransactionLinkId != null)
			{
				// payalldan cevap geldi.
				require_once( plugin_dir_path(__FILE__) . 'includes/PayallRestHttpCaller.php');
				require_once( plugin_dir_path(__FILE__) . 'includes/PayallHelper.php');
				require_once( plugin_dir_path(__FILE__) . 'includes/PayallAccessTokenRequest.php');
				require_once( plugin_dir_path(__FILE__) . 'includes/PayallFinishPaymentProcess.php');				

				$user_id = get_current_user_id();
				$amount = $order->order_total;				
				$instalmentCount = sanitize_text_field($_POST['InstallmentCount']);
				$netAmount = sanitize_text_field($_POST['NetAmount']);
				$hashFromServer = sanitize_text_field($_POST['Hash']);
				$returnStatus =  sanitize_text_field($_POST['ReturnStatus']);

				$clientIp = PayallHelper::payall_get_client_ip();

				$record = array(
					'order_id' => $orderid,
					'customer_id' => $user_id,
					'payall_id' => $TransactionLinkId,
					'amount' => $amount,
					'amount_paid' => $netAmount,
					'installment' => $instalmentCount,
					'cardholdername' => '',
					'cardexpdate' => '',
					'cardnumber' => '',
					'createddate' =>date("Y-m-d h:i:s"), 
					'ipaddress' =>  $clientIp,
					'status_code' => 1, //default unsuccessful
					'result_code' => '', 
					'result_message' => '',
					'mode' =>  $mode,
					'shared_payment_url' => 'null'
				);

				$record['status_code'] = $returnStatus;
				$record['result_code'] = $returnStatus;
				$record['result_message'] = sanitize_text_field($_POST['FriendlyResponse']);
				$this->payall_addRecord($record);

				if($record['status_code'] == 0 && $hashFromServer != null)
				{//Başarılı işlem
					
					$hashComputed = $this->fn_payall_gethashofpayment($payall_settings, $orderid, $returnStatus, $netAmount);

					if($hashComputed == $hashFromServer)
					{
						$accessTokenResponse = $this->payall_getAccessToken($payall_settings);

						$finishPaymentRequest = new PayallFinishPaymentProcess();

						$finishPaymentRequest->transactionLinkId = $TransactionLinkId;
						$finishPaymentRequest->orderId = $orderid;
						$finishPaymentRequest->clientIp = $clientIp;
						$finishPaymentRequest->clientUserAgent = '';
						$finishPaymentRequest->accessToken = $accessTokenResponse;
						$finishPaymentRequest->apiUrl = $apiUrl;

						PayallFinishPaymentProcess::Execute($finishPaymentRequest);

						$order->update_status('processing', __('Payall payment is processing.', 'woocommerce'));
						$order->add_order_note ('Payment completed with PayALL. Transaction number: #' . $record['payall_id'] . ' Tutar ' . $record['amount'] . ' Taksit: ' . $record['installment'] . ' Ödenen:' . $record['amount_paid']);
						$order->payment_complete();
						$woocommerce->cart->empty_cart();
						wp_redirect($this->get_return_url());					
						exit;
						$error_message = false;
					}
					else 
					{
						$order->update_status ('pending', 'Ödeme hash kontrolünü geçemedi. Security breach.', 'woocommerce');						
						$error_message = __('Payment could not be completed: Return message has been changed. Security breach.','payall');
						$order->add_order_note($error_message);

						if($mode == 'form3d')
						{
							$record = $this->payall_generatePaymentLink($orderid);					
							$payall_id = $record['payall_id'];
							$payall_paywithcard_url = $record['paywithcard_url'];
						}	
					}
				}
				else { //Başarısız işlem				
					$order->update_status('pending', 'Payment Failed.', 'woocommerce');
					$error_message = __('Payment could not be completed: Issuer bank response: ('. $record['result_code'] . ') ' . $record['result_message'],'payall');
					$order->add_order_note($error_message);

					if($mode == 'form3d')
					{
						$record = $this->payall_generatePaymentLink($orderid);					
						$payall_id = $record['payall_id'];
						$payall_paywithcard_url = $record['paywithcard_url'];
					}					
				}
			}
			else 
			{
				$record = $this->payall_generatePaymentLink($orderid);

				$shared_payment_url = null;

				if(isset($record["shared_payment_url"]))
				{
					$shared_payment_url = $record["shared_payment_url"];
				}

				if($shared_payment_url != null) // Ortak ödemeye yönlen 
				{	
					$this->payall_saveRecord($record);
					wp_redirect($record["shared_payment_url"]);
					exit;
				}
				
				$payall_id = $record['payall_id'];
				$payall_paywithcard_url = $record['paywithcard_url'];
			}
	
			include(dirname(__FILE__).'/payform.php');
		}
	
		private function payall_addRecord($record)
		{
			global $wpdb;
			$wpdb->insert($wpdb->prefix . 'payall', $record);
		}

		private function payall_updateRecordByOrderId($record)
		{
			global $wpdb;
			$wpdb->update($wpdb->prefix . 'payall', $record, array('order_id' => (int) $record['order_id']));
		}
		
		public function payall_saveRecord($record)
		{		
			if (isset($record['order_id']) AND $record['order_id'] AND $this->payall_getRecordByOrderId($record['order_id']))
				return $this->payall_updateRecordByOrderId($record);
			
			return $this->payall_addRecord($record);
		}

		public function payall_getRecordByOrderId($order_id)
		{
			global $wpdb;
			return $wpdb->get_row('SELECT * FROM `' . $wpdb->prefix . 'payall` WHERE `order_id` = ' . (int) $order_id, ARRAY_A);
		}
	}

	//END Class payall

	function payall($methods)
	{
		$methods[] = 'payall';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'payall');
}
add_action( 'plugins_loaded', 'payall_load_plugin_textdomain' );

