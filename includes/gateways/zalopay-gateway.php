<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Gateway_Paypal Class.
 */
class TinoZaloPayGateway extends WC_Payment_Gateway {

    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {

      add_action( 'woocommerce_api_zalopay', array( $this, 'zalopay_callback' ) );

    }



    public function show_notify(){

    }


    public function template_ifinish(){
        ?>
        <div id="finishscan" style="display: none;">
            <p class="questionfinish">Bạn đã thanh toán xong?</p>
            <div class="btnfinish-scan nut-animation">Tôi đã thanh toán xong</div>

            <div id="thongbaofinish" style="display: none">
                <?php echo $this->finish_notify_text; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Check if this gateway is enabled and available in the user's country.
     * @return bool
     */
    public function is_valid_for_use() {
        return true;
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
     public function init_form_fields() {


         $this->form_fields = array(
             'enabled' => array(
                 'title' => __( 'Bật/Tắt', 'woocommerce' ),
                 'type' => 'checkbox',
                 'label' => __( 'Bật cổng thanh toán này', 'woocommerce' ),
                 'default' => 'yes'
             ),
             'sandbox' => array(
                 'title' => __( 'Sandbox', 'woocommerce' ),
                 'type' => 'checkbox',
                 'label' => __( 'Bật sandbox cổng thanh toán này', 'woocommerce' ),
                 'default' => 'yes'
             ),
             'currency_convert' => array(
                 'title' => __( 'VND Rate', 'woocommerce' ),
                 'type' => 'number',
                 'label' => __( 'Tỷ giá tiền tệ', 'woocommerce' ),
                 'default' => '1'
             ),
             'title' => array(
                 'title' => __( 'Tên Cổng Thanh Toán', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'Tên cổng thanh toán mà người dùng sẽ thấy khi thanh toán', 'woocommerce' ),
                 'default' => 'ZaloPay Credit Card',
                 'desc_tip'      => true,
             ),
             'description' => array(
                 'title' => __( 'Mô Tả Cho Khách', 'woocommerce' ),
                 'type' => 'textarea',
                 'description' => __( 'Đoạn mô tả giúp khách hiểu rõ hơn cách thức thanh toán', 'woocommerce' ),
                 'default' => 'Hãy mở App ZaloPay lên và nhấn Đặt Hàng để quét mã thanh toán'
             ),
             'appname' => array(
                 'title' => __( 'ZaloPay App Name', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'ZaloPay App Name', 'woocommerce' ),
                 'default' => '',
                 'desc_tip'      => true,
             ),
             'appid' => array(
                 'title' => __( 'ZaloPay Appid', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'ZaloPay Appid', 'woocommerce' ),
                 'default' => '',
                 'desc_tip'      => true,
             ),
             'key1' => array(
                 'title' => __( 'ZaloPay Key1', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'ZaloPay Key1', 'woocommerce' ),
                 'default' => '',
                 'desc_tip'      => true,
             ),
             'key2' => array(
                 'title' => __( 'ZaloPay Key2', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'ZaloPay Key2', 'woocommerce' ),
                 'default' => '',
                 'desc_tip'      => true,
             ),
             'publickey' => array(
                 'title' => __( 'ZaloPay Public Key', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'ZaloPay Key2', 'woocommerce' ),
                 'default' => '',
                 'desc_tip'      => true,
             ),

             'finish_notify_text' => array(
                 'title' => __( 'Thông báo hoàn tất thanh toán', 'woocommerce' ),
                 'type' => 'text',
                 'description' => __( 'Khách bấm Tôi Đã Thanh Toán và sẽ thấy thông báo này', 'woocommerce' ),
                 'default' => 'Cám ơn bạn đã thanh toán. Chúng tôi sẽ kiểm tra đơn hàng và sớm liên hệ lại với bạn.',
                 'desc_tip'      => false,
             )
         );
     }


      public function thankyou_page( $order_id ) {

      }

    public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
        $order->update_status('pending', __( 'Đơn hàng chờ thanh toán', 'woocommerce' ));
        $woocommerce->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
        );
    }



        public function zalopay_callback() {

          global $woocommerce;
          $currency = get_woocommerce_currency();

          $isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';
          if ($isPostMethod) {
            try {

              $params = json_decode(file_get_contents('php://input'), true);
              $params['key2'] = $this->key2;
              # Kiểm tra xem callback có hợp lệ không
              $result = $this->verifyCallback($params);
              if ($result['returncode'] === 1) {
                # Giao dịch thành công, tiền hành xử lý đơn hàng
                $data = json_decode($params["data"], true);
                $orderid = substr($data['apptransid'],6);
                $order = wc_get_order( $orderid );
                if ($order) {

                  $transID = $order->get_transaction_id();

                  if ($transID === $data['zptransid']) {
                    echo json_encode([
                      "returncode" => 2, # ZaloPay Server sẽ callback lại tối đa 3 lần
                      "returnmessage" => "Đơn hàng đã xác nhận"
                    ]);
                    die;
                  }

                  $total = round($order->get_total());
                  $total = strval($total);
                  if ($currency != 'VND') {
                     $totalamount = $total * $this->currency_convert;
                  } else {
                    $totalamount = $total;
                  }
                  $amount = round($totalamount);
                  $amount = strval($amount);
                  if ($data['amount'] == $amount) {
                    $order->payment_complete(esc_html($data['zptransid']));
                    $order->reduce_order_stock();
                    $order->add_order_note( 'ZaloPay ZTransId: ' .esc_html($data['zptransid']), false );
                    $order->add_order_note( 'ZaloPay AppTransId: ' .esc_html($data['apptransid']), false );
                  } else {
                    $order->add_order_note('Số tiền không đủ. ZaloPay TransId: ' .esc_html($data['zptransid']), false );
                  }
                } else {

                }

              }

              echo json_encode($result);
            } catch (Exception $e) {
              echo json_encode([
                "returncode" => 0, # ZaloPay Server sẽ callback lại tối đa 3 lần
                "returnmessage" => "exception"
              ]);
            }
          }
          die;
        }

        /*
         * In case you need a Refund
         */
         public function process_refund($order_id, $amount = null, $reason = ''){

           $order = wc_get_order( $order_id );
           $z_trans_id = $order->get_transaction_id('view');
           $currency = get_woocommerce_currency();
           $timestamp = round(microtime(true) * 1000);
           $mrefundid = date('ymd') . '_' . $this->appid . '_' . $timestamp;

           if ($currency != 'VND') {
              $totalamount = $amount * $this->currency_convert;
           } else {
             $totalamount = $amount;
           }
           $totalamount = round($totalamount);
           $refundAmount = strval($totalamount);
           $description = 'Rerund order '.$order_id;

           $mac = hash_hmac('sha256', $this->appid . '|' . $z_trans_id . '|' . $refundAmount . '|' . $description . '|' . $timestamp, $this->key1);
           $params = array(
             'appid' => $this->appid,
             'mrefundid' => $mrefundid,
             'zptransid' => $z_trans_id,
             'timestamp' => $timestamp,
             'amount' => $refundAmount,
             'description' => $description,
             'mac' => $mac,
           );

             try {
               $result = $this->zalopay_postForm($this->zalopayurl . 'partialrefund', $params);
               if ( is_wp_error( $result ) ) {
                 $logger = wc_get_logger();
                 $logger->debug($result, $context );
                 return false;
               }

               if ($result['returncode'] >= 1) {

                 $mac2 = hash_hmac('sha256', $this->appid . '|' . $mrefundid . '|' . $timestamp, $this->key1);
                 $params = array(
                   'appid' => $this->appid,
                   'mrefundid' => $mrefundid,
                   'timestamp' => $timestamp,
                   'mac' => $mac2,
                 );
                 $result2 = $this->zalopay_postForm($this->zalopayurl . 'getpartialrefundstatus', $params);
                 if ($result2['returncode'] == 1) {

                   $order->add_order_note(
                     sprintf( __( 'Success: Refunded %1$s - Refund ID: %2$s', 'tino' ), wc_price($amount), esc_html($result['refundid']) ),
                     false
                   );
                   return true;
                 }

                 if ($result2['returncode'] == 2) {
                   $order->add_order_note(
                     sprintf( __( 'Success: Transaction %1$s is being refunded - Refund ID: %2$s', 'tino' ), wc_price($amount), esc_html($result['refundid']) ),
                     false
                   );
                   return true;
                 }

                 return false;
                } else {
                  return $result['returnmessage'];
                }


             } catch (Exception $e) {
               return false;
             }
         }


    static function verifyCallback(Array $params)
    {
      $key2 = $params["key2"];
      $data = $params["data"];
      $requestMac = $params["mac"];

      $result = [];
      $mac = hash_hmac('sha256', $data, $key2);

      if ($mac != $requestMac) {
        $result['returncode'] = -1;
        $result['returnmessage'] = 'mac not equal';
      } else {
        $result['returncode'] = 1;
        $result['returnmessage'] = 'success';
      }

      return $result;
    }


    public static function log( $message, $level = 'info' ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = wc_get_logger();
            }
            self::$log->log( $level, $message, array( 'source' => 'zalopay' ) );
        }
    }

    public function getTimestamp() {
      return round(microtime(true) * 1000);
    }

    static function zalopay_postForm($url, $params) {
      $context = stream_context_create([
        "http" => [
          "header" => "Content-type: application/x-www-form-urlencoded\r\n",
          "method" => "POST",
          "content" => http_build_query($params)
        ]
      ]);

      // $args = array(
      //   'method'      => 'POST',
      //    'body'        => $params,
      //    'timeout'     => '10',
      //    'redirection' => '5',
      //    'httpversion' => '1.0',
      //    'blocking'    => true,
      //    'headers'     => array(
      //      'Content-Type' => 'Content-type: application/x-www-form-urlencoded\r\n',
      //      'Content-Length' => strlen(http_build_query($params))
      //    ),
      //    'cookies'     => array(),
      //  );
      // $response = wp_remote_post( $url, $args );
      // return wp_remote_retrieve_body( $response );

      return json_decode(file_get_contents($url, false, $context), true);
    }


    public function zalopay_getstatus($order_id){
      global $woocommerce;
      $order = new WC_Order( $order_id );
      $app_trans_id = date('ymd').$order_id;

      $mac = hash_hmac('sha256', $this->appid . '|' . $app_trans_id . '|' . $this->key1, $this->key1);
      $params = array(
        'appid' => $this->appid,
        'apptransid' => $app_trans_id,
        'mac' => $mac,
      );
      $zalopaystatus = $this->zalopay_postForm($this->zalopayurl . 'getstatusbyapptransid', $params);
      return $zalopaystatus;
    }


}
