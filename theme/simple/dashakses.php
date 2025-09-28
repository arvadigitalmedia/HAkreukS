<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
# Cek apakah sudah beli atau belum
if (isset($datamember['mem_id'])) {
	if (isset($slug[3]) && !empty($slug[3])) {
		$produk = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".cek($slug[3])."'");
		if ($produk['pro_harga'] == 0 || db_var("SELECT `order_status` FROM `sa_order` WHERE `order_idproduk`=".$produk['page_id']." AND `order_idmember`=".$datamember['mem_id']) == 1) {
			if (substr($produk['pro_file'], 0,4) == 'http') {
				header("Location:".$produk['pro_file']);
			} else {
				$token = generateDownloadToken($produk['pro_file']);
				#echo $_SERVER['REMOTE_ADDR'];
				header("Location:".$weburl.'download.php?f='.$produk['pro_file'].'&id='.$token);
				echo $weburl.'download.php?f='.$produk['pro_file'].'&id='.$token;
			}
		} else {
			echo 'Anda belum order produk ini';
		}
	}
} else {
	echo 'Belum Login';
}