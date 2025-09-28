<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($slug[2]) && !empty($slug[2])) :
	$order = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".txtonly($slug[2])."'");
	if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
    $urlapi = 'api-sandbox';
  } else {
    $urlapi = 'api'; 
  }

	if (isset($order['page_judul'])) :
		# Kalau member sudah login, cek apakah sudah order atau belum
		if ($iduser = is_login()) {
			$cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idmember`=".$iduser." AND `order_idproduk`=".$order['page_id']);
			if (isset($cekorder['order_status'])) {
      	if ($cekorder['order_status'] == 1) {
	      	# Order sudah lunas, arahkan ke halaman download
	      	header("Location:".$weburl."dashboard/akses/".$order['page_url']);
      	} else {
      		# Sudah order tapi belum lunas, arahkan ke halaman invoice
      		header("Location:".$weburl."invoice/".$cekorder['order_id']);
      	}
      } else {
      	$idmember = $iduser;
      	$idsponsor = db_var("SELECT `sp_sponsor_id` FROM `sa_sponsor` WHERE `sp_mem_id`=".$iduser);
      }
		} elseif (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
		  if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($_POST['email'])."'")) {
				$error = 'Email sudah ada yang menggunakan';
			}

			# Cek form yg required

			$req = db_select("SELECT * FROM `sa_form` WHERE `ff_registrasi`=1 AND `ff_required`=1");
			if (count($req) > 0) {
				foreach ($req as $req) {
					if (!isset($_POST[$req['ff_field']]) || empty($_POST[$req['ff_field']])) {
						$error = $req['ff_label'].' wajib diisi';
					} else {
						if ($req['ff_field'] == 'whatsapp') {
							if (empty(formatwa($_POST['whatsapp']))) {
								$error = $req['ff_label'].' wajib diisi dg format 08123456789';
							}
						}
					}
				}
			}

			if (!isset($error)) {
				if (!isset($idsponsor)) {
					if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
						if (db_exist("SELECT `mem_id` FROM `sa_member` WHERE `mem_id`=".$_COOKIE['idsponsor'])) {
							$idsponsor = $_COOKIE['idsponsor'];
						} else {
							$idsponsor = 1;
						}
					} else {
						$idsponsor = 1;
					}
				}

				if (isset($_POST['sponsor']) && !empty($_POST['sponsor'])) {
					$sponsor = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($_POST['sponsor']))."'");
					
					if (is_numeric($sponsor)) {
						$idsponsor = $sponsor;
					} 
				}

				$defaultkey = array('nama','email','password','whatsapp','kodeaff');
				$datalain = '';
				

				foreach ($_POST as $key => $value) {
					if (in_array($key, $defaultkey)) {
						${$key} = cek($value);
					} else {
						$datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
					}
				}
				

				if (isset($_FILES) && count($_FILES) > 0) {
					$max_size = 1024000;
					$whitelist_ext = array('jpeg','jpg','png','gif');
					$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
					$pic_dir = str_replace('saregister.php','upload',__FILE__);
					$memberid = 'XXX'.rand(1000,9999).'XXX';
					
					if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

					foreach($_FILES as $field => $files) {
						$filename = $memberid.'_'.$field;
						$target_file = $pic_dir.'/'.$filename;
				    $uploadOk = 1;
				    $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
				    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
				      $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
				      $uploadOk = 0;
				    }
				    //Check that the file is of the right type
						if (!in_array($files["type"], $whitelist_type)) {
						  $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
						  $uploadOk = 0;
						}
						// Check file size
				    if ($files["size"] > $max_size) {
				      $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
				      $uploadOk = 0;
				    }
				    if ($uploadOk == 1) {
			        $file = $files["tmp_name"];
			        $target_file = $target_file.'.'.$imageFileType;
			        $img = new Imagick();
			        $img->readImage($file);
			        $width = $img->getImageWidth();
			        if ($width > 800) {
			            $width = 800;
			        }
			        $img->setimagebackgroundcolor('white');
			        //$img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
			        $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
			        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
			        $img->setImageCompressionQuality(80);
			        $img->resizeImage($width,800,Imagick::FILTER_CATROM,1,TRUE);
			        $img->stripImage();
			        $img->writeImage($target_file);
			        #$gambar = $target_file.'.'.$imageFileType;
			        $datalain .= txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType."\n";	        
			    	}
					}
				}
				
				if (!isset($password) || empty($password)) { $password = randomword(); } else { $password = $_POST['password']; }

				if (!isset($kodeaff)) { $kodeaff = $nama; }
				$kodeaff = cekkodeaff(txtonly(strtolower($kodeaff)));
				if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }
										
				$newuserid = db_insert("INSERT INTO `sa_member` (
					`mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
					`mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`) 
				VALUES ('".$nama."','".$email."','".create_hash($password)."',
					'".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
					1,1)");

				
				if (is_numeric($newuserid)) {
					$spnetwork = db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".$idsponsor);
					$newspnetwork = '['.$idsponsor.']'.$spnetwork;

					$cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES 
						(".$newuserid.",".$idsponsor.",'".$newspnetwork."')");
					if (isset($memberid)) {
						$datalain = str_replace($memberid,$newuserid,$datalain);
						db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
						$files = glob($pic_dir . '/'.$memberid.'*');					
						// Loop semua file yang ditemukan dan ganti nama file
						foreach ($files as $file) {
						    // Buat nama file baru dengan mengganti teks XXX123XXX dengan ID member baru
						    $newName = str_replace($memberid, $newuserid, $file);
						    // Ganti nama file
						    rename($file, $newName);
						}
					}
					

				} else {
					$error = db_error();
				}

				if (isset($cek)) {
					if ($cek === false) {
						$error = db_error();
					} else {
						$idmember = $newuserid;
					}
				}				
			}
		} elseif (isset($_POST['username']) && filter_var($_POST['username'],FILTER_VALIDATE_EMAIL) 
			&& isset($_POST['password']) && !empty($_POST['password'])) {

			$datamember = db_row("SELECT * FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`=`sa_member`.`mem_id`
				WHERE `mem_email`='".cek($_POST['username'])."'");

			if (isset($datamember['mem_id'])) {
				if (validate_password($_POST['password'],$datamember['mem_password'])) {
		      $idmember = $id = $datamember['mem_id'];
		      $idsponsor = $datamember['sp_sponsor_id'];
		      $hash = sha1(rand(0,500).microtime().SECRET);
		      $signature = sha1(SECRET . $hash . $id);
		      $cookie = base64_encode($signature . "-" . $hash . "-" . $id);
		      setcookie('authentication', $cookie,time()+36000,'/');
		      db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);

		      # Cek apakah sudah order sebelumnya
		      $cekorder = db_row("SELECT * FROM `sa_order` WHERE `order_idmember`=".$idmember." AND `order_idproduk`=".$order['page_id']);
		      if (isset($cekorder['order_status'])) {
		      	if ($cekorder['order_status'] == 1) {
			      	# Order sudah lunas, arahkan ke halaman download
			      	header("Location:".$weburl."dashboard/akses/".$order['page_url']);
			      	exit();
		      	} else {
		      		# Sudah order tapi belum lunas, arahkan ke halaman invoice
		      		header("Location:".$weburl."invoice/".$cekorder['order_id']);
		      		exit();
		      	}
		      }

		    } else {
		        $error = 'Maaf, sepertinya Password anda kurang tepat, silahkan cek kembali dan pastikan tombol capslock tidak tertekan.';
		    }
			} else {
				$error = 'Maaf, kami tidak menemukan akun dengan email tersebut.';
			}
		}

		# Bikin Order
		if (isset($idmember) && is_numeric($idmember) && !isset($error)) {
			$lastidorder = db_var("SELECT AUTO_INCREMENT
          FROM information_schema.TABLES
          WHERE TABLE_NAME = 'sa_order'");
      if (!is_numeric($lastidorder)) {
        $lastidorder = 0;
      }

			$idunik = $lastidorder;
      if (strlen($lastidorder) > 3) {
        $idunik = substr($lastidorder, -3);
      }

			if (isset($settings['kodeunik']) && is_numeric($settings['kodeunik'])) {
				switch ($settings['kodeunik']) {
					case 0:
						$hrgunik = $order['pro_harga'];
						break;
					case 1:						
						$hrgunik = ($order['pro_harga']-1000)+$idunik;
						break;
					case 2:
						$hrgunik = $order['pro_harga']+$idunik;
						break;					
					default:
						$hrgunik = $order['pro_harga'];
						break;
				}
			} else {
				$hrgunik = $order['pro_harga']+$idunik;
			}

			# Bikin Transaksi
			if (isset($_POST['payment']) && !empty($_POST['payment'])) {
				if ($_POST['payment'] == 'manual') {
					$order_trx = 'manual';
				} else {
					# Tripay Create Start
					$apiKey       = $settings['tripay_api'];
			    $privateKey   = $settings['tripay_private'];
			    $merchantCode = $settings['tripay_merchant'];
			    $merchantRef  = str_pad($lastidorder,4,0,STR_PAD_LEFT);
			    $amount       = $order['pro_harga'];

			    # Instruksi Pembayaran
			    $data = [
			        'method'         => $_POST['payment'],
			        'merchant_ref'   => $merchantRef,
			        'amount'         => $amount,
			        'customer_name'  => $nama,
			        'customer_email' => $email,
			        'customer_phone' => $whatsapp,
			        'order_items'    => [
			            [
			                'sku'         => 'PRO-'.$order['page_id'],
			                'name'        => $order['page_judul'],
			                'price'       => $order['pro_harga'],
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
			    	$order_trx = $arrhasil['data']['reference'];
			    } else {
			    	$order_trx = '';
			    }
					# Tripay Create End
				}
			}
			
			$idorder = db_insert("INSERT INTO `sa_order` (`order_idmember`,`order_idsponsor`,`order_idproduk`,`order_tglorder`,`order_harga`,`order_hargaunik`,`order_trx`) 
				VALUES (".$idmember.",".$idsponsor.",".$order['page_id'].",'".date('Y-m-d H:i:s')."',".$order['pro_harga'].",".$hrgunik.",'".$order_trx."')");
			if (is_numeric($idorder)) {
				# Kirim Notif yuk
				$datalain = array(
					'newpass' => $password,
					'idorder' => $idorder,
					'hrgunik' => $hrgunik,
					'hrgproduk' => $order['pro_harga'],
					'namaproduk' => $order['page_judul'],
					'urlproduk' => $order['page_url']
				);
				sa_notif('order',$idmember,$datalain);
				# Redirect ke invoice
				header("Location:".$weburl."invoice/".$idorder);
			} else {
				$error = db_error();
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

    <title>Order <?=$order['page_judul'];?></title>

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/052e965aa8.js" crossorigin="anonymous"></script> 
    <style type="text/css">
        .password-wrapper {
          position: relative;
        }
        
        .password-wrapper input[type="password"] {
          padding-right: 30px; /* Ruang untuk ikon */
        }
        
        .password-wrapper .toggle-password {
          position: absolute;
          top: 50%;
          right: 5px;
          transform: translateY(-50%);
          cursor: pointer;
        }
    </style>
    <script>
      function togglePassword() {
	      var passwordInput = document.getElementById("password");
	      var toggleBtn = document.getElementById("togglePassword");

	      if (passwordInput.type === "password") {
	        passwordInput.type = "text";
	        toggleBtn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>';
	      } else {
	        passwordInput.type = "password";
	        toggleBtn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
	      }
	    }
    </script>
</head>
<body>
	<div class="container-fluid p-3">
		
		<form action="" method="post" onsubmit="document.getElementById('formsubmit').disabled=true;
          document.getElementById('formsubmit').value='Tunggu sebentar...';" enctype="multipart/form-data">
		<div class="row m-md-5">
			<div class="col-md-8 order-1">
				<?php
				if (isset($error)) {
					echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.$error.'
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
				}
				?>
				<div class="card mb-3">					
				<?php 				
				if (isset($_GET['act']) == 'login') :?>
					<div class="card-header">
				    Login
				  </div>
					<div class="card-body">
				      <div class="mb-3 row">
				      	<h2>Login</h2>
				      </div>
				      <div class="mb-3 row">
						    <label for="staticEmail" class="col-sm-3 col-form-label text-start">Email</label>
						    <div class="col-sm-9">
						      <input type="email" class="form-control" name="username" placeholder="email@example.com">
						    </div>
						  </div>
						  <div class="mb-3 row">
						    <label for="inputPassword" class="col-sm-3 col-form-label text-start">Password</label>
						    <div class="col-sm-9">
						      <div class="password-wrapper">
							      <input type="password" id="password" class="form-control" name="password">
							      <span class="toggle-password" id="togglePassword" onclick="togglePassword()"><i class="fas fa-eye text-secondary"></i></span>
			            </div>
						    </div>
						  </div>
						  <input type="submit" class="btn btn-success" value=" ORDER SEKARANG ">
						<div class="mt-3 pt-3 border-top row">					
							<div class="col text-center">
								Belum punya akun? Silahkan <a href="<?=$visiturl;?>">Register</a>
							</div>
						</div>
					</div>
				<?php else :?>
					<div class="card-header">
				    Register
				  </div>
					<div class="card-body">
							<?php 
						  if (!isset($idsponsor)) {
						  	if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
						  		$idsponsor = $_COOKIE['idsponsor'];
						  	} else {
						  		$idsponsor = 1;
						  	}
						  }

				  		$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);

				      echo form_builder('register');
				      ?>
				      <div class="text-end">
			      		<input type="submit" class="btn btn-success" id="formsubmit" value=" ORDER SEKARANG ">
			      	</div>
							<div class="mt-3 pt-3 border-top text-center">
								<?php 
								if (isset($datasponsor['mem_nama'])) {
					  			echo 'Rekomendasi: '.$datasponsor['mem_nama'];
					  		}
					  		?>  		
		  				</div>
						<div class="mt-3 pt-3 border-top row">					
							<div class="col text-center">
								Sudah punya akun? Silahkan <a href="<?=$visiturl;?>?act=login">Login</a>
							</div>
						</div>
					</div>
				<?php endif;?>					
				</div>
			</div>
			<div class="col-md-4">
				<div class="card mb-3">
					<div class="card-header">
				    Order Anda
				  </div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-hover table-bordered">
								<thead class="table-secondary">
									<tr>
										<th>Produk</th>
										<th class="text-end">Harga</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?=$order['page_judul'];?></td>
										<td class="text-end"><?=number_format($order['pro_harga']);?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php
				  echo '				  
				  <div class="card mb-3">
					<div class="card-header">
				    Metode Pembayaran
				  </div>
				  <ul class="list-group list-group-flush">';
				  if (isset($settings['carapembayaran']) && !empty($settings['carapembayaran'])) { 
				  	echo '
				    <li class="list-group-item">
					    <div class="form-check">
					    	<input class="form-check-input" type="radio" name="payment" value="manual" required>
					    	<label class="form-check-label" for="flexCheckChecked">
						    <img src="'.$weburl.'img/bank-transfer.jpg" alt="" style="width:100px; float:left; margin-right:10px"/>
						    <strong>Transfer Manual</strong>
						    </label>
						  </div>
				    </li>'; 
				  }

				  if (isset($settings['tripay_merchant']) && !empty($settings['tripay_merchant'])) {
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
				        $fee = $payment['fee_customer']['flat'] + (($payment['fee_customer']['percent']/100) * $order['pro_harga']);
				        echo '
				        <li class="list-group-item">
				        	<div class="form-check">
									  <input class="form-check-input" type="radio" name="payment" value="'.$payment['code'].'" required>
									  <label class="form-check-label" for="flexCheckChecked">
									    <img src="'.$payment['icon_url'].'" alt="" style="width:100px; float:left; margin-right:10px"/>
				        			<strong>'.$payment['name'].'</strong><br/><small>Fee admin: Rp. '.number_format($fee).'</small>
									  </label>
									</div>
				        </li>';
				      }            
				    } else {
				      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				        <strong>Error!</strong> '.$arrhasil['message'].'
				        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				      </div>';
				    }
				  }
				  echo '</ul>
				  </div>';
				?>
			</div>
		</div>
		</form>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>
<?php
	else :
		header("HTTP/1.0 404 Not Found");
		echo '<h1>Not Found</h1>';
	endif;
else :
	header("HTTP/1.0 404 Not Found");
	echo '<h1>Not Found</h1>';
endif;
?>