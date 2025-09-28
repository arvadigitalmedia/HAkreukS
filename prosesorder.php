<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($idinvoice) && is_numeric($idinvoice)) :
$proses = db_row("SELECT * FROM `sa_order`
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`= `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_status` = 0 AND `sa_order`.`order_id`=".$idinvoice);
if (isset($proses['order_id'])) {
	# Update data order
	db_query("UPDATE `sa_order` SET `order_status`=1,`order_idstaff`=".$staff.",`order_tglbayar`='".date('Y-m-d H:i:s')."' WHERE `order_id`=".$proses['order_id']);
	
	# Update Status Member jadi Premium
	if ($proses['mem_status'] < 2 && $proses['pro_harga'] > 0) {
		db_query("UPDATE `sa_member` SET `mem_status`=2,`mem_tglupgrade`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$proses['mem_id']);
	}

	$keterangan = 'Penjualan '.$proses['page_judul'];
	$ins = "(".$proses['order_id'].",".$proses['order_idmember'].",".$proses['order_idsponsor'].",'".date('Y-m-d H:i:s')."',".$proses['order_hargaunik'].",0,1,'".$keterangan."',0,'SA'),";
	# Dapatkan data upline
	if (!empty($proses['sp_network'])) {
		$network = str_replace('][', ',', $proses['sp_network']);
		$network = substr($network, 1,-1);
		if (!empty($network)) {
			$upline = db_select("SELECT * FROM `sa_member` WHERE `mem_id` IN (".$network.") ORDER BY FIELD(`mem_id`,".$network.")");		

			if (count($upline) > 0) {
				$komisi = unserialize($proses['pro_komisi']);
				$lvl = 1;
				
				foreach ($upline as $upline) {
					$kredit = 0;
					if ($upline['mem_status'] >= 2) {
						$kredit = $komisi['premium'][$lvl] ??=0;
					} else {
						$kredit = $komisi['free'][$lvl] ??=0;
					}

					if ($kredit > 0) {
						$keterangan = 'Komisi Penjualan '.$proses['page_judul'];
						$ins .= "(".$proses['order_id'].",".$proses['order_idmember'].",".$upline['mem_id'].",'".date('Y-m-d H:i:s')."',".$kredit.",0,2,'".$keterangan."',".$lvl.",'SA'),";
					}
					$lvl++;
				}			
			}
		}
	}
	
	$cek = db_query("INSERT INTO `sa_laporan` (`lap_idorder`,`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_level`,`lap_app`) 
		VALUES ".substr($ins,0,-1));
	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		# Kirim Notif yuk
		$datalain = array(
			'idorder' => $proses['order_id'],
			'hrgunik' => number_format($proses['order_hargaunik']),
			'hrgproduk' => number_format($proses['order_harga']),
			'namaproduk' => $proses['page_judul'],
			'urlproduk' => $proses['page_url']
		);
		sa_notif('prosesorder',$proses['order_idmember'],$datalain);

		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Terima kasih. Order '.$proses['order_id'].' telah diproses 🙏
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} else {
	echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
	  <strong>Error!</strong> Order tidak ditemukan. Mungkin sudah dihapus atau sudah diproses sebelumnya.
	  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>';
}

endif;