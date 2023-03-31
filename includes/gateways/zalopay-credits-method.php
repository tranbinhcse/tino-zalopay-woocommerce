<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZaloPayCredits extends TinoZaloPayGateway {

	public function __construct() {

        $this->id                 = 'zalopay_credits';
        $this->icon = sprintf("%s/public/images/zalopay-icon.png",TINO_ZALOPAY_PLUGIN_URL);
        $this->has_fields         = false;
        //$this->order_button_text  = __( 'Thanh Toán', 'woocommerce' );
        $this->method_title       = __( 'ZaloPay Credit Card', 'woocommerce' );
        $this->method_description = 'Thanh toán bằng thẻ Credit Card tại site ZaloPay';
        $this->supports           = array(
          'subscriptions',
          'products',
          'subscription_cancellation',
          'subscription_reactivation',
          'subscription_suspension',
          'subscription_amount_changes',
          'subscription_payment_method_change',
          'subscription_date_changes',
          // 'default_credit_card_form',
          'refunds',
          'pre-orders'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title                       = $this->get_option( 'title' );
        $this->description                 = $this->get_option( 'description' );
        $this->method_description          = 'Thanh toán bằng thẻ Credit Card cổng thanh toán ZaloPay';
        $this->sandbox                     = 'yes' === $this->get_option( 'sandbox', 'no' );
        $this->debug                       = 'yes' === $this->get_option( 'debug', 'no' );
        $this->finish_notify_text          = $this->get_option( 'finish_notify_text' );
        $this->sandbox                     = $this->get_option( 'sandbox' );
        $this->appname                       = $this->get_option( 'appname' );
        $this->appid                       = $this->get_option( 'appid' );
        $this->key1                        = $this->get_option( 'key1' );
        $this->key2                        = $this->get_option( 'key2' );
        $this->publickey                   = 'MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAOfB6/x0b5UiLkU3pOdcnXIkuCSzmvlVhDJKv1j3yBCy vsgAHacVXd+7WDPcCJmjSEKlRV6bBJWYam5vo7RB740CAwEAAQ==';
        $this->zalopayurl                  = $this->sandbox ? 'https://sandbox.zalopay.com.vn/v001/tpe/' : 'https://zalopay.com.vn/v001/tpe/';
        $this->currency_convert            = $this->get_option( 'currency_convert' ) ? $this->get_option( 'currency_convert' ) : 1;

        self::$log_enabled    = true;

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
        // Customer Emails.
        //add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        // add_action( 'woocommerce_view_order', array( $this, 'thankyou_page' ), 1, 1 );
        // add_action('admin_notices', array($this,'show_notify'));

        add_action( 'woocommerce_api_zalopay', array( $this, 'zalopay_callback' ) );
        // add_action( 'woocommerce_api_tino-zalopay-payment-qrcode', array( $this, 'webhook_zalopay' ) );
    }


    public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $order->update_status('pending', __( 'Đơn hàng chờ thanh toán', 'woocommerce' ));
        $currency = get_woocommerce_currency();


        $zalopay_notifyUrl = get_home_url().'/wc-api/zalopay';
        // $returnUrl = $order->get_checkout_payment_url();
        $returnUrl = $this->get_return_url( $order );

        $app_trans_id = date('ymd').$order_id;

        $total = round($order->get_total());
        $total = strval($total);

        if ($currency != 'VND') {
           $totalamount = $total * $this->currency_convert;
        } else {
          $totalamount = $total;
        }

        $amount = round($totalamount);
        $amount = strval($amount);

        $bankcode = 'CC';

        $app_user = $this->appname;
        $app_time = $this->getTimestamp();
        $embed_data = array(
          //'promotioninfo' => '',
          //'merchantinfo' => $this->appname,
          'bankgroup' => 'CC',
          'redirecturl' => $returnUrl,
          // 'zlppaymentid' => 'ZalopayAtm',
        );

        $products = array();
        foreach ( $order->get_items() as $product_id => $product ) {
           $product_id = $product->get_product_id();
           $name = $product->get_name();
           $quantity = $product->get_quantity();
           $itemprice = $product->get_total();
           $products[] = array(
             'itemid' => $product_id,
             'itename' => $name,
             'itemprice'=> $itemprice,
             'itemquantity' => $quantity,
           );
        }

        $item = $products;
        $mac = hash_hmac('sha256', $this->appid . '|' . $app_trans_id . '|' . $app_user . '|' .  $amount . '|' . $app_time . '|' . json_encode($embed_data) . '|' . json_encode($item), $this->key1);
        $params = array(
          'appid' => $this->appid,
          'appuser' => $app_user,
          'apptransid' => $app_trans_id,
          'apptime' => $app_time,
          'amount' => $amount,
          'description' => 'Thanh toán đơn hàng '.$order_id . ' tại ' .$app_user,
          'item' => json_encode($item),
          'embeddata' => json_encode($embed_data),
          'mac' => $mac,
          'bankcode' => $bankcode,
          'email' => '',
          'phone' => '',
          'address' => ''
        );
        $zalopayorder = $this->zalopay_postForm($this->zalopayurl . 'createorder', $params);
         if ($zalopayorder['returncode'] == 1) {
           $zp_trans_token = $zalopayorder['zptranstoken'];
           $order_url = $zalopayorder['orderurl'];
            $woocommerce->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $order_url
            );
        }
    }



        public function thankyou_page( $order_id ) {
          $order = new WC_Order($order_id);
          if($order->get_status() =='completed' || $order->get_payment_method() != $this->id) return;

          if ($order->get_status() =='pending' && $order->get_payment_method() == $this->id) {
            $zalostatus =  $this->zalopay_getstatus($order_id);
            if ($zalostatus) {
               if ($zalostatus['returncode'] === 1) {

                 $transID = $order->get_transaction_id();
                 if ($transID === $zalostatus['zptransid']) {

                 } else {
                   $total = round($order->get_total());
                   $total = strval($total);
                   if ($currency != 'VND') {
                      $totalamount = $total * $this->currency_convert;
                   } else {
                     $totalamount = $total;
                   }
                   $amount = round($totalamount);
                   $amount = strval($amount);
                   if ($zalostatus['amount'] == $amount) {
                     $order->payment_complete(esc_html($zalostatus['zptransid']));
                     $order->reduce_order_stock();
                     $order->add_order_note( 'ZaloPay ZTransId: ' .esc_html($zalostatus['zptransid']), false );
                     $order->add_order_note( 'ZaloPay AppTransId: ' .esc_html($zalostatus['apptransid']), false );
                   } else {
                     $order->add_order_note('Số tiền không đủ. ZaloPay TransId: ' .esc_html($zalostatus['zptransid']), false );
                   }
                 }
               }
            }
          }


          if ($order->get_status() =='pending' && $order->get_payment_method() == $this->id) {
            ?>
            <div id="frame-thanhtoan">
                   <h3>Đơn hàng chưa thanh toán thành công, vui lòng thử lại</h3>
                 <?php $url = $order->get_checkout_payment_url(); ?>
                 <a href="<?php echo $url ?>" class="btn button">Thanh toán</a>
            </div>

            <?php
          }
        }



}
