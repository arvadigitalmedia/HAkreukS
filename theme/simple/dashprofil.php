<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Edit Profil';
$head['scripthead'] = '
<style type="text/css">
    .password-wrapper {
      position: relative;
    }
    
    .password-wrapper input[type="password"] {
      padding-right: 30px; /* Ruang untuk ikon */
    }
    
    .password-wrapper .toggle-password {
      position: absolute;
      top: 50%;
      right: 5px;
      transform: translateY(-50%);
      cursor: pointer;
    }
</style>
<script>
  function togglePassword() {
    var passwordInput = document.getElementById("password");
    var toggleBtn = document.getElementById("togglePassword");

    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      toggleBtn.innerHTML = \'<i class="fas fa-eye-slash text-secondary"></i>\';
    } else {
      passwordInput.type = "password";
      toggleBtn.innerHTML = \'<i class="fas fa-eye text-secondary"></i>\';
    }
  }
</script>';
showheader($head);
$editmember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$iduser);
$dataform = extractdata($editmember);

if (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
	if (db_exist("SELECT `mem_email` FROM `sa_member` 
		WHERE `mem_email`='".cek($_POST['email'])."' AND `mem_id` != ".$iduser)) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> Email sudah ada yang menggunakan
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		$defaultkey = array('nama','email','password','whatsapp','kodeaff');
		$datalain = '';		

		foreach ($_POST as $key => $value) {			
			if (in_array($key, $defaultkey)) {
				${$key} = cek($value);
			} else {
				$datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
			}
		}

		if (isset($_FILES) && count($_FILES) > 0) {
			$max_size = 1024000;
			$whitelist_ext = array('jpeg','jpg','png','gif');
			$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
			$pic_dir = caripath('theme').'/upload';
			
			if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

			foreach($_FILES as $field => $files) {
				if (isset($files['name']) && !empty($files['name'])) {
					$filename = $iduser.'_'.$field;
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
				    $target_file = $target_file . '.' . $imageFileType;

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

				    $datalain .= '[' . txtonly(strtolower($field)) . '|' . $filename . '.' . $imageFileType . ']';
					}

	    	} else {
	    		if (isset($dataform[$field]) && !empty($dataform[$field])) {
	    			$datalain .= '['.txtonly(strtolower($field)).'|'.$dataform[$field].']';	
	    		} else {
	    			$datalain .= '['.txtonly(strtolower($field)).'| ]';	
	    		}	    		
	    	}
			}
		}

		if (isset($_POST['kodeaff']) && !empty($_POST['kodeaff'])) {
			if ($_POST['kodeaff'] == $editmember['mem_kodeaff']) {
				$kodeaff = $editmember['mem_kodeaff'];
			} else {
				$kodeaff = cekkodeaff(txtonly(strtolower($_POST['kodeaff'])));
			}
		} else {
			$kodeaff = $editmember['mem_kodeaff'];	
		}

		if (isset($_POST['password']) && !empty($_POST['password'])) {
			$password = ",`mem_password` = '".create_hash($_POST['password'])."'";
		} else {
			$password = '';
		}		
		
		if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }

		$cek = db_query("UPDATE `sa_member` SET 
			`mem_nama`='".$nama."',
			`mem_email`='".$email."',
			`mem_whatsapp`='".$whatsapp."',
			`mem_kodeaff`='".$kodeaff."',
			`mem_datalain`='".$datalain."'
			".$password."
			WHERE `mem_id`=".$iduser);
		
		$editmember = db_row("SELECT * FROM `sa_member` 
		LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
		WHERE `mem_id`=".$iduser);
		$dataform = extractdata($editmember);
	}

	if (isset($cek)) {
		if ($cek === false) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> '.db_error().'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Data member telah disimpan.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	}
}	

?>
<form action="" method="post" enctype="multipart/form-data">
<div class="card">
  <div class="card-body">
	  <div class="mb-3 row">
      <label class="col-sm-4 col-form-label text-start">ID Member</label>
      <div class="col-sm-3">
        <input type="number" id="mem_id" class="form-control" value="<?= $editmember['mem_id'];?>" name="mem_id" disabled>
        
      </div>
    </div>
	  <?php 	 
	  echo form_builder('profil',$dataform); ?>
	  <input type="submit" class="btn btn-success" value=" SIMPAN ">
	</div>
</div>
</form>

<?php showfooter(); ?>