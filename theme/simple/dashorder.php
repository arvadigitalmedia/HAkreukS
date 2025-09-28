<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Order List';
showheader($head);

if (isset($_GET['proses']) && is_numeric($_GET['proses'])) {
	$idinvoice = $_GET['proses'];
	$staff = $datamember['mem_id'];
	include('prosesorder.php');
} elseif (isset($_GET['batal']) && is_numeric($_GET['batal']) && $_GET['batal'] > 0) {
	$proses = db_row("SELECT * FROM `sa_order`
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`= `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_status` = 1 AND `sa_order`.`order_id`=".$_GET['batal']);
	if (isset($proses['order_id'])) {
		# Update data order
		db_query("UPDATE `sa_order` SET `order_status`=0,`order_idstaff`=".$datamember['mem_id'].",`order_tglbayar`=NULL WHERE `order_id`=".$proses['order_id']);
		db_query("DELETE FROM `sa_laporan` WHERE `lap_idorder`=".$_GET['batal']);
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Order '.$proses['order_id'].' telah dibatalkan 🙏
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
	} else {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Order tidak ditemukan. Mungkin sudah dihapus atau sudah dibatalkan sebelumnya.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	db_query("DELETE FROM `sa_order` WHERE `order_id`=".$_GET['del']);
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Order '.$_GET['del'].' telah dihapus 🙏
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
}
?>
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">
				  <?php 
				  $select = array('','','');
				  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				  	$select[$_GET['status']] = ' selected';
				  }
				  ?>
				  <select name="status" class="form-select">
				  	<option value="">All Order</option>
				  	<option value="0"<?=$select[0];?>>Belum Lunas</option>
				  	<option value="1"<?=$select[1];?>>Lunas</option>
				  </select>
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
			<th>ID</th>
			<th class="d-none d-sm-table-cell">Tgl Order</th>
			<th>Nama</th>
			<th class="d-none d-sm-table-cell">Produk</th>
			<th class="d-none d-sm-table-cell text-end">Harga</th>
			<th class="d-none d-sm-table-cell">&nbsp;</th>
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
			if (is_numeric($_GET['cari'])) {
				$where = "WHERE `order_id`=".$_GET['cari'];
			} else {
				$where = "WHERE (`sa_member`.`mem_nama` LIKE '%".$s."%' 
								OR `sa_member`.`mem_email` LIKE '%".$s."%' 
								OR `sa_member`.`mem_whatsapp` LIKE '%".$s."%' 
								OR `sa_member`.`mem_datalain` LIKE '%".$s."%' 
								OR `sa_member`.`mem_kodeaff` LIKE '%".$s."%'
								OR `sa_page`.`page_judul` LIKE '%".$s."%' 
								OR `sa_page`.`page_diskripsi` LIKE '%".$s."%'
								OR `sa_page`.`page_url` LIKE '%".$s."%')";
			}
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			if ($where == '') {
				$where .= "WHERE `sa_order`.`order_status`=".$_GET['status'];			
			} else {
				$where .= " AND `sa_order`.`order_status`=".$_GET['status'];			
			}
		}

		$order = db_select("SELECT * FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			".$where."
			ORDER BY `order_tglorder` DESC
			LIMIT ".$start.",".$jmlperpage);
		if (count($order) > 0) {
			foreach ($order as $order) {
				echo '
				<tr>
					<td><a href="'.$weburl.'invoice/'.$order['order_id'].'" target="_blank">'.$order['order_id'].'</td>
					<td class="d-none d-sm-table-cell">'.$order['order_tglorder'].'</td>
					<td>
					<span class="d-none d-sm-block">'.$order['mem_nama'].'</span>
					<span class="d-sm-none">
					<strong>'.$order['mem_nama'].'</strong>
					<small>('.$order['order_tglorder'].')</small>
					<br/>Produk: '.$order['page_judul'].'<br/>
					Harga: '.number_format($order['order_hargaunik']).'<br/>';
				if ($order['order_status'] == 0) {
					echo '<a href="'.$weburl.'dashboard/orderlist?proses='.$order['order_id'].'" class="btn btn-sm btn-success">Proses</a>';
				} else {
					echo '<a href="'.$weburl.'dashboard/orderlist?batal='.$order['order_id'].'" class="btn btn-sm btn-warning">Batal</a>';
				}
				echo '
					&nbsp;&nbsp;<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$order['page_judul'].' oleh '.$order['mem_nama'].'" 
					data-bs-id="'.$order['order_id'].'" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can" title="Delete"></i></a>
					</span>
					</td>
					<td class="d-none d-sm-table-cell">'.$order['page_judul'].'</td>
					<td class="d-none d-sm-table-cell text-end">'.number_format($order['order_hargaunik']).'</td>
					<td class="d-none d-sm-table-cell text-end">';
				if ($order['order_status'] == 0) {
					echo '<a href="'.$weburl.'dashboard/orderlist?proses='.$order['order_id'].'" class="btn btn-sm btn-success">Proses</a>';
				} else {
					echo '<a href="'.$weburl.'dashboard/orderlist?batal='.$order['order_id'].'" class="btn btn-sm btn-warning">Batal</a>';
				}
				echo '
					&nbsp;&nbsp;<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$order['page_judul'].' oleh '.$order['mem_nama'].'" 
					data-bs-id="'.$order['order_id'].'" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can" title="Delete"></i></a>
				</tr>
				';
			}
		}
		?>
	</tbody>
</table>
</div>
<?php
$jmlmember = db_var("SELECT count(*) FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
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
$footer['konfirm'] = "⚠️ Anda akan menghapus order <strong>'+nama+'</strong>";
showfooter($footer);
?>