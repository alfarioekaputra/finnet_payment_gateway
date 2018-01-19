<?php

class finnet_Payment_CC extends WC_Payment_Gateway {

	function __construct() {

		// global ID
		$this->id = "finnet-cc";

		// Show Title
		$this->method_title = __( "Finpay CC", 'finnet-cc' );

		// Show Description
		$this->method_description = __( "Finnet CC Plug-in for WooCommerce", 'finnet-cc' );

		// vertical tab title
		$this->title = __( "Finpay CC", 'finnet-cc' );


		$this->icon = null;

		$this->has_fields = true;

		// support default form with credit card
		//$this->supports = array( 'default_credit_card_form' );

		// setting defines
		$this->init_form_fields();

		// load time variable setting
        $this->init_settings();
        
        $this->enabled			= $this->get_option('enabled');
        $this->title			= $this->get_option('title');
        $this->merchant_id		= $this->get_option('merchant_id');
        $this->password			= $this->get_option('password');
        $this->testmode			= $this->get_option('testmode');
            
        // Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// further check of SSL if you want
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		add_action( 'woocommerce_thankyou_cc', array( $this, 'thankyou_page_cc' ) );
        
		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
    } // Here is the  End __construct()
    

    // administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'finnet-cc' ),
				'label'		=> __( 'Enable this payment gateway', 'finnet-cc' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'finnet-cc' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-cc' ),
				'default'	=> __( 'Credit Card', 'finnet-cc' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'finnet-cc' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-cc' ),
				'default'	=> __( 'Pembayaran dengan metode Credit Card', 'finnet-cc' ),
				'css'		=> 'max-width:450px;'
			),
			'merchant_id' => array(
				'title'		=> __( 'Merchant ID', 'finnet-cc' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet Merchant ID', 'finnet-cc' ),
			),
			'password' => array(
				'title'		=> __( 'Password', 'finnet-cc' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet password', 'finnet-cc' ),
			),
			'environment' => array(
				'title'		=> __( 'Test Mode', 'finnet-cc' ),
				'label'		=> __( 'Enable Test Mode', 'finnet-cc' ),
				'type'		=> 'checkbox',
				'description' => __( 'This is the test mode of gateway.', 'finnet-cc' ),
				'default'	=> 'no',
			)
		);		
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page_cc($data) {
		global $woocommerce;
        
		$invoice = $data['invoice'];

		$customer_order = new WC_Order( $invoice );
		
		if($data['failed'] != 1){
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet processing payment cc.', 'finnet-cc' ) );
												 
			// paid order marked
			$customer_order->update_status('processing');
			
			return array('result' => 'success');
		}else {
			// Payment failed
			$customer_order->add_order_note( __( 'Finnet failed payment.', 'finnet-cc' ) );
												 
			// failed order marked
			$customer_order->update_status('failed');

			return array('result' => 'success');
		}
	}
    
    
	// Response handled for payment gateway
	public function process_payment( $order_id ) {
        
        global $woocommerce;
        
		$customer_order = new WC_Order( $order_id );
		
		$data = [];
		// Iterating through each WC_Order_Item_Product objects
		foreach ($customer_order->get_items() as $item_key => $item_values):

			## Using WC_Order_Item methods ##

			// Item ID is directly accessible from the $item_key in the foreach loop or
			$item_id = $item_values->get_id();

			## Using WC_Order_Item_Product methods ##

			$item_name = $item_values->get_name(); // Name of the product
			$item_type = $item_values->get_type(); // Type of the order item ("line_item")

			$product_id = $item_values->get_product_id(); // the Product id
			$wc_product = $item_values->get_product(); // the WC_Product object
			## Access Order Items data properties (in an array of values) ##
			$item_data = $item_values->get_data();

			$data[] = [$item_data['name'],$item_data['total'],$item_data['quantity']];

		endforeach;
		
		// checking for transiction
		$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$environment_url = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';

		$return_url = add_query_arg(array('jenis' => 'cc','utm_nooverride'=>'2'),$this->get_return_url($customer_order));
		$failed_url = add_query_arg('failed','1',$this->get_return_url($customer_order));
		$sof_id = 'cc';
		
		$add_info1 = $customer_order->billing_first_name.' '.$customer_order->billing_last_name;
		$amount = strtok($customer_order->order_total, '.');;
        $cust_email = $customer_order->billing_email;
        $cust_id = $customer_order->get_customer_id();
        $cust_msisdn = $customer_order->billing_phone;
		$cust_name = $add_info1;
		$failed_url = $failed_url;
		$invoice = $order_id;
		$items = json_encode($data);
        $merchant_id = $this->merchant_id;
		$sof_type = 'pay';
		$success_url = $return_url;
        $timeout = '2880';
		$trans_date = date('Ymdhis');
        $password = $this->password;

		$mer_signature = hash('sha256', strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($cust_email).'%'.strtoupper($cust_id).'%'.strtoupper($cust_msisdn).'%'.strtoupper($cust_name).'%'.strtoupper($failed_url).'%'.strtoupper($invoice).'%'.strtoupper($items).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($success_url).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password));
		$ref = strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($cust_email).'%'.strtoupper($cust_id).'%'.strtoupper($cust_msisdn).'%'.strtoupper($cust_name).'%'.strtoupper($failed_url).'%'.strtoupper($invoice).'%'.strtoupper($items).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($success_url).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password);
		
		// This is where the fun stuff begins
		$payload = array(
			'add_info1' => $add_info1,
            'amount' => $amount,
            'cust_email' => $cust_email,
            'cust_id' => $cust_id,
            'cust_msisdn' => $cust_msisdn,
			'cust_name' => $cust_name,
			'failed_url' => $failed_url,
			'invoice' => $invoice,
			'items' => $items,
			'mer_signature' => $mer_signature,
			'merchant_id' => $merchant_id,
			'return_url' => $return_url,
			'sof_id' => $sof_id,
			'sof_type' => $sof_type,
			'success_url' => $success_url,
			'timeout' => $timeout,
			'trans_date' => $trans_date
			
        );
        
		// Send this payload to Authorize.net for processing
		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'body'      => http_build_query( $payload ),
			'timeout'   => 90,
			'sslverify' => true,
        ) );
        
			
		if ( is_wp_error( $response ) ){
			throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'finnet-cc' ) );
		}else{
			update_post_meta( $order_id, '_finpay_ref_cc', $ref);
		}

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Finnet Response was not get any data.', 'finnet-cc' ) );
			
		// get body response while get not error
		$response_body = wp_remote_retrieve_body( $response );
		//var_dump($response_body);die;
		$response = json_decode($response_body, true);
		
		if($response['status_code'] == '00'){
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet pending payment cc.', 'finnet-cc' ) );
            
			// paid order marked
			$customer_order->update_status('pending-payment');

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			$kode_bayar = add_query_arg(array('payment_code' => $response['payment_code'], 'nm_bank' => $_POST['nm_bank']), $this->get_return_url($customer_order));
            
            // Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $response['redirect_url'],
			);
		} else {
			//transiction fail
			wc_add_notice( $r['response_reason_text'], 'error' );
			$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
		}
		
	}

	
	// Validate fields
	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

}

add_filter( 'woocommerce_payment_gateways', 'gateway_class_cc' );
function gateway_class_cc( $methods ) {
	$methods[] = 'finnet_Payment_CC'; 
	return $methods;
}