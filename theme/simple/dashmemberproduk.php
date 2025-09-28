<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Daftar Produk yg Tersedia';
showheader($head);
?>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama Produk</th>
			<th class="text-end">Harga</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$data = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL AND `pro_status`=1");
		$order = db_select("SELECT * FROM `sa_order` WHERE `order_idmember`=".$datamember['mem_id']." AND `order_status`=1");
		if (count($order) > 0) {
			foreach ($order as $order) {
				$orderlist[$order['order_idproduk']] = 1;
			}
		}
		if (count($data) > 0) {			
			foreach ($data as $data) {
				if ($settings['khususpremium'] == 1 && $datamember['mem_status'] < 2) {
					$urlaff = '<em>URL Affiliasi khusus Premium Member</em>';
				} elseif ($data['pro_status'] == 0) {
					$urlaff = '<em>Produk dinonaktifkan</em>';
				} else {
					$urlaff = '<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a>
					&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'\')" 
            style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard">
          <i class="fa-regular fa-copy"></i></a> 
					';
				}
				echo '
			<tr>
			<td>
			<a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a>
			<div class="konten'.$data['page_id'].' konten mt-2">
				<p>';
				if (isset($data['pro_img']) && !empty($data['pro_img'])) {
					echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="float-start me-2" style="width:150px" alt="'.$data['page_judul'].'"/>';
				}
				echo '
				'.$data['page_diskripsi'].'</p>
				<p>URL Affiliasi: '.$urlaff.'</p>         
			</div>
			</td>
			<td class="text-end">'.number_format($data['pro_harga']).'</td>
			<td class="text-end">';
			if (isset($orderlist[$data['page_id']]) || $data['pro_harga'] == 0) {
				echo '<a href="'.$weburl.'dashboard/akses/'.$data['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a>';					
			} elseif ($data['pro_status'] == 1) {
				echo '<a href="'.$weburl.'order/'.$data['page_url'].'" class="btn btn-sm btn-primary" target="_blank">Order</a>';
			}
			echo '
			</td>
			</tr>';
			}  				
		}
		?>
	</tbody>
</table>
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