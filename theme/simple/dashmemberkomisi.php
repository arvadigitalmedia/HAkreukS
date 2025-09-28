<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Laporan Perolehan Komisi';
showheader($head);

if (isset($_GET['detil'])) {
	
	$exp = explode('-',$_GET['detil']);
	if (is_numeric($exp[0]) && is_numeric($exp[1])) {
		$tgl = $exp[0].'-'.$exp[1];
		$select = "SELECT * FROM `sa_laporan`
		LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idmember` 
		WHERE `lap_idsponsor`=".$iduser."
		AND `lap_code` =2
		AND MONTH(`lap_tanggal`) = ".$exp[1]." AND YEAR(`lap_tanggal`) = ".$exp[0]."
		ORDER BY `lap_tanggal`";
		$data = db_select($select);
		echo '
		<h4>Laporan '.date('F Y',strtotime($tgl.'-10 10:00:00')).'</h4>
		<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<thead class="table-secondary">
			<tr>
				<th>Tanggal</th>
				<th>Transaksi</th>
				<th>Member</th>
				<th class="text-end">Pemasukan</th>
				<th class="text-end">Pengeluaran</th>
			</tr>
			</thead>
			<tbody>';
		foreach ($data as $data) {
			echo '
			<tr>
				<td>'.date('d-m H:i', strtotime($data['lap_tanggal'])).'</td>
				<td>'.$data['lap_keterangan'].'</td>
				<td><a href="'.$weburl.'dashboard/kliendetil?id='.$data['mem_id'].'" target="_blank">'.$data['mem_nama'].'</a></td>
				<td class="text-end">'.number_format($data['lap_masuk']).'</td>
				<td class="text-end">'.number_format($data['lap_keluar']).'</td>
			</tr>';			
		}
		echo '
			</tbody>
		</table>
		</div>
		';
	}
	
} else {
	$data = db_select("
    SELECT SUM(`lap_masuk`) - SUM(`lap_keluar`) AS `komisi`,
           DATE_FORMAT(`lap_tanggal`, '%Y-%m') AS `bulan`
    FROM `sa_laporan` 
    WHERE `lap_idsponsor` = ".$iduser." AND `lap_code` = 2
    GROUP BY `bulan` ORDER BY `bulan` DESC
	");
	echo db_error(); // Untuk debugging

	if (count($data) > 0) {
		// Inisialisasi array untuk menyimpan bulan dan komisi
		$duit = [];
		$bulan_terdaftar = [];

		foreach ($data as $data_item) {
		    $duit[$data_item['bulan']]['komisi'] = $data_item['komisi'];
		    $bulan_terdaftar[] = $data_item['bulan'];
		}

		// Menentukan bulan paling awal dan bulan paling akhir
		$bulan_terawal = min($bulan_terdaftar);
		$bulan_terakhir = max($bulan_terdaftar);

		// Convert bulan awal dan akhir ke format DateTime
		$start = new DateTime($bulan_terawal . '-01');
		$end = new DateTime($bulan_terakhir . '-01');

		// Buat array untuk menyimpan semua bulan dari terbaru ke terlama
		$bulan_list = [];

		// Loop dari bulan paling akhir ke bulan paling awal
		while ($end >= $start) {
		    $bulan_list[] = $end->format('Y-m'); // Tambahkan bulan ke array
		    $end->modify('-1 month'); // Mundur satu bulan
		}
	}
	
	echo '
	<div class="table-responsive">
	<table class="table table-hover table-bordered" style="max-width: 500px">
	    <thead class="table-secondary">
	        <tr>
	            <th>Bulan</th>
	            <th class="text-end">Komisi</th>
	        </tr>
	    </thead>
	    <tbody>';

	// Loop untuk menampilkan tabel dengan bulan dan komisi
	if (isset($duit) && is_array($duit)) {
	    foreach ($bulan_list as $bulan) {
	        $komisi = isset($duit[$bulan]['komisi']) ? $duit[$bulan]['komisi'] : 0; // Default 0 jika tidak ada data
	        echo  '
	        <tr>
	            <td><a href="laporankomisi?detil='.$bulan.'">'.date('F Y', strtotime($bulan)).'</a></td>                    
	            <td class="text-end">'.number_format($komisi).'</td>
	        </tr>';
	    }
	}

	echo '
	    </tbody>
	</table>
	</div>';


}
showfooter();
