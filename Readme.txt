=== WebXpay Payment Gateway for WooCommerce ===
Contributors: webxpay
Tags: visa, master, amex, ezcash, mcash, sampath vishwa, sri lanka, lanka, ipg, payment, gateway, gateways
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The WebXpay payment gateway allows a simple, fast and secure way to accept online payments in Sri Lanka.

== Description ==

The WebXpay payment gateway allows a simple, fast and secure way to accept online payments in Sri Lanka. You can now accept online payments from credit/debit cards, mobile wallets and internet banking networks and also offer your customers installment payment options online too. WebXpay supports Visa, MasterCard, AMEX, eZ-Cash, mCash and major Sri Lankan Internet banking networks.

== Installation ==

How to install the plugin

1. Install plugin "webxpay-woocommerce.zip"
2. Update webxpay checkout configuration

How to create a custom Thank You page

1. Install 'insert Php' and 'php code widget' plugins
2. Create a page called "Thank You" 
3. Copy the following code snippet to the above page

[insert_php]
 if(isset($_GET['webxpay'])){
	   $status = $_GET['status'];  
	   $order_id = $_GET['unidue'];         
	   if($status == '0')
	   {
			$tmassage = ' <h2 style="text-transform: capitalize;">Payment Unsuccess.</h2><p>Sorry we didn`t receive your payment for order reference #'.$order_id.'</p> <br/> <p>Please Try Again</p>';
	   }else if($status == '1')
	   {
			$tmassage = ' <h2 style="text-transform: capitalize;">Payment Success.</h2><p>Thank you for your payment for order reference #'.$order_id.'</p>';
	   }
	   echo $tmassage;
 }    
 
 [/insert_php]
	 
== Frequently Asked Questions ==

= How can you sign up for a WebXpay account? =

Please visit https://www.webxpay.com to sign up for a merchant account.
