<?php
if (isset($_GET['pay']) && !empty($_GET['pay'])) {
  if ($_GET['pay'] == 'manual') {
    if (isset($settings['carapembayaran'])) {
      $manual = str_replace('[hargaunik]', number_format($order['order_hargaunik']), $settings['carapembayaran']);
      $manual = str_replace('[harga]', number_format($order['order_harga']), $manual);
      $manual = str_replace('[namaproduk]', $order['page_judul'], $manual);
      db_query("UPDATE `sa_order` SET `order_trx`='manual' WHERE `order_id`=".$order['order_id']);
      $sukses = 1;
    }
  } else {
    $apiKey       = $settings['tripay_api'];
    $privateKey   = $settings['tripay_private'];
    $merchantCode = $settings['tripay_merchant'];
    $merchantRef  = str_pad($order['order_id'],4,0,STR_PAD_LEFT);
    $amount       = $order['order_harga'];

    # Instruksi Pembayaran
    $data = [
        'method'         => $_GET['pay'],
        'merchant_ref'   => $merchantRef,
        'amount'         => $amount,
        'customer_name'  => $order['mem_nama'],
        'customer_email' => $order['mem_email'],
        'customer_phone' => $order['mem_whatsapp'],
        'order_items'    => [
            [
                'sku'         => 'PRO-'.$order['page_id'],
                'name'        => $order['page_judul'],
                'price'       => $order['order_harga'],
                'quantity'    => 1,
                'product_url' => $weburl.'produk/'.$order['page_url']
            ]
        ],
        'return_url'   => $weburl.'thanks',
        'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
        'signature'    => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey)
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT  => true,
        CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/transaction/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
        CURLOPT_FAILONERROR    => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);
    $hasil = empty($error) ? $response : $error;
    $arrhasil = json_decode($hasil,TRUE);


    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
      # Simpan ke database
      db_query("UPDATE `sa_order` SET `order_trx`='".$arrhasil['data']['reference']."' WHERE `order_id`=".$order['order_id']);
      $sukses = 1;
    }
  }

  if (isset($sukses)) {
    echo '
      <script type="text/javascript">
      <!--
      window.location = "'.$weburl.'invoice/'.$order['order_id'].'"
      //-->
      </script>';
  }
} else {
  echo '
  <h3 class="text-center">Pilih Metode Pembayaran</h3>
  <div class="list-group">';
  if (isset($settings['carapembayaran'])) { echo '
    <a href="?pay=manual" class="list-group-item list-group-item-action">
    <img src="'.$weburl.'img/bank-transfer.jpg" alt="" style="width:100px; float:left; margin-right:10px"/>
    <strong>Transfer Manual</strong>
    </a>'; 
  }
  if (isset($settings['tripay_merchant']) && !empty($settings['tripay_merchant'])) {
    if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
      $urlapi = 'api-sandbox';
    } else {
      $urlapi = 'api'; 
    }

    # Daftar Metode Pembayaran
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_FRESH_CONNECT  => true,
      CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/merchant/payment-channel',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => false,
      CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$settings['tripay_api']],
      CURLOPT_FAILONERROR    => false,
      CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    $hasil = empty($error) ? $response : $error;
    $arrhasil = json_decode($hasil,TRUE);
    
    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
      
      foreach ($arrhasil['data'] as $payment) {
        $fee = $payment['fee_customer']['flat'] + (($payment['fee_customer']['percent']/100) * $order['order_harga']);
        echo '
        <a href="?pay='.$payment['code'].'" class="list-group-item list-group-item-action">
        <img src="'.$payment['icon_url'].'" alt="" style="width:100px; float:left; margin-right:10px"/>
        <strong>'.$payment['name'].'</strong><br/><small>Fee admin: Rp. '.number_format($fee).'</small>
        </a>';
      }            
    } else {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> '.$arrhasil['message'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
    }
  }
  echo '</div>';
}
?>