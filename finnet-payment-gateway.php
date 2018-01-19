<?php

/*
Plugin Name: Finnet Payment Gateway
Plugin URI: http://www.url.com/
Description: WooCommerce custom payment gateway integration on finnet.
Version: 1.5.6
*/

add_action( 'plugins_loaded', 'finnet_payment_gateway', 0 );
function finnet_payment_gateway() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'finnet-payment-gateway-kd-bayar.php' );
	include_once('finnet-payment-gateway-va.php');
	include_once('finnet-payment-gateway-tcash.php');
	include_once('finnet-payment-gateway-cc.php');
	// class add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_finnet_payment_gateway' );
	function add_finnet_payment_gateway( $methods ) {
		$methods[] = 'finnet_payment_kd_bayar';
		$methods[] = 'finnet_payment_VA';
		$methods[] = 'finnet_payment_TCash';
		$methods[] = 'finnet_payment_CC';
		return $methods;
	}
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'finnet_payment_gateway_action_links' );
function finnet_payment_gateway_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'finnet-payment-gateway' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}
