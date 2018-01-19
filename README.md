# finnet_payment_gateway
plugin woocomerce payment gateway finnet

tambahkan potongan source dibawah ini, pada file wp-content/plugin/woocommerce/templates/checkout/thankyou.php dibawah `<div class="woocommerce-order">`

```
<?php
   if($_GET['utm_nooverride'] == 1){
                 do_action( 'woocommerce_thankyou_custom', $_POST);
        }else{
                data = $_GET['utm_nooverride'];
                $failed = $_GET['failed'] ? $_GET['failed'] : '0';
                $invoice = substr($data, strpos($data, "=") + 1);

                $data = ['invoice' => $invoice, 'failed' => $failed];

                do_action( 'woocommerce_thankyou_custom', $data);
        }
?>
```
