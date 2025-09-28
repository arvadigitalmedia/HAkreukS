<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle'] ='Setting Payment';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style>
.card-header .fas.fa-caret-down {
  transition: transform 0.2s;
}

.card-header.collapsed .fas.fa-caret-down {
  transform: rotate(-90deg);
}
a[id="fr-logo"] {
  height:1px !important;
}
p[data-f-id="pbf"] {
  height:1px !important;
}
a[href*="www.froala.com"] {
  height:1px !important;
  background: #fff !important
}
</style>';
showheader($head);
if (isset($_POST) && count($_POST) > 0) {
	$newsettings = $_POST;
	$newsettings = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$newsettings);
	if (!isset($_POST['tripay_sandbox'])) {
		$newsettings['tripay_sandbox'] = 0;
	}

	$settings = updatesettings($newsettings);
	if ($settings === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Setting telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>

<form action="" method="post">
<div class="card mb-3">
  <div class="card-header" onclick="toggleCardBody('manual')">
    <i class="fas fa-caret-down"></i> Setting Pembayaran
  </div>
  <div class="card-body" id="manual">
  	<div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Kode Unik</label>
	    <div class="col-sm-10">
	      <select name="kodeunik" class="form-select">
	      	<?php
	      	$unik = array('','','');
	      	if (isset($settings['kodeunik'])) { $unik[$settings['kodeunik']] = ' selected'; }	      	
	      	?>
	      	<option value="0"<?= $unik[0]; ?>>Tanpa Kode Unik (100,000)</option>
	      	<option value="1"<?= $unik[1]; ?>>Kurangi dari harga asli (99,003)</option>
	      	<option value="2"<?= $unik[2]; ?>>Tambah dari harga asli (100,003)</option>
	      </select>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Instruksi Pembayaran Manual</label>
	    <div class="col-sm-10">
	      <textarea class="form-control" id="editor" rows="5"  name="carapembayaran"><?= htmlspecialchars($settings['carapembayaran'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
	      <small class="form-text text-muted">Petunjuk cara pembayaran di halaman invoice. Gunakan shortcode:<br/>
	      <code>[harga]</code> utk menampilkan harga asli<br/>
	    	<code>[hargaunik]</code> utk menampilkan harga dg kode unik<br/>
	    	<code>[hargacopy]</code> utk menampilkan harga dg kode unik tanpa penanda ribuan<br/>
	    	<code>[namaproduk]</code> utk menampilkan nama produk<br/>
	    	<code>[copy data="ISI_DATA"]</code> utk menampilkan tombol copy isi data</small>
	    </div>
	  </div>
  </div>
</div>
<div class="card">
  <div class="card-header" onclick="toggleCardBody('tripay')">
    <i class="fas fa-caret-down"></i> Integrasi Payment Gateway Tripay
  </div>
  <div class="card-body" id="tripay">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Kode Merchant</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_merchant" value="<?= $settings['tripay_merchant'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">API Key</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_api" value="<?= $settings['tripay_api'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Private Key</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_private" value="<?= $settings['tripay_private'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Callback</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" value="<?= $weburl;?>tripaycall.php" disabled>
	      <div class="form-text">Copy Paste ke <code>Merchant > Detail</code> di dashboard Tripay</div>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Mode Sandbox</label>
	    <div class="col-sm-10">
	    	<div class="form-check">
		      <input type="checkbox" class="form-check-input" name="tripay_sandbox" value="1" 
		      <?php if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) { echo ' checked'; } ?>>
		    </div>
	    </div>
	  </div>
	  <div class="mb-3 row">
	  	<div class="col">Dapatkan akun <a href="https://tripay.co.id/?ref=TP28329" target="_blank">Tripay di sini</a></div>
	  </div>
	</div>
</div>
<input type="submit" value="Simpan" class="btn btn-success mt-3">
</form>
<?php 
$footer['scriptfoot'] = '
<script type="text/javascript" src="'.$weburl.'editor/js/froala_editor.pkgd.min.js"></script>
<script>
  document.addEventListener(\'DOMContentLoaded\', function () {
    new FroalaEditor(\'#editor\', {
      imageUploadURL: \''.$weburl.'upload_image.php\',
      imageUploadParams: {
        id: \'my_editor\'
      },
      codeViewKeepOriginal: true,
      htmlUntouched: true,
      htmlAllowedTags: [\'.*\'], // Allow all HTML tags
      htmlAllowedAttrs: [\'.*\'], // Allow all attributes
      htmlRemoveTags: [],
      events: {
        \'image.beforeUpload\': function (files) {
          var editor = this;

          // Create a FormData object.
          var formData = new FormData();

          // Append the uploaded image to the form data.
          formData.append(\'file\', files[0]);

          // Get the article title and append it to the form data.
          // var judulArtikel = document.getElementById(\'judul\').value;
          formData.append(\'judul\', \'payment\');

          // Make the AJAX request.
          fetch(\''.$weburl.'upload_image.php\', {
            method: \'POST\',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.link) {
              // Insert the image into the editor.
              editor.image.insert(data.link, null, null, editor.image.get());
            } else {
              console.error(\'Upload failed:\', data.error);
            }
          })
          .catch(error => {
            console.error(\'Error:\', error);
          });

          // Prevent the default behavior.
          return false;
        }
      }
    });
  });

function toggleCardBody(boxId) {
  const cardBody = document.getElementById(boxId);
  const cardHeader = cardBody.previousElementSibling;

  if (cardBody.style.display === \'none\' || cardBody.style.display === \'\') {
    cardBody.style.display = \'block\';
    cardHeader.classList.remove(\'collapsed\');
  } else {
    cardBody.style.display = \'none\';
    cardHeader.classList.add(\'collapsed\');
  }
}
</script>';
showfooter($footer); ?>