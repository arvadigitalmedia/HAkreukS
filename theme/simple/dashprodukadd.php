<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Manage Produk';
showheader($head);

if (isset($_POST['urlpage']) && !empty($_POST['urlpage']) && isset($_POST['judulpage']) && !empty($_POST['judulpage'])) {
	if (isset($_FILES['thumb'])) {

    $max_size = 1024000;
    $files = $_FILES['thumb'];
    $whitelist_ext = array('jpeg','jpg','png','gif');
    $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
    $pic_dir = caripath('theme').'/upload';
    
    if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }
    
    $gambar = $editgambar = '';

    if (isset($files['name']) && !empty($files['name'])) {
      $filename = txtonly(strtolower($_POST['judulpage']));
      $target_file = $pic_dir.'/'.$filename;
      $uploadOk = 1;
      $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
      if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
        $uploadOk = 0;
      }
      //Check that the file is of the right type
      if (!in_array($files["type"], $whitelist_type)) {
        $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
        $uploadOk = 0;
      }
      // Check file size
      if ($files["size"] > $max_size) {
        $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
        $uploadOk = 0;
      }
      if ($uploadOk == 1) {
        $file = $files["tmp_name"];
        $target_file = $target_file.'.'.$imageFileType;

        if (class_exists('Imagick')) {
	        // Metode dengan Imagick
	        $img = new Imagick();
	        $img->readImage($file);
	        $width = $img->getImageWidth();
	        if ($width > 800) {
	            $width = 800;
	        }
	        $img->setimagebackgroundcolor('white');
	        $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
	        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
	        $img->setImageCompressionQuality(80);
	        $img->resizeImage($width, 800, Imagick::FILTER_CATROM, 1, true);
	        $img->stripImage();
	        $img->writeImage($target_file);
		    } else {
	        // Metode alternatif tanpa Imagick (menggunakan GD)
	        $img = imagecreatefromstring(file_get_contents($file));
	        $width = imagesx($img);
	        $height = imagesy($img);
	        $new_width = 800;
	        $new_height = floor($height * ($new_width / $width));

	        // Resize image
	        $tmp_img = imagecreatetruecolor($new_width, $new_height);
	        imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

	        // Save the image
	        if ($imageFileType == 'jpg' || $imageFileType == 'jpeg') {
	            imagejpeg($tmp_img, $target_file, 80);
	        } elseif ($imageFileType == 'png') {
	            imagepng($tmp_img, $target_file);
	        } elseif ($imageFileType == 'gif') {
	            imagegif($tmp_img, $target_file);
	        }

	        imagedestroy($img);
	        imagedestroy($tmp_img);
		    }
		    
        $gambar = $filename.'.'.$imageFileType;
        $editgambar = ",`pro_img`='".$gambar."'";
      } else {
        echo '
        <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
          <strong>Error!</strong> '.$txterror.'
          <button type="button" class="btn-close" id="tutup"></button>
        </div>';
      }
    }
  }

	foreach ($_POST['komisi'] as $key => $value) {
		$komisi[$key] = numonly($value);
	}
	$dbkomisi = serialize($komisi);

	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		# Edit Page
		$cek = db_query("UPDATE `sa_page` SET 
			`page_judul` = '".cek($_POST['judulpage'])."',
			`page_diskripsi` = '".cek($_POST['diskripsipage'])."',
			`page_url` = '".cekurlpage($_POST['urlpage'],$_GET['edit'])."',
			`page_iframe` = '".cek($_POST['iframe'])."',
			`page_method`= ".cek($_POST['metodelp']).",			
			`pro_harga` = ".numonly($_POST['harga']).",
			`pro_komisi` = '".$dbkomisi."',
			`pro_file` = '".cek($_POST['namafile'])."',
			`page_fr` = '".serialize($_POST['fr'])."'
			".$editgambar."
			WHERE `page_id`=".$_GET['edit']);
	} else {
		# Simpan di database
		$cek = db_query("INSERT INTO `sa_page` (`page_judul`,`page_diskripsi`,`page_url`,`page_iframe`,`page_method`,`pro_harga`,`pro_komisi`,`pro_file`,`pro_img`,`page_fr`) VALUES 
			('".cek($_POST['judulpage'])."','".cek($_POST['diskripsipage'])."','".cekurlpage($_POST['urlpage'])."','".cek($_POST['iframe'])."',".cek($_POST['metodelp']).",	".numonly($_POST['harga']).",'".$dbkomisi."','".cek($_POST['namafile'])."','".$gambar."','".serialize($_POST['fr'])."')");
	}


	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Produk telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	$page = db_row("SELECT * FROM `sa_page` WHERE `page_id`=".$_GET['edit']);
}
?>

<form action="" method="post" enctype="multipart/form-data">
<a name="form"></a>
<div class="card">
  <div class="card-header">
     Tambah Produk
  </div>
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Nama Produk</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="judulpage" value="<?= $page['page_judul'] ??= '';?>" required>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Harga Produk</label>
	    <div class="col-sm-10">
	      <input type="number" class="form-control" name="harga" value="<?= $page['pro_harga'] ??= '';?>" required>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Nama File / URL Akses</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="namafile" value="<?= $page['pro_file'] ??= '';?>" required>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Diskripsi Produk</label>
	    <div class="col-sm-10">
	      <textarea class="form-control" rows="3" name="diskripsipage"><?= $page['page_diskripsi'] ??= '';?></textarea>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Produk</label>
	    <div class="col-sm-10">
	      <div class="input-group">
			    <span class="input-group-text" id="basic-addon3"><?= $weburl.$datamember['mem_kodeaff'];?>/</span>
			    <input type="text" class="form-control" name="urlpage" value="<?= $page['page_url'] ??= '';?>" required>
			  </div>
	    </div>
	  </div>	  
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Sales Page</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="iframe" value="<?= $page['page_iframe'] ??= 'https://';?>" required>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Metode</label>
	    <div class="col-sm-5">
	    	<?php
	    	$metode = array(
	    		1 => 'Gunakan iFrame',
	    		2 => 'Inject URL',
	    		3 => 'Redirect URL'
	    	);

	    	$metode = apply_filter('page_metode_lp',$metode);

	    	echo '<select name="metodelp" id="metodelp" class="form-select">';
	    	foreach ($metode as $key => $value) {
	    		echo '<option value="'.$key.'"';
	    		if (isset($page['page_method']) && $page['page_method'] == $key) {
	    			echo ' selected';
	    		}
	    		echo '>'.$value.'</option>';
	    	}
	    	echo '</select>';
	    	?>					    	
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Find and Replace</label>
	    <div class="col-sm-10">
	    	<?php 
	    	if (isset($page['page_fr']) && !empty($page['page_fr'])) {
	    		$fr = unserialize($page['page_fr']);
	    	}
	    	for ($i=1; $i <= 5; $i++) :?>
	    	<div class="input-group">
	      	<input type="text" class="form-control" placeholder="find" name="fr[<?= $i;?>][find]" value="<?= $fr[$i]['find'] ??= '';?>">
	      	<input type="text" class="form-control" placeholder="replace" name="fr[<?= $i;?>][replace]" value="<?= $fr[$i]['replace'] ??= '';?>">
	      </div>
	    	<?php endfor; ?>
	      <small class="form-text text-muted">Ubah text landing page (hanya berlaku untuk metode Inject URL)</small>
	    </div>
	  </div>  
	  <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Thumbnail</label>
      <div class="col-sm-10">
        <input type="file" class="form-control" name="thumb" >
        <small class="form-text text-muted">Rekomendasi ukuran: 200 x 200 pixel</small>
        <div class="mt-2" id="previewthumb">
          <?php 
          if (isset($page['pro_img']) && $page['pro_img'] != '') {
            echo '<img src="'.$weburl.'upload/'.$page['pro_img'].'?id='.rand(100,999).'" class="img-fluid img-thumbnail" style="max-width: 200px">';
          }
          ?>
        </div>
      </div>
    </div>
	  <?php 
	  if (isset($page['pro_komisi']) && !empty($page['pro_komisi'])) {
	  	$komisi = unserialize($page['pro_komisi']);
	  }
	  for ($lvl=1; $lvl <= 10 ; $lvl++) : ?>  	

	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Komisi Level <?=$lvl;?></label>
	    <div class="col-sm-5">
	      <div class="input-group">
		      <span class="input-group-text" id="basic-addon3">Premium</span>
		      <input type="number" class="form-control" name="komisi[premium][<?=$lvl;?>]" value="<?= $komisi['premium'][$lvl] ??= '';?>">
		    </div>	    	
	    </div>
	    <div class="col-sm-5">
	      <div class="input-group">
		      <span class="input-group-text" id="basic-addon3">Free</span>
		      <input type="number" class="form-control" name="komisi[free][<?=$lvl;?>]" value="<?= $komisi['free'][$lvl] ??= '';?>">
		    </div>	    	
	    </div>	    
	  </div>

		<?php endfor;?>	  
	  <input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
	</div>
</div>
</form>
<?php showfooter(); ?>