<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); } 
if (isset($slug[2]) && is_numeric($slug[2])) :

  $order = db_row("SELECT * FROM `sa_order` 
      LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
      LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
      WHERE `sa_order`.`order_id`=".$slug[2]);
  if (isset($order['order_id'])) :
    if (isset($_GET['act']) && $_GET['act'] == 'ubahpembayaran') {
      if (isset($datamember['mem_id']) && $datamember['mem_id'] == $order['order_idmember']) {
        db_query("UPDATE `sa_order` SET `order_trx` ='' WHERE `order_id`=".$order['order_id']." AND `order_idmember`=".$datamember['mem_id']);
        echo db_error();
        $order['order_trx'] = '';
      } else {
        echo 'Data member tidak ada';
      }
    }
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?=$weburl;?>img/<?=$favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Invoice <?=$order['page_judul'];?></title>

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
    <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
</head>
<body>
	<div class="container p-md-3 mt-3 mb-3">
    <div class="card">
      <div class="card-body">
    		<div class="row mt-3">
          <div class="col-md-6 p-md-5">
            <h1>Invoice #<?=str_pad($order['order_id'],4,0,STR_PAD_LEFT);?></h1> 
            <?php if ($order['order_status'] == 1) { echo '<div style="font-size:24px;font-weight:700" class="text-success">LUNAS</div>';} ?>
          </div>
          <div class="col-md-6 p-md-5 text-end">
            <strong>Ditagihkan kepada:</strong><br>
            <?php 
            if ($iduser = is_login()) {
              if ($iduser == $order['order_idmember']) {
                echo '                
                '.$order['mem_nama'].'<br/>
                WA: <a href="https://wa.me/'.$order['mem_whatsapp'].'">'.$order['mem_whatsapp'].'</a>';
              } else {
                echo sensor($order['mem_nama']);
              }
            } else {
              echo sensor($order['mem_nama']);
            }
            ?>
          </div>
        </div>
        <div class="row mt-5">
          <div class="col-md-5 p-md-5 mb-3">
          <?php
          if ($order['order_status'] == 0) {
            if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
              $urlapi = 'api-sandbox';
            } else {
              $urlapi = 'api'; 
            }

            if (empty($order['order_trx'])) {
              include('payment.php');
            } elseif ($order['order_trx'] == 'manual') {
              if (isset($settings['carapembayaran']) && !empty($settings['carapembayaran'])) { 
                $manual = str_replace('[hargaunik]', number_format($order['order_hargaunik']), $settings['carapembayaran']);
                $manual = str_replace('[harga]', number_format($order['order_harga']), $manual);
                $manual = str_replace('[namaproduk]', $order['page_judul'], $manual);
                $manual = str_replace('[hargacopy]', $order['order_hargaunik'], $manual); 
                $manual = copycode($manual);  
                echo '<h3>Cara Pembayaran</h3>'.
                $manual;
              }            
            } else {
              $apiKey = $settings['tripay_api'];
              $payload = ['reference' => $order['order_trx']];

              $curl = curl_init();

              curl_setopt_array($curl, [
                  CURLOPT_FRESH_CONNECT  => true,
                  CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/transaction/detail?'.http_build_query($payload),
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_HEADER         => false,
                  CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
                  CURLOPT_FAILONERROR    => false,
                  CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
              ]);

              $response = curl_exec($curl);
              $error = curl_error($curl);

              curl_close($curl);

              $hasil = empty($error) ? $response : $error;
              $arrhasil = json_decode($hasil,TRUE);

              if (isset($arrhasil['data'])) {
                $datatri = $arrhasil['data'];                
                $carabayar = '
              <h3>Cara Pembayaran</h3>
              <div class="accordion" id="metodebayar">';
                if ($datatri['payment_method'] == 'SHOPEEPAY' || $datatri['payment_method'] == 'OVO' || $datatri['payment_method'] == 'DANA') {
                  
                  $detil = '<a href="'.$datatri['checkout_url'].'" target="_blank" class="btn btn-success">Lanjutkan Pembayaran '.$datatri['payment_name'].'</a>';

                } else {
                  if (isset($datatri['qr_url'])) {
                    $detil = '<img src="'.$datatri['qr_url'].'" alt="QR Code" class="img-fluid"/>';
                  } else {
                    $detil = '
                    <div class="table-responsive">
                      <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                          <th>'.$datatri['payment_name'].'</th>
                        </thead>
                        <tbody>
                          <tr><td>No. Reference</td></tr>
                          <tr><td>'.$datatri['reference'].'</td></tr>
                          <tr><td>No. Virtual Account</td></tr>
                          <tr><td><a onclick="copyToClipboard(\''.$datatri['pay_code'].'\')" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard">'.$datatri['pay_code'].' &nbsp; <i class="fa-regular fa-copy"></i></a></td></tr>
                          <tr><td>Jumlah Transfer</td></tr>
                          <tr><td><a onclick="copyToClipboard(\''.$datatri['amount'].'\')" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard">'.number_format($datatri['amount']).' &nbsp; <i class="fa-regular fa-copy"></i></a></td></tr>                      
                        </tbody>
                      </table>
                    </div>
                    ';
                  }
                }

                $acc = 1;
                foreach ($datatri['instructions'] as $instruksi) {
                  if ($acc == 1) { $s = ' show'; } else { $s = ''; }
                  $carabayar .= '
                  <div class="accordion-item">
                    <h2 class="accordion-header">
                      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$acc.'" aria-expanded="true" aria-controls="collapse'.$acc.'">
                        '.$instruksi['title'].'
                      </button>
                    </h2>
                    <div id="collapse'.$acc.'" class="accordion-collapse collapse'.$s.'" data-bs-parent="#metodebayar">
                      <div class="accordion-body">
                        <ol>';
                        foreach ($instruksi['steps'] as $step) {
                          $carabayar .= '<li>'.$step.'</li>';
                        }
                        $carabayar .= '
                        </ol>
                      </div>
                    </div>
                  </div>
                  ';
                  $acc++;
                }
                
                $carabayar .= '</div>';
              } else {
                include('payment.php');
              }              
            }
          }

          echo $detil??=''; 
          if (isset($datamember['mem_id']) && $datamember['mem_id'] == $order['order_idmember']) {
            echo '<a href="'.$weburl.'invoice/'.$order['order_id'].'?act=ubahpembayaran" class="btn btn-success">Ubah Pembayaran</a>';
          }
          ?>
          </div>
          <div class="col-md-7 p-md-5">
            <div class="table-responsive">
              <table class="table table-hover table-bordered">
                <thead class="table-secondary">
                  <tr>                
                    <th>Nama Produk</th>
                    <th class="text-end">Harga</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <strong><?= $order['page_judul'];?></strong><br/>
                      <?= $order['page_diskripsi']??='';?>
                    </td>
                    <td class="text-end"><?= number_format($order['order_harga']);?></td>
                  </tr>
                  <?php if (isset($datatri['fee_customer'])) :  ?>
                  <tr>
                    <td>Biaya Admin</td>
                    <td class="text-end"><?= number_format($datatri['fee_customer']);?></td>
                  </tr> 
                  <tr>
                    <td>Total</td>
                    <td class="text-end"><?= number_format($datatri['amount']);?></td>
                  </tr>
                  <?php else : ?>
                  <tr>
                    <td>Angka Unik</td>
                    <td class="text-end"><?= number_format($order['order_hargaunik'] - $order['order_harga']);?></td>
                  </tr> 
                  <tr>
                    <td>Total</td>
                    <td class="text-end"><?= number_format($order['order_hargaunik']);?></td>
                  </tr>  
                  <?php endif; ?>                                          
                </tbody>
              </table>  
                         
            </div>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col p-md-3">
            <?php
            echo $carabayar??='';            
            ?>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col text-center">
            <a href="<?=$weburl;?>dashboard">Login ke dashboard</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<script>
  function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    showCopySuccessModal();
}

function showCopySuccessModal() {
    // Buat modal jika belum ada
    if (!document.getElementById('copySuccessModal')) {
        var modalHTML = `
            <div id="copySuccessModal" class="copy-modal-overlay" style="display: none;">
                <div class="copy-modal-content">
                    <div class="copy-modal-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="copy-modal-text">Data berhasil disalin</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Tambahkan CSS untuk modal
        var style = document.createElement('style');
        style.textContent = `
            .copy-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                backdrop-filter: blur(3px);
            }
            
            .copy-modal-content {
                background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF8C00 100%);
                border: 2px solid #B8860B;
                border-radius: 15px;
                padding: 30px 40px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 
                            0 0 0 1px rgba(255, 255, 255, 0.2),
                            inset 0 1px 0 rgba(255, 255, 255, 0.4);
                transform: scale(0.8);
                animation: modalAppear 0.3s ease-out forwards;
                max-width: 350px;
                min-width: 280px;
            }
            
            .copy-modal-icon {
                font-size: 3rem;
                color: #2d5016;
                margin-bottom: 15px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }
            
            .copy-modal-text {
                font-size: 1.2rem;
                font-weight: 600;
                color: #1a1a1a;
                text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);
                letter-spacing: 0.5px;
            }
            
            @keyframes modalAppear {
                0% {
                    transform: scale(0.8);
                    opacity: 0;
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            @keyframes modalDisappear {
                0% {
                    transform: scale(1);
                    opacity: 1;
                }
                100% {
                    transform: scale(0.8);
                    opacity: 0;
                }
            }
            
            .copy-modal-content.disappearing {
                animation: modalDisappear 0.3s ease-in forwards;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Tampilkan modal
    var modal = document.getElementById('copySuccessModal');
    var content = modal.querySelector('.copy-modal-content');
    
    modal.style.display = 'flex';
    content.classList.remove('disappearing');
    
    // Sembunyikan modal setelah 2 detik
    setTimeout(function() {
        content.classList.add('disappearing');
        setTimeout(function() {
            modal.style.display = 'none';
        }, 300);
    }, 2000);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" 
integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>
<?php
  else:
    echo 'Invoice tidak ditemukan';
  endif;
endif;
?>