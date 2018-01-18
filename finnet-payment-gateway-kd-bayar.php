<?php

class finnet_payment_kd_bayar extends WC_Payment_Gateway {

	function __construct() {

		// global ID
		$this->id = "finnet-kode-bayar";

		// Show Title
		$this->method_title = __( "Finpay Kode Bayar", 'finnet-kode-bayar' );

		// Show Description
		$this->method_description = __( "Finnet Kode Bayar Plug-in for WooCommerce", 'finnet-kode-bayar' );

		// vertical tab title
		$this->title = __( "Finnet Kode Bayar", 'finnet-kode-bayar' );


		$this->icon = null;

		$this->has_fields = true;

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
		add_action( 'woocommerce_thankyou_custom', array( $this, 'thankyou_page' ) );

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // Here is the  End __construct()
	
    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
		echo '<select id="bank" name="nm_bank">
					<option>--Select Bank--</option>
        			<option value="MANDIRI">MANDIRI</option>
                    <option value="BNI">BNI</option>
                    <option value="BRI">BRI</option>
                    <option value="BCA">BCA</option>
                    <option value="Danamon">Danamon</option>
                    <option value="Permata">Permata</option>
                    <option value="BII">BII</option>
                    <option value="CIMB Niaga">CIMB Niaga</option>
                    <option value="OCBC NISP">OCBC NISP</option>
                    <option value="BTN">BTN</option>
                    <option value="Panin">Bank Panin</option>
                    <option value="MEGA">MEGA</option>
                    <option value="BUKOPIN">BUKOPIN</option>
                    <option value="Syariah Mandiri">Syariah Mandiri</option>
                    <option value="Mega Syariah">Mega Syariah</option>
                    <option value="BRI Syariah">BRI Syariah</option>			
                    <option value="BJB">Bank BJB</option>
                    <option value="Buana">Buana</option>
                    <option value="Ekonomi">Ekonomi</option>
                    <option value="Mayapada">Mayapada</option>
                    <option value="Muamalat">Muamalat</option>
                    <option value="HSBC">HSBC</option>
                    <option value="Bumiputera">Bumiputera</option>
                    <option value="BPRKS">BPRKS</option>
                    <option value="Delima eMoney">Delima eMoney</option>
      </select>';
      echo '<select id="channel" name="nm_channel">
        <option value="">Channel</option>
        <option value="ATM">ATM</option>
                    <option value="Internet Banking">Internet Banking</option>
                    <option value="Mobile Banking">Mobile Banking</option>
      </select>';
    }
    

    // administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'finnet-kode-bayar' ),
				'label'		=> __( 'Enable this payment gateway', 'finnet-kode-bayar' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'finnet-kode-bayar' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-kode-bayar' ),
				'default'	=> __( 'Transfer Bank', 'finnet-kode-bayar' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'finnet-kode-bayar' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-kode-bayar' ),
				'default'	=> __( 'Pembayaran dengan metode transfer bank', 'finnet-kode-bayar' ),
				'css'		=> 'max-width:450px;'
			),
			'merchant_id' => array(
				'title'		=> __( 'Merchant ID', 'finnet-kode-bayar' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet Merchant ID', 'finnet-kode-bayar' ),
			),
			'password' => array(
				'title'		=> __( 'Password', 'finnet-kode-bayar' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet password', 'finnet-kode-bayar' ),
			),
			'environment' => array(
				'title'		=> __( 'Test Mode', 'finnet-kode-bayar' ),
				'label'		=> __( 'Enable Test Mode', 'finnet-kode-bayar' ),
				'type'		=> 'checkbox',
				'description' => __( 'This is the test mode of gateway.', 'finnet-kode-bayar' ),
				'default'	=> 'no',
			)
		);		
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		global $woocommerce;
        
		$invoice = $_POST['invoice'];
		$result_code = $_POST['result_code'];
		
		$customer_order = new WC_Order( $invoice );
		
		if($result_code == '00'){
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet processing payment.', 'finnet-kode-bayar' ) );
												 
			// paid order marked
			$customer_order->update_status('processing');

			return array('result' => 'success');
		}elseif ($result_code == '05') {
			// Payment expired
			$customer_order->add_order_note( __( 'Finnet expired payment.', 'finnet-kode-bayar' ) );
												 
			// expired order marked
			$customer_order->update_status('failed');

			return array('result' => 'success');
		}
	}
    
    
	// Response handled for payment gateway
	public function process_payment( $order_id ) {
        
        global $woocommerce;
        
		$customer_order = new WC_Order( $order_id );
		
		// checking for transiction
		$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$environment_url = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';

		
		$add_info1 = $customer_order->billing_first_name.' '.$customer_order->billing_last_name;
		$amount = $customer_order->order_total;
		$invoice = $order_id;
		$merchant_id = $this->merchant_id;
		$return_url = add_query_arg('utm_nooverride','1',$this->get_return_url($order));
		$sof_id = 'finpay021';
		$sof_type = 'pay';
		$timeout = '43200';
		$trans_date = date('Ymdhis');
		$password = $this->password;
		
		$mer_signature = hash('sha256', strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($invoice).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password));
		$ref = strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($invoice).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password);
		// This is where the fun stuff begins
		$payload = array(
			'add_info1' => $add_info1,
			'amount' => $amount,
			'invoice' => $invoice,
			'mer_signature' => $mer_signature,
			'merchant_id' => $merchant_id,
			'return_url' => $return_url,
			'sof_id' => $sof_id,
			'sof_type' => $sof_type,
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
			throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'finnet-tcash' ) );
		}else{
			update_post_meta( $order_id, '_finpay_ref_kdbayar', $ref);
		}
		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Finnet Response was not get any data.', 'finnet-kode-bayar' ) );
			
		// get body response while get not error
		$response_body = wp_remote_retrieve_body( $response );
		
		$response = json_decode($response_body, true);
		
		if($response['status_code'] == 00){
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet pending payment.', 'finnet-kode-bayar' ) );
												 
			// paid order marked
			$customer_order->update_status('pending-payment');

			$to_email = $customer_order->billing_email;
			$headers .= "MIME-Version: 1.0";
			$headers .= "Content-Type: text/html; charset=UTF-8";
			$message = '<div style=" display: block;position: relative;max-width: 50%;min-width: 300px;height: auto;margin: 25px auto;background: #ffffff;box-shadow: 0px 0px 10px #888888;font-family: Lato, sans-serif;font-size: 14px;">
					<div style=" text-align:center;">
					<img src="https://www.sarinahonline.co.id/wp-content/uploads/2017/12/sarinah-thewindowofinonesia-02-1.png" style="width:40%; margin: 15px 15px " >
					</div>    
					<h2 style="padding: 15px;background: #ff0000;color: #ffffff;text-align: center;border-bottom: 5px solid #cc9933;">
						Petunjuk Pembayaran
					</h2>
						<p style="padding: 15px 5%;color: #333333;">
							
							
							<b>Silakan ikuti langkah-langkah berikut untuk menyelesaikan pembayaran :</b><br>
							<ul style="list-style:decimal; padding-left: 10%;">
								<li style="margin-bottom: 10px;color: #333333;"> Pilih Menu Bayar / Beli</li>
								<li style="margin-bottom: 10px;color: #333333;"> Pilih Menu Telepon / HP</li>
								<li style="margin-bottom: 10px;color: #333333;"> Pilih CDMA / Telkom</li>
								<li style="margin-bottom: 10px;color: #333333;"> Pilih Telkom / Speedy Vision</li>
								<li style="margin-bottom: 10px;color: #333333;"> Masukkan Kode 12 Digit "'.$response['payment_code'].'" kode pembayaran yang anda dapatkan</li>
								<li style="margin-bottom: 10px;color: #333333;"> Pilih YA untuk melanjutkan pembayaran</li>
							</ul>
						</p>
						<div style="display: block;width: auto;background: #f2f2f2;border-top: 1px solid #eeeeee;padding: 25px 5%;text-align: center;color: #888888;">
								&copy;2017<a href="https://www.sarinahonline.co.id/" style="color: #888888;"> Sarinah Online.</a> All rights Reserved
						</div>
				
				</div>';
			
			wp_mail($to_email, 'Your order is Pending', $message, $headers );

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			$kode_bayar = add_query_arg('payment_code', $response['payment_code'], $this->get_return_url($customer_order));
			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $kode_bayar,
			);
		} else {
			//transiction fail
			wc_add_notice( $r['response_reason_text'], 'error' );
			$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
		}
		
	}

	public function check_order($order_id) {
		
		global $woocommerce;

		$order_ref = get_post_meta($order_id, '_telr_ref', true);

		$data = array(
			'ivp_method'	=> "check",
			'ivp_store'	=> $this->store_id ,
			'order_ref'	=> $order_ref,
			'ivp_authkey'	=> $this->store_secret,
			);

		$response = $this->api_request($data);
		var_dump($data);die;
		$order_status_arr = array(2,3);
		$transaction_status_arr = array('A', 'H');

		if (array_key_exists("order", $response)) {
			$order_status = $response['order']['status']['code'];
			$transaction_status = $response['order']['transaction']['status'];
			if ( in_array($order_status, $order_status_arr) && in_array($transaction_status, $transaction_status_arr)) {
				return true;
			}
		}
		return false;
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

add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );
	function add_custom_gateway_class( $methods ) {
		$methods[] = 'finnet_payment_kd_bayar'; 
		return $methods;
	}

	add_action('woocommerce_checkout_process', 'process_custom_payment');
	function process_custom_payment(){

		if($_POST['payment_method'] != 'finnet-kode-bayar')
			return;

		if( !isset($_POST['nm_bank']) || empty($_POST['nm_bank']) )
			wc_add_notice( __( 'Please select bank name' ), 'error' );


		if( !isset($_POST['nm_channel']) || empty($_POST['nm_channel']) )
			wc_add_notice( __( 'Please select channel'), 'error' );

	}
	add_filter( 'wp_mail_content_type', 'set_html_content_type' );
	function set_html_content_type() 
{
  return 'text/html';
}
	/*function order_completed( $order_id ) {
		$order = new WC_Order( $order_id );
		$to_email = $order["billing_address"];
		$headers = 'From: Your Name <your@email.com>' . "\r\n";
		wp_mail($to_email, 'subject', 'message', $headers );

		
	
	}
	
	add_action( 'woocommerce_payment_complete', 'order_completed' );*/
