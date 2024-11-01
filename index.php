<?php

include_once 'Math/BigInteger.php';
//include 'Crypt/RSA.php';
include 'Crypt/webxpay.php';

if ( ! defined( 'ABSPATH' ) ) exit; 
/*
Plugin Name: WebXpay Payment Gateway for WooCommerce
Plugin URI: www.webxpay.com
Description: WebXpay Gateway from WebXpay (Pvt) Ltd.
Version: 1.0
Author: WebXpay (Private) Limited
Author URI: www.webxpay.com
*/

add_action('plugins_loaded', 'woocommerce_webxpay', 0);

function woocommerce_webxpay(){
	if(!class_exists('WC_Payment_Gateway')) return;

	class WC_WebXPay extends WC_Payment_Gateway {
	  
	public function __construct(){
				
		$plugin_dir = plugin_dir_url(__FILE__);

		$this->id = 'webxpay';	  
		$this->icon = $plugin_dir . 'logo.png';
		$this->method_title = __( "WebXpay", 'webxpay' );
		$this->has_fields = false;

		$this->init_form_fields();
		$this->init_settings(); 

		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		   
		$this->msg['message'] 	= "";
		$this->msg['class'] 		= "";

		add_action('init', array(&$this, 'check_webxpay_response'));	  
		  
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
		} else {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}
		add_action('woocommerce_receipt_webxpay', array(&$this, 'receipt_page'));
	 
	}

	function init_form_fields(){

	   $this -> form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'webxpay'),
					'type' => 'checkbox',
					'label' => __('Enable WebXpay Module.', 'webxpay'),
					'default' => 'no'),
					
				'title' => array(
					'title' => __('Title:', 'webxpay'),
					'type'=> 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'webxpay'),
					'default' => __('Card / Mobile Wallet Payment', 'webxpay')),
				
				'description' => array(
					'title' => __('Description:', 'webxpay'),
					'type'=> 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'webxpay'),
					'default' => __('Visa / MasterCard', 'webxpay')),	
					
				'liveurl' => array(
					'title' => __('PG Domain:', 'webxpay'),
					'type'=> 'text',
					'description' => __('IPG data submiting to this URL', 'webxpay'),
					'default' => __('https://www.webxpay.com/index.php?route=checkout/billing', 'webxpay')),
										
				'currency_code' => array(
					'title' => __('Currency Code:', 'webxpay'),
					'type'=> 'text',
					'description' => __('Currency code supported by WebXpay.', 'webxpay'),
					'default' => __('LKR', 'webxpay')),										
					
				'secret_key' => array(
					'title' => __('Secret Key:', 'webxpay'),
					'type'=> 'text',
					'description' => __('A key given by WebXpay.', 'webxpay'),
					'default' => __('D15AA1E2482BCD6D56E5F3A4B5DE4', 'webxpay')),
					
				'public_key' => array(
					'title' => __('Public Key:', 'webxpay'),
					'type'=> 'textarea',
					'description' => __('A key given by WebXpay.', 'webxpay'),
					'default' => __('-----BEGIN PUBLIC KEY----- MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDMgKoWe7+6lkjIoYO0nr/nFxzm nzctfu0xIanhqe7s0odp4ft3wRaK6EG3UxfsRqXQQ05lW6PTIzZgaa+Dm/AYl4vy egTHkpy2M/23j5zjdv6wZapKgLq+Q6LxbJe0EWre6tdiqoY4uQurWQqgeuq0+p1H 7qIrwD+3hXiIa+KgrQIDAQAB -----END PUBLIC KEY-----', 'webxpay')),
													
				'checkout_msg' => array(
					'title' => __('Checkout Message:', 'webxpay'),
					'type'=> 'textarea',
					'description' => __('Message display when checkout'),
					'default' => __('Thank you for your order. Please click the button below to pay with WebXpay.', 'webxpay'))	,

				'responce_url' => array(
                    'title' => __('Redirect URL :', 'webxpay'),
                    'type'=> 'text',
                    'description' => __('After payment is sucess/unsucess redirecting to this page.'),
                    'default' => __('http://your-site.com/THANK-YOU/', 'webxpay'))							
			
			);
	}

	public function admin_options(){
		echo '<h3>'.__('WebXpay Payment Gateway', 'webxpay').'</h3>';
		echo '<p>'.__('WebXpay Payment Gateway allows you to accept payments from customers using Visa / MasterCard / AMEX / EzCash / Mcash / Sampath Vishwa and any new payment options made available to public use.').'</p>';
		echo '<table class="form-table">';        
			$this->generate_settings_html();
		echo '</table>'; 
	}

	function payment_fields(){	
		if($this -> description) echo wpautop(wptexturize($this -> description));
	}

	function receipt_page($order){      

		global $woocommerce;
		$order_details = new WC_Order($order);
			  
		echo '<br>'.$this->checkout_msg.'</b>'; 

		echo $this->generate_ipg_form($order);			
	}
		
	public function generate_ipg_form($order_id){

		global $woocommerce;

		$order = new WC_Order($order_id);
		//var_dump($order); die();

		
		$billing_address = $order->get_billing_address();
		$productinfo = "Order $order_id"; 
		
		$currency_code 	= $this -> currency_code;
		$curr_symbole 	= 'Rs.';		
		
		//initialize RSA
		$rsa = new Crypt_RSA();	
		
		// unique_order_id|total_amount|secret_key
		$plaintext = $order_id .'|' . $order->order_total;
		//load public key for encrypting
		$publickey = $this -> public_key;
		$rsa->loadKey($publickey);
		$encrypt = $rsa->encrypt($plaintext);

		//encode for data passing
		$payment = base64_encode($encrypt);
		
		global $wpdb;				
		$table_name = $wpdb->prefix . 'webxpay_ipg';			
		$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_reference_no = '".$order_id."'" );		
			
		if($check_oder > 0){
			$wpdb->update( 
				$table_name, 
				array( 
					'order_id'=>$order_id, 
					'order_reference_number'=>'', 
					'currency_code'=>'', 
					'currency_code'=>$this->currency_code, 
					'amount'=>$order->order_total, 
					'date_added' => date('Y-m-d H:i:s'), 
					'date_time_transaction'=>'', 
					'status_code'=>'',  
					'comment'=>''
				), 
				array( 'merchant_reference_no' => $order_id ));								
		}else{
			
			$wpdb->insert($table_name, array( 
					'order_id'=>$order_id, 
					'order_reference_number'=>'', 
					'currency_code'=>'', 
					'currency_code'=>$this->currency_code, 
					'amount'=>$order->order_total, 
					'date_added' => date('Y-m-d H:i:s'), 
					'date_time_transaction'=>'', 
					'status_code'=>'',  
					'comment'=>''
					), 
				array( '%s', '%d' ) );					
		}		
		
		//Get customer details
		$first_name = $order->billing_first_name;
		$last_name = $order->billing_last_name;
		$email = $order->billing_email;
		$contact_number = $order->billing_phone;
		$address_line_one = $order->billing__address_1;
		$address_line_two = $order->billing_address_2;
		$city = $order->billing_city;
		$state = $order->billing_state;
		$postal_code = $order->billing_postcode;
		
		$countries_obj   = new WC_Countries();
		$countries   = $countries_obj->__get('countries');
		$country = $countries[$order->billing_country];
		
		//create form array
		$form_args = array(
		  'secret_key' => $this->secret_key,
		  'payment' => $payment,          
		  'first_name' => (!empty($first_name) ? $first_name : ''),
		  'last_name' => (!empty($last_name) ? $last_name : ''),
		  'email' => (!empty($email) ? $email : ''),
		  'contact_number' => (!empty($contact_number) ? $contact_number : ''),
		  'address_line_one' => (!empty($address_line_one) ? $address_line_one : ''),
		  'address_line_two' => (!empty($address_line_two) ? $address_line_two : ''),
		  'city' => (!empty($city) ? $city : ''),
		  'state' => (!empty($state) ? $state : ''),
		  'postal_code' => (!empty($postal_code) ? $postal_code : ''),
		  'country' => (!empty($country) ? $country : ''),	  
		  );
		  
		$form_args_array = array();
		foreach($form_args as $key => $value){
		  $form_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
		}			
		

		return '<p>'.$percentage_msg.'</p>
		<p>Total amount will be <b>'.$curr_symbole.' '.number_format(($order->order_total)).'</b></p>
		<form action="'.$this -> liveurl.'" method="post" id="merchantForm">
			' . implode('', $form_args_array) . '
			 
			<input type="submit" class="button-alt" id="submit_ipg_payment_form" value="'.__('Pay Now', 'webxpay').'" /> 
			
			</form>'; 
	}
		
	function process_payment($order_id){
		
		$order = new WC_Order($order_id);
		return array('result' => 'success', 'redirect' => add_query_arg('order',           
		   $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
		);
	}
		 
	function check_webxpay_response(){		
			
		global $woocommerce;	
		global $wpdb;		
		
		if(isset($_POST['payment']) && isset($_POST['signature'])) {	


			$payment = base64_decode($_POST ["payment"]);
			$signature = base64_decode($_POST ["signature"]);		
			
			//initialize RSA
			$rsa = new Crypt_RSA();
			//load public key for signature matching
			$rsa->loadKey($this->public_key);
			//verify signature
			$signature_status = $rsa->verify($payment, $signature) ? TRUE : FALSE;
			
			//proceed if signatures match only
			if($signature_status)
			{

				//get payment response in segments
				//payment format: order_id|order_refference_number|date_time_transaction|payment_gateway_id|status_code|comment;
				$responseVariables = explode('|', $payment);      										
													

				if(!empty($responseVariables)){
						
					$order_id = $responseVariables[0];
					$order_reference_number = $responseVariables[1];
					$date_time_transaction = $responseVariables[2];
					$response_code = $responseVariables[3];
					$status_code = $responseVariables[4];
					//$comment = $responseVariables[5];	
					$payment_gateway_id = $responseVariables[5];				
					$order 	= new WC_Order($order_id);
				
					//update database							
					$table_name = $wpdb->prefix . 'webxpay_ipg';	
					$wpdb->update( 
					$table_name, 
					array( 						
						'order_id'=>$order_id, 
						'order_reference_number'=> $order_reference_number,
						'currency_code'=>$this->currency_code, 							 
						'date_time_transaction'=> $date_time_transaction,
						'payment_gateway_id' => $payment_gateway_id,
						'status_code'=> $status_code,  
						'comment'=> $comment
					), 
					array( 'order_id' => $order_id ));

					//Sampath bank - Paycorp IPG
					if($payment_gateway_id == 1) {
													
					//var_dump($payment_gateway_id); die();

						if($responseVariables[3] == '10' || $responseVariables[3] == '00') {						
																			
							$order->add_order_note('WebXpay Payment successful<br/>Order reference number: ' . $order_reference_number);
							$order->add_order_note($this->msg['message']);
							
							$order->payment_complete();	
							$woocommerce->cart->empty_cart();	 
							wp_redirect( $this->responce_url.'?webxpay=true&status=1&unidue='.$order_reference_number, 301 ); 
							exit;	
						}
						else{					
							$order->update_status('failed');
							$order->add_order_note('WebXpay Payment unsuccessful<br/>Order reference number: ' . $order_reference_number . '<br/>Status code: ' . $status_code . '<br/>Comment: ' . $comment . '');
							$order->add_order_note($this->msg['message']);	

							$woocommerce->cart->empty_cart();		
							wp_redirect( $this->responce_url.'?webxpay=true&status=0&unidue='.$order_reference_number, 301 );
							exit;		
						}
					}	
					//Amex bank - EzCash - SAmpath Vishwa - Mcash
					else if ($payment_gateway_id == 4 || $payment_gateway_id == 2 || $payment_gateway_id == 5 || $payment_gateway_id == 3){

						if($responseVariables[3] == '0' || $responseVariables[3] == '00') {						
																			
							$order->add_order_note('WebXpay Payment successful<br/>Order reference number: ' . $order_reference_number);
							$order->add_order_note($this->msg['message']);
							
							$order->payment_complete();	
							$woocommerce->cart->empty_cart();	 
							wp_redirect( $this->responce_url.'?webxpay=true&status=1&unidue='.$order_reference_number, 301 ); 
							exit;	
						}
						else{					
							$order->update_status('failed');
							$order->add_order_note('WebXpay Payment unsuccessful<br/>Order reference number: ' . $order_reference_number . '<br/>Status code: ' . $status_code . '<br/>Comment: ' . $comment . '');
							$order->add_order_note($this->msg['message']);	

							$woocommerce->cart->empty_cart();		
							wp_redirect( $this->responce_url.'?webxpay=true&status=0&unidue='.$order_reference_number, 301 );
							exit;		
						}
					}



				}								
			}						
		}
	}


	function wc_custom_redirect_after_purchase()
	{
		//return '<p>I am Here!</p>';
	}

	function get_pages($title = false, $indent = true) {
		$wp_pages = get_pages('sort_column=menu_order');
		$page_list = array();
		if ($title) $page_list[] = $title;
		foreach ($wp_pages as $page) {
			$prefix = '';            
			if ($indent) {
				$has_parent = $page->post_parent;
				while($has_parent) {
					$prefix .=  ' - ';
					$next_page = get_page($has_parent);
					$has_parent = $next_page->post_parent;
				}
			}            
			$page_list[$page->ID] = $prefix . $page->post_title;
		}
		return $page_list;
	}
	}
	// Catch payment response
	if(isset($_POST['payment']) && isset($_POST['signature'])) {
		
		$WC = new WC_WebXPay();	
	}
   
	function woocommerce_add_webxpay($methods) {
	   $methods[] = 'WC_WebXPay';
	   return $methods;
	}
	 	
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_webxpay' );
}

global $webxpay_db_version;
$webxpay_db_version = '1.0';

function webxpay_install() {		
	global $wpdb;
	global $webxpay_db_version;

	$table_name = $wpdb->prefix . 'webxpay_ipg';
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
	  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
	  $charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$sql = "CREATE TABLE $table_name (
				id int(9) NOT NULL AUTO_INCREMENT,
				order_id int(9) NOT NULL,
				order_reference_number VARCHAR(20) DEFAULT NULL,					
				currency_code VARCHAR(6) NOT NULL,
				amount VARCHAR(20) NOT NULL,					
				date_added datetime NOT NULL,
				date_time_transaction datetime DEFAULT '0000-00-00 00:00:00',
				payment_gateway_id int(5) DEFAULT 0,
				status_code VARCHAR(10) DEFAULT NULL,
				comment VARCHAR(100) DEFAULT NULL,
				UNIQUE KEY id (id)
			) CHARSET=utf8;"; 
									

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'webxpay_db_version', $webxpay_db_version );
}

function webxpay_install_data() {
	global $wpdb;
	
	$welcome_name = 'WebXpay';
	$welcome_text = 'Congratulations, you just completed the installation!';
	
	$table_name = $wpdb->prefix . 'webxpay_ipg';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'name' => $welcome_name, 
			'text' => $welcome_text, 
		) 
	);
}

register_activation_hook( __FILE__, 'webxpay_install' );
register_activation_hook( __FILE__, 'webxpay_install_data' );