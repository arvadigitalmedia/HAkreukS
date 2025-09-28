<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Laporan Keuangan';
showheader($head);

$bulanlist = db_select("SELECT DATE_FORMAT( `lap_tanggal`,  '%Y-%m' ) AS `bulan` FROM  `sa_laporan` 		
		GROUP BY `bulan` ORDER BY `bulan` DESC");

$namabulan = array(
	'01' => 'Januari',
	'02' => 'Februari',
	'03' => 'Maret',
	'04' => 'April',
	'05' => 'Mei',
	'06' => 'Juni',
	'07' => 'Juli',
	'08' => 'Agustus',
	'09' => 'September',
	'10' => 'Oktober',
	'11' => 'Nopember',
	'12' => 'Desember'	
);
$tahun = date('Y');
$bulan = date('m');

if (isset($_GET['detil'])) {
	$exp = explode('-',$_GET['detil']);
	if (is_numeric($exp[0]) && is_numeric($exp[1])) {
		$tahun = $exp[0];
		$bulan = $exp[1];
	}
}

$data = db_select("SELECT * FROM `sa_laporan` 
	LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `lap_idmember`
	WHERE MONTH(`lap_tanggal`) = ".$bulan." AND YEAR(`lap_tanggal`) = ".$tahun."
	AND `lap_code`=1
	ORDER BY `lap_tanggal`");
echo '
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col">
	    	<div class="input-group">				  
				  <select class="form-select" name="detil">';
				  foreach ($bulanlist as $list) {
				  	$ex = explode('-', $list['bulan']);
				  	echo '<option value="'.$list['bulan'].'"';
				  	if ($list['bulan'] == $tahun.'-'.$bulan) { echo ' selected'; }
				  	echo '>'.$namabulan[$ex[1]].' '.$ex[0].'</option>';
				  }
echo '
				  </select>
				  <input type="submit" value=" Pilih Bulan " class="btn btn-secondary">
				  
				</div>	      
	    </div>
	  </div>
	</div>
</div>
</form>
<h4>Laporan '.$namabulan[$bulan].' '.$tahun.'</h4>
<div class="table-responsive">
<table class="table table-hover table-bordered">
<thead class="table-secondary">
	<tr>
		<th>Tanggal</th>
		<th>Keterangan</th>		
		<th class="text-end">Pemasukan</th>
		<th class="text-end">Pengeluaran</th>
		<th class="text-end">Saldo</th>
	</tr>
</thead>
<tbody>';
if (count($data) > 0) {
	$saldo = 0;
	foreach ($data as $data) {
		$saldo = $saldo + ($data['lap_masuk']-$data['lap_keluar']);
		echo '
	<tr>
		<td>'.$data['lap_tanggal'].'</td>
		<td>'.$data['lap_keterangan'].' '.$data['mem_nama'].'</td>		
		<td class="text-end">'.number_format($data['lap_masuk']).'</td>
		<td class="text-end">'.number_format($data['lap_keluar']).'</td>
		<td class="text-end">'.number_format($saldo).'</td>
	</tr>
		';
	}
}
echo '
</tbody>
</table>
</div>
';

showfooter();
