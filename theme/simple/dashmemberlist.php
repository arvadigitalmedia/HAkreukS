<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }

if (isset($_POST['memberfu']) && !empty($_POST['memberfu'])) {
	setcookie('memberfu',rawurlencode($_POST['memberfu']),strtotime('+30 days'),'/');
	$ok = 1;
}

$head['pagetitle'] = 'Memberlist';
$head['scripthead'] = '<link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet">';
showheader($head);
if (isset($ok)) {
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Konten Follow Up telah dipasang di link whatsapp member anda. Selamat mencoba.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	# Hapus Member dan Pindahkan downlinenya ke admin
	$cek = db_query("DELETE FROM `sa_member` WHERE `mem_id`=".$_GET['del']);
	$cek = db_query("DELETE FROM `sa_sponsor` WHERE `sp_mem_id`=".$_GET['del']);
	$cek = db_query("UPDATE `sa_sponsor` SET `sp_sponsor_id`=".$iduser.",`sp_network`='[".$iduser."]' WHERE `sp_sponsor_id`=".$_GET['del']);

	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Member ID: '.$_GET['del'].' telah dihapus.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['up']) && is_numeric($_GET['up'])) {
	$upmember = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$_GET['up']." AND `mem_status` = 1");
	

	if (isset($upmember['mem_id'])) {
		db_query("UPDATE `sa_member` SET `mem_status`=2 WHERE `mem_id`=".$_GET['up']);
		sa_notif('upgrade',$_GET['up']);
		
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> '.$upmember['mem_nama'].' telah diupgrade.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Member tidak ditemukan. Mungkin sudah diupgrade sebelumnya.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col-sm-9">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">
				  <?php 
				  $select = array('','','');
				  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				  	$select[$_GET['status']] = ' selected';
				  }
				  ?>
				  <select name="status" class="form-select">
				  	<option value="">All Member</option>
				  	<option value="1"<?=$select[1];?>>Free Member</option>
				  	<option value="2"<?=$select[2];?>>Premium Member</option>
				  </select>
				  <input type="submit" value=" Cari " class="btn btn-secondary">
				</div>	      
	    </div>
	    <div class="col-sm-3 text-end">	    	
	    	<a href="member?edit=new" class="btn btn-success">Add</a> &nbsp;
	    	<a href="export?data=member" class="btn btn-primary">Download</a>
	    </div>
	  </div>
	</div>
</div>
</form>
<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>ID</th>
			<th>Nama</th>
			<th class="d-none d-sm-table-cell">Email</th>
			<th class="d-none d-sm-table-cell">WhatsApp</th>
			<th class="d-none d-sm-table-cell">Sponsor</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php 
		$jmlperpage = 25;
		if (isset($_GET['start']) && is_numeric($_GET['start'])) {
		    $start = ($_GET['start'] - 1) * $jmlperpage;
		    $page = $_GET['start'];
		} else {
		    $start = 0;
		    $page = 1;
		}		
		
		$where = '';
		if (isset($_GET['cari']) && !empty($_GET['cari'])) {
			$s = cek($_GET['cari']);
			$where .= "WHERE (`m`.`mem_nama` LIKE '%".$s."%' 
								OR `m`.`mem_email` LIKE '%".$s."%' 
								OR `m`.`mem_whatsapp` LIKE '%".$s."%' 
								OR `m`.`mem_datalain` LIKE '%".$s."%' 
								OR `m`.`mem_kodeaff` LIKE '%".$s."%')";
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			if ($where == '') {
				$where .= "WHERE `m`.`mem_status`=".$_GET['status'];			
			} else {
				$where .= " AND `m`.`mem_status`=".$_GET['status'];			
			}
		}

		$data = db_select("SELECT 
			`m`.`mem_nama` AS `NamaMember`,
			`m`.`mem_whatsapp` AS `WAMember`,
			`m`.`mem_email` AS `EmailMember`,
			`m`.`mem_id` AS `IDMember`,
			`m`.`mem_status` AS `StatusMember`,
			`s`.`mem_nama` AS `NamaSponsor`,
			`s`.`mem_whatsapp` AS `WASponsor`,
			`s`.`mem_email` AS `EmailSponsor`,
			`s`.`mem_id` AS `IDSponsor`,
			`s`.`mem_status` AS `StatusSponsor`
			FROM `sa_member` `m` LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
			LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id`
			".$where."
			ORDER BY `m`.`mem_tgldaftar` DESC
			LIMIT ".$start.",".$jmlperpage);
		if (count($data) > 0) {
			foreach ($data as $data) {
				if (isset($_POST['memberfu']) && !empty($_POST['memberfu'])) {
					$memberfu = rawurlencode($_POST['memberfu']);
				} elseif (isset($_COOKIE['memberfu'])) {
					$memberfu = $_COOKIE['memberfu'];				
				} else {
					$memberfu = '';
				}

				$memberfu = str_replace('%5Bnama%5D', $data['NamaMember'], $memberfu);

				echo '
				<tr>
				<td>'.$data['IDMember'].'</td>
				<td>
				<a href="member?edit='.$data['IDMember'].'">'.$data['NamaMember'].'</a>';

				if ($data['StatusMember'] == 2) { echo ' <sup><i class="fa-solid fa-circle-check text-success" title="Premium"></i></sup>'; }

				echo '
				<span class="d-sm-none">
					<br/><i class="fa-regular fa-envelope"></i> '.$data['EmailMember'].'
					<br/><i class="fa-brands fa-whatsapp"></i> <a href="https://wa.me/'.$data['WAMember'].'?text='.$memberfu.'" target="_blank">'.$data['WAMember'].'</a>
					<br/><i class="fa-solid fa-user-tie"></i> <a href="member?edit='.$data['IDSponsor'].'">'.$data['NamaSponsor'].'</a>
				</span>
				</td>
				<td class="d-none d-sm-table-cell">'.$data['EmailMember'].'</td>
				<td class="d-none d-sm-table-cell"><a href="https://wa.me/'.$data['WAMember'].'?text='.$memberfu.'" target="_blank">'.$data['WAMember'].'</a></td>
				<td class="d-none d-sm-table-cell"><a href="member?edit='.$data['IDSponsor'].'">'.$data['NamaSponsor'].'</a>';
				
				if ($data['StatusSponsor'] == 2) { echo ' <sup><i class="fa-solid fa-circle-check text-success" title="Premium"></i></sup>'; }

				echo '</td>
				<td class="text-end">';
				
				if ($data['StatusMember'] == 1) {
					echo '<a href="?up='.$data['IDMember'].'"><i class="fa-solid fa-circle-arrow-up text-success" title="Upgrade"></i></a>';
				} 

				if ($data['IDMember'] != 1) {
					echo '
					&nbsp; 
					<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$data['NamaMember'].'" 
					data-bs-id="'.$data['IDMember'].'"><i class="fa-solid fa-trash-can text-danger" title="Delete"></i></a>';
				}

				echo '
				</td>
				</tr>';
			}
		} else {
			echo '<tr><td colspan="4">Belum ada member</td></tr>';
		}
		?>
	</tbody>
</table>
</div>
<?php
$jmlmember = db_var("SELECT count(*)
	FROM `sa_member` `m` LEFT JOIN `sa_sponsor` `k` ON `m`.`mem_id` = `k`.`sp_mem_id` 
	LEFT JOIN `sa_member` `s` ON `k`.`sp_sponsor_id` = `s`.`mem_id`
	".$where);
$jmlpage = ceil($jmlmember/$jmlperpage);
echo '
<nav aria-label="Page navigation" class="mt-3">
  <ul class="pagination">';
if ($jmlpage > 10) {
  if ($page <= 4){
    # Depan
    for ($i=1;$i<=5;$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
    echo '
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
  } elseif ($page >= 5 && $page <= ($jmlpage-5)) {
    # Tengah
    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
    for ($i=($page-2);$i<=($page+2);$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>
    <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
  } else {
    # Belakang
    echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
    <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
    for ($i=($jmlpage-5);$i<=$jmlpage;$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
  }
} else {
  for ($i=1;$i<=$jmlpage;$i++) {
      if ($i == $page) {
          echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
      } else {
          echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
      }
  }
}

echo '
	</ul>
</nav>';
?>

<form action="" method="post">
<div class="card mb-3">
	<div class="card-body">
		<textarea name="memberfu" placeholder="Konten follow up via WhatsApp" class="form-control"></textarea>
		<small class="form-text text-muted mb-3">Silahkan menambah kata-kata follow up sebelum klik link whatsapp klien di atas. Gunakan shortcode [nama] untuk menampilkan nama klien</small>
		<br/><input type="submit" value="Simpan" class="btn btn-primary">
	</div>
</div>
</form>

<!-- Modal -->
<div class="modal fade" id="konfirmasi" tabindex="-1" aria-labelledby="konfirmasilabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="konfirmasilabel">JUDUL</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">ISI
      </div>
      <div class="modal-footer">        
        <a href="#" class="btn btn-secondary delbutton">Hapus</a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<?php
$footer['konfirm'] = "⚠️ Anda akan menghapus <strong>'+nama+'</strong>. Semua downline di bawahnya akan diarahkan ke admin.";
showfooter($footer);
?>