<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Manage Produk';
showheader($head);

if (isset($_GET['nonaktif']) && is_numeric($_GET['nonaktif'])) {
	$cek = db_query("UPDATE `sa_page` SET `pro_status`=0 WHERE `page_id`=".$_GET['nonaktif']);
	$action = 'dinonaktifkan';
} elseif (isset($_GET['aktif']) && is_numeric($_GET['aktif'])) {
	$cek = db_query("UPDATE `sa_page` SET `pro_status`=1 WHERE `page_id`=".$_GET['aktif']);
	$action = 'diaktifkan';
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	$cek = db_query("DELETE FROM `sa_page` WHERE `page_id`=".$_GET['del']);
	$action = 'dihapus';
}

if (isset($cek)) {
	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Produk telah '.$action.'.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col-sm-10">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">				  
				  <input type="submit" value=" Cari " class="btn btn-secondary">
				</div>	      
	    </div>
	    <div class="col-sm-2 text-end">	    	
	    	<a href="?edit=new" class="btn btn-success">Tambah Produk</a>
	    </div>
	  </div>
	</div>
</div>
</form>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama Produk</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$sale = db_select("SELECT `order_idproduk`,count(*) AS `jmlsale` FROM `sa_order` WHERE `order_status`=1 GROUP BY `order_idproduk`");
		foreach ($sale as $sale) {
			$produksale[$sale['order_idproduk']] = $sale['jmlsale'];
		}

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
			$where = "AND (`page_judul` LIKE '%".$s."%' 
								OR `page_diskripsi` LIKE '%".$s."%'
								OR `page_url` LIKE '%".$s."%')";
		}

		$data = db_select("SELECT * FROM `sa_page` 
			WHERE `pro_harga` IS NOT NULL ".$where."
			ORDER BY `page_judul` ASC
			LIMIT ".$start.",".$jmlperpage);

		if (count($data) > 0) {			
			foreach ($data as $data) {
				echo '
			<tr>
			<td>
			<a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a>
			<div class="konten'.$data['page_id'].' konten mt-2">';
			if (isset($data['pro_img']) && !empty($data['pro_img'])) {
				echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="me-2" style="width:150px; float:left" alt="'.$data['page_judul'].'"/>';
			}
			echo '
				URL Affiliasi: 
					<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a>
					&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'\')" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard"><i class="fa-regular fa-copy"></i></a>
					<br/>
				URL Order: <a href="'.$weburl.'order/'.$data['page_url'].'" target="_blank">
					'.$weburl.'order/'.$data['page_url'].'</a><br/>
				URL Sales Page: <a href="'.$data['page_iframe'].'" target="_blank">'.$data['page_iframe'].'</a><br/>
				Harga: '.number_format($data['pro_harga']).'<br/>
				Penjualan: '.number_format($produksale[$data['page_id']]??=0).'
				<div class="mt-2">
					<a href="?edit='.$data['page_id'].'" class="btn btn-success mr-3"><i class="fa-solid fa-pen-to-square" title="Edit produk"></i> Edit</a>
					&nbsp;';
			if ($data['pro_status'] == 1) {
				echo '
					<a href="?nonaktif='.$data['page_id'].'" class="btn btn-warning mr-3" title="Nonaktif produk"><i class="fa-regular fa-circle-stop"></i> Nonaktif</a>
					&nbsp;';
			} else {
				echo '
					<a href="?aktif='.$data['page_id'].'" class="btn btn-primary mr-3" title="Aktifkan produk"><i class="fa-regular fa-circle-play"></i> Aktifkan</a>
					&nbsp;';
			}

			echo '
					<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$data['page_judul'].'" 
					data-bs-id="'.$data['page_id'].'" class="btn btn-danger mr-3" title="Hapus produk"><i class="fa-solid fa-trash-can"></i> Hapus</a>
				</div>
			</div>
			</td>
			</tr>';
			}  				
		}
		?>
	</tbody>
</table>
</div>
<?php
$jmlproduk = db_var("SELECT * FROM `sa_page` 
			WHERE `pro_harga` IS NOT NULL ".$where);
$jmlpage = ceil($jmlproduk/$jmlperpage);
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
<?php showfooter(); ?>