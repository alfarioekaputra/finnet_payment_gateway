<?php

class finnet_Payment_VA extends WC_Payment_Gateway {

	function __construct() {

		// global ID
		$this->id = "finnet-va";

		// Show Title
		$this->method_title = __( "Finpay Virtual Account", 'finnet-va' );

		// Show Description
		$this->method_description = __( "Finnet Kode Bayar Plug-in for WooCommerce", 'finnet-va' );

		// vertical tab title
		$this->title = __( "Finpay Virtual Account", 'finnet-va' );


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
            
        // Actions
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action( 'woocommerce_thankyou', array($this, 'update_order_status'));

       
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
                    <option value="bni">BNI Virtual Account</option>
        			<option value="permata">Permata Virtual Account</option>
                </select>';
      
    }
    

    // administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'finnet-va' ),
				'label'		=> __( 'Enable this payment gateway', 'finnet-va' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'finnet-va' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-va' ),
				'default'	=> __( 'Virtual Account', 'finnet-va' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'finnet-va' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'finnet-va' ),
				'default'	=> __( 'Pembayaran dengan metode virtual account', 'finnet-va' ),
				'css'		=> 'max-width:450px;'
			),
			'merchant_id' => array(
				'title'		=> __( 'Merchant ID', 'finnet-va' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet Merchant ID', 'finnet-va' ),
			),
			'password' => array(
				'title'		=> __( 'Password', 'finnet-va' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Enter your finnet password', 'finnet-va' ),
			),
			'environment' => array(
				'title'		=> __( 'Test Mode', 'finnet-va' ),
				'label'		=> __( 'Enable Test Mode', 'finnet-va' ),
				'type'		=> 'checkbox',
				'description' => __( 'This is the test mode of gateway.', 'finnet-va' ),
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
			$customer_order->add_order_note( __( 'Finnet processing payment.', 'finnet-va' ) );
												 
			// paid order marked
			$customer_order->update_status('processing');

			return array('result' => 'success');
		}elseif ($result_code == '05') {
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet expired payment.', 'finnet-va' ) );
												 
			// paid order marked
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

		$return_url = add_query_arg('utm_nooverride','4',$this->get_return_url($order));
		//echo $return_url;die;
		if($_POST['nm_bank'] == 'permata'){
            $sof_id = 'vapermata';
		}elseif($_POST['nm_bank'] == 'bni'){
            $sof_id = 'vastbni';
        }

		$add_info1 = $customer_order->billing_first_name.' '.$customer_order->billing_last_name;
		$amount = strtok($customer_order->order_total, '.');;
        $cust_email = $customer_order->billing_email;
        $cust_id = $customer_order->get_customer_id();
        $cust_msisdn = $customer_order->billing_phone;
        $cust_name = $add_info1;
        $invoice = $order_id;
        $merchant_id = $this->merchant_id;
        $sof_type = 'pay';
        $timeout = '43200';
		$trans_date = date('Ymdhis');
        $password = $this->password;

		$mer_signature = hash('sha256', strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($cust_email).'%'.strtoupper($cust_id).'%'.strtoupper($cust_msisdn).'%'.strtoupper($cust_name).'%'.strtoupper($invoice).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password));
        $ref = strtoupper($add_info1).'%'.strtoupper($amount).'%'.strtoupper($cust_email).'%'.strtoupper($cust_id).'%'.strtoupper($cust_msisdn).'%'.strtoupper($cust_name).'%'.strtoupper($invoice).'%'.strtoupper($merchant_id).'%'.strtoupper($return_url).'%'.strtoupper($sof_id).'%'.strtoupper($sof_type).'%'.strtoupper($timeout).'%'.strtoupper($trans_date).'%'.strtoupper($password);
		// This is where the fun stuff begins
		$payload = array(
			'add_info1' => $add_info1,
            'amount' => $amount,
            'cust_email' => $cust_email,
            'cust_id' => $cust_id,
            'cust_msisdn' => $cust_msisdn,
            'cust_name' => $cust_name,
			'invoice' => $invoice,
			'mer_signature' => $mer_signature,
			'merchant_id' => $merchant_id,
			'return_url' => $return_url,
			'sof_id' => $sof_id,
			'sof_type' => $sof_type,
			'timeout' => $timeout,
			'trans_date' => $trans_date
			
        );
        
		//var_dump($customer_order->billing_email);die;
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
			update_post_meta( $order_id, '_finpay_ref_va', $ref);
		}

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Finnet Response was not get any data.', 'finnet-va' ) );
			
		// get body response while get not error
		$response_body = wp_remote_retrieve_body( $response );
		
		$response = json_decode($response_body, true);
		
		//var_dump($response);die;
		if($response['status_code'] == '00'){
			// Payment successful
			$customer_order->add_order_note( __( 'Finnet pending payment.', 'finnet-va' ) );
            
			// paid order marked
			$customer_order->update_status('pending-payment');

			$to_email = $cust_email;
			$headers .= "MIME-Version: 1.0";
            $headers .= "Content-Type: text/html; charset=UTF-8";
            
            if($sof_id == 'vapermata'){
                
			$message = '<b>Silakan ikuti langkah-langkah berikut untuk menyelesaikan pembayaran </b>
			<ol>
<li>Silahkan pilih menu <strong>Transaksi Lainnya</strong>. Setelah itu klik menu <strong>Transfer</strong> lalu klik menu<strong> Rek NSB Lain Permata</strong></li>
<li>Masukkan nomor rekening dengan nomor Virtual Account Anda ('.$response['payment_code'].') dan pilih <strong>Benar</strong></li>
<li>Kemudian masukkan <strong>jumlah nominal transaksi</strong> sesuai dengan invoice yang ditagihkan pada anda. Setelah itu pilih <strong>Benar</strong></li>
<li>Lalu <strong>pilih rekening</strong> anda. Tunggu sebentar hingga muncul konfirmasi pembayaran. Kemudian pilih <strong>Ya</strong></li>
</ol>';
            }elseif($sof_id == 'vastbni'){
                $message = '<div class="content-body article-body">
                <h1>No Virtual Account <b>'.$response['payment_code'].'</b></h1>
                <p><strong>Langkah-langkah melakukan pembayaran melalui Rekening BNI Virtual Account adalah sebagai berikut:</strong></p>
            <p><strong>Via ATM</strong></p>
            <ul>
            <li>Masukkan kartu, pilih bahasa kemudian masukkan PIN Anda</li>
            <li>Pilih "Menu Lainnya" lalu pilih "Transfer"</li>
            <li>Pilih "Tabungan" lalu "Rekening BNI Virtual Account"</li>
            <li>Masukkan nomor Virtual Account dan nominal yang ingin Anda bayar</li>
            <li>Periksa kembali data transaksi kemudian tekan "Ya"</li>
            </ul>
            <p><strong>Via Internet Banking</strong></p>
            <ul>
            <li>Login di <a href="https://ibank.bni.co.id/">https://ibank.bni.co.id</a>, masukkan User ID dan Password</li>
            <li>Pilih “TRANSFER” lalu pilih “Tambah Rekening Favorit”</li>
            <li>Jika Anda menggunakan desktop untuk menambah rekening, pilih “Transaksi” lalu pilih “Atur Rekening Tujuan” kemudian “Tambah Rekening Tujuan”</li>
            <li>Masukkan Nama dan nomor Virtual Account Anda, lalu masukkan Kode Otentikasi Token</li>
            <li>Jika Nomor rekening tujuan berhasil ditambahkan, kembali ke menu “TRANSFER”</li>
            <li>Pilih “TRANSFER ANTAR REKENING BNI”, kemudian pilih rekening tujuan</li>
            <li>Pilih Rekening Debit dan ketik nominal, lalu masukkan kode otentikasi token</li>
            </ul>
            <p><strong>Via SMS Banking</strong></p>
            <ul>
            <li>Buka aplikasi SMS Banking BNI, pilih menu Transfer</li>
            <li>Masukkan nomor Virtual Account pada kolom "No. Rekening Tujuan"</li>
            <li>Masukkan "Jumlah Transfer", klik Proses</li>
            <li>Pilih "Transfer" kemudian "Send"</li>
            <li>Balas SMS konfirmasi dengan ketik PIN Anda digit ke-2 &amp; 6, klik "Yes"</li>
            <li>Atau SMS dan kirim ke 3346 dengan format:<br>TRF[SPASI]NOMOR BNI Virtual Account[SPASI]NOMINAL</li>
            </ul>
            <p><strong>Via Mobile Banking</strong></p>
            <ul>
            <li>Login ke BNI Mobile Banking, masukkan User ID dan MPIN</li>
            <li>Pilih menu "Transfer", lalu pilih "Antar Rekening BNI"</li>
            <li>Pilih "Input Rekening Baru"</li>
            <li>Masukkan "Rekening Debet", "Rekening Tujuan" dan "Nominal" kemudian klik "Lanjut"</li>
            <li>Periksa kembali data transaksi Anda, masukkan "Password Transaksi" kemudian klik "Lanjut</li>
            </ul>
            <p><strong>Via Teller / Counter Bank</strong></p>
            <ul>
            <li>Isi formulir Setoran Rekening</li>
            <ul>
            <li>Pilih setoran tunai &amp; tulis jumlah nominal setoran</li>
            <li>Isi nama pemilik rekening, nomor rekening virtual, nama &amp; tanda tangan penyetor</li>
            </ul>
            <li>Silahkan menuju counter teller beserta formulir setoran kemudian isi formulir pemindahbukuan rekening</li>
            <ul>
            <li>Isi nama Penerima (Nama Pemilik VA), Nomor Virtual Account, Cabang (Bank BNI) pengelola rekening &amp; jumlah uang yang akan disetorkan/dipindahbukukan</li>
            <li>Isi nama, nomor rekening penyetor dan BNI Cabang pembuka rekening</li>
            </ul>
            </ul>
              </div>';
            }
			wp_mail($to_email, 'Your order is Pending', $message, $headers );

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			$kode_bayar = add_query_arg(array('payment_code' => $response['payment_code'], 'nm_bank' => $_POST['nm_bank']), $this->get_return_url($customer_order));
            
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

add_filter( 'woocommerce_payment_gateways', 'gateway_class_va' );
	function gateway_class_va( $methods ) {
		$methods[] = 'finnet_Payment_VA'; 
		return $methods;
	}

	add_action('woocommerce_checkout_process', 'process_custom_payment_va');
	function process_custom_payment_va(){

		if($_POST['payment_method'] != 'finnet-va')
			return;

		if( !isset($_POST['nm_bank']) || empty($_POST['nm_bank']) )
			wc_add_notice( __( 'Please select bank name' ), 'error' );

	}

	/*function order_completed( $order_id ) {
		$order = new WC_Order( $order_id );
		$to_email = $order["billing_address"];
		$headers = 'From: Your Name <your@email.com>' . "\r\n";
		wp_mail($to_email, 'subject', 'message', $headers );

		
	
	}
	
	add_action( 'woocommerce_payment_complete', 'order_completed' );*/
