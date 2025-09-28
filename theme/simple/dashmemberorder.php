<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Order Anda';
showheader($head);
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
			<th>Produk</th>
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
				$where = " AND `order_id`=".$_GET['cari'];
			} else {
				$where = " AND (`sa_page`.`page_judul` LIKE '%".$s."%' 
								OR `sa_page`.`page_diskripsi` LIKE '%".$s."%'
								OR `sa_page`.`page_url` LIKE '%".$s."%')";
			}
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			$where .= " AND `sa_order`.`order_status`=".$_GET['status'];	
		}

		$order = db_select("SELECT * FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_idmember`=".$datamember['mem_id'].$where."
			ORDER BY `order_tglorder` DESC
			LIMIT ".$start.",".$jmlperpage);
		if (count($order) > 0) {
			foreach ($order as $order) {
				echo '
				<tr>
					<td><a href="'.$weburl.'invoice/'.$order['order_id'].'" target="_blank">'.$order['order_id'].'</td>
					<td class="d-none d-sm-table-cell">'.$order['order_tglorder'].'</td>
					<td>
					<span class="d-none d-sm-block">'.$order['page_judul'].'</span>
					<span class="d-sm-none">
					<strong>'.$order['mem_nama'].'</strong>
					<small>('.$order['order_tglorder'].')</small>
					<br/>Produk: '.$order['page_judul'].'<br/>
					Harga: '.number_format($order['order_hargaunik']).'<br/>';
					if ($order['order_status'] == 0) {
						echo '<a href="'.$weburl.'invoice/'.$order['order_id'].'" class="btn btn-sm btn-success" target="_blank">Cek Invoice</a>';
					} else {
						echo '<a href="'.$weburl.'dashboard/akses/'.$order['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a>';
					}
				echo '
					</span>
					</td>					
					<td class="d-none d-sm-table-cell text-end">'.number_format($order['order_hargaunik']).'</td>
					<td class="d-none d-sm-table-cell text-end">';
				if ($order['order_status'] == 0) {
					echo '<a href="'.$weburl.'invoice/'.$order['order_id'].'" class="btn btn-sm btn-success" target="_blank">Cek Invoice</a>';
				} else {
					echo '<a href="'.$weburl.'dashboard/akses/'.$order['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a>';
				}
				echo '
					</td>
				</tr>
				';
			}
		}
		?>
	</tbody>
</table>
<?php
$jmlmember = db_var("SELECT count(*) FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_idmember`=".$datamember['mem_id'].$where);
$jmlpage = floor(($jmlmember/$jmlperpage)+1);
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
<?php showfooter();?>