<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Pembayaran Komisi';
showheader($head);
if (isset($_GET['detil']) && is_numeric($_GET['detil'])) {
	$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$_GET['detil']);
	echo '<h3>Data Komisi '.$datasponsor['mem_nama'].'</h3>';
	$data = db_select("SELECT * FROM `sa_laporan` 
		LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idmember`
		WHERE `lap_idsponsor`=".$_GET['detil']." AND `lap_code`=2");
	
	echo '
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
			$saldo = $saldo + $data['lap_masuk'] - $data['lap_keluar'];
			if ($data['lap_masuk'] > 0) {
				$keterangan = $data['lap_keterangan'].' '.$data['mem_nama'].' Level: '.$data['lap_level'];
			} else {
				$keterangan = $data['lap_keterangan'];
			}
			echo '
		<tr>
			<td>'.$data['lap_tanggal'].'</td>
			<td>'.$keterangan.'</td>			
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
} else {
	# Pencairan Komisi
	if (isset($_POST['cair']) && is_numeric($_POST['cair']) && isset($_POST['idsponsor']) && is_numeric($_POST['idsponsor'])) {
		$cekdata = db_row("SELECT SUM(`lap_masuk`)-SUM(`lap_keluar`) AS `komisi` FROM `sa_laporan` 
		WHERE `lap_code`=2 AND `lap_idsponsor`=".$_POST['idsponsor']." GROUP BY `lap_idsponsor`");
		
		if ($cekdata['komisi'] >= $_POST['cair']) {
			# Ok Cairkan
			$cek = db_insert("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`)
				VALUES 
				(0,".$_POST['idsponsor'].",'".date('Y-m-d H:i:s')."',0,".$_POST['cair'].",2,'Pencairan Komisi'),
				(".$_POST['idsponsor'].",0,'".date('Y-m-d H:i:s')."',0,".$_POST['cair'].",1,'Pencairan Komisi')
				");

			$datalain = array('komisi' => number_format($_POST['cair']));

			sa_notif('cair_komisi',$_POST['idsponsor'],$datalain);
			
			if ($cek === false) {
				echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.db_error().'
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
			} else {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
				  <strong>Ok!</strong> Pencairan Komisi berhasil.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
			}
		} else {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> Pencairan lebih banyak dari saldo. Saldo tersisa: '.number_format($cekdata['komisi']).'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	}

	$where = '';
	if (isset($settings['minkomisi']) && $settings['minkomisi'] > 0) {
		$minkomisi = $settings['minkomisi'];
		$infominkomisi = '<br/>Hanya memunculkan member yang mendapat komisi lebih dari '.number_format($minkomisi);
	} else {
		$minkomisi = 0;
		$infominkomisi = '';
	}
	if (isset($_GET['cari']) && !empty($_GET['cari'])) {
		$s = cek($_GET['cari']);
		$where = "AND (`mem_nama` LIKE '%".$s."%' 
							OR `mem_email` LIKE '%".$s."%' 
							OR `mem_whatsapp` LIKE '%".$s."%' 
							OR `mem_datalain` LIKE '%".$s."%' 
							OR `mem_kodeaff` LIKE '%".$s."%')";
		$having = '';
	} else {
		$having = " HAVING `komisi` > ".$minkomisi;
	}

	$data = db_select("SELECT `sa_member`.*,
    SUM(`lap_masuk`)-SUM(`lap_keluar`) AS `komisi` FROM `sa_laporan` 
		LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_laporan`.`lap_idsponsor` 
		WHERE `lap_code`=2 ".$where." GROUP BY `sa_member`.`mem_id`,`sa_member`.`mem_nama`".$having);
	
	echo '
	<form action="" method="get">
	<div class="card mb-3">
		<div class="card-body">
		  <div class="row">	    
		    <div class="col">
		    	<div class="input-group">
					  <input type="text" class="form-control" name="cari" value="'.($_GET['cari'] ??= '').'">
					  <input type="submit" value=" Cari " class="btn btn-secondary">
					</div>	      
		    </div>
		  </div>
		</div>
	</div>
	</form>
	<div class="table-responsive">
	<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama</th>
			<th class="d-none d-sm-table-cell">Rekening</th>
			<th class="d-none d-sm-table-cell">Komisi Keagenan</th>
			<th>Pencairan</th>
		</tr>
	</thead>
	<tbody>';
	if (count($data) > 0) {
		foreach ($data as $data) {
			$datalain = extractdata($data);
			echo '
		<tr>
			<td>
				<a href="?detil='.$data['mem_id'].'" target="_blank">'.$data['mem_nama'].'</a>
				<span class="d-sm-none">
					<br/>'.($datalain['rekening']??='').'
					<br/>Komisi: '.number_format($data['komisi']).'
				</span>
			</td>
			<td class="d-none d-sm-table-cell">'.($datalain['rekening']??='').'</td>
			<td class="d-none d-sm-table-cell">'.number_format($data['komisi']).'</td>
			<td><form action="" method="post">
			<input type="number" name="cair" style="width:100px" value="'.$data['komisi'].'" >
			<input type="hidden" name="idsponsor" value="'.$data['mem_id'].'"/>
			<input type="submit" value="GO"/></form></td>
		</tr>
			';
		}	
	}
	echo '
	</tbody>
	</table>
	<small>Untuk memunculkan rekening, <a href="'.$weburl.'dashboard/form?edit=new">tambah setting form</a> dengan setting:<br/>
	- Field : <code>Custom Field</code><br/>
	- Custom Field: <code>rekening</code>'.$infominkomisi.'
	</small>
	</div>';
}
showfooter();
