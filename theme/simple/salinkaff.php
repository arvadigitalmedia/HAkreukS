<?php
if (isset($_POST['kodeaff']) && !empty($_POST['kodeaff'])) {
	if (substr($_POST['kodeaff'], 0,4) == 'http') {
		# Berarti ada http-nya jadi hapus dulu		
		$kodeaff = str_replace($weburl, '', $_POST['kodeaff']);
		if (substr($kodeaff, 0,4) == 'http') {
			# Jika masih ada, berarti dia input http padahal webnya https
			$newweb = str_replace('https://','http://',$weburl);
			$kodeaff = str_replace($newweb, '', $_POST['kodeaff']);
		}
		$kodeaff = txtonly($kodeaff);
	} else {
		$kodeaff = txtonly($_POST['kodeaff']);
	}

	# Cek apakah ada pemiliknya
	$setkhususpremium = getsettings('khususpremium');
	if ($setkhususpremium == 1) {
		# Affiliasi hanya khusus premium
		$khususpremium = " AND `mem_status` > 1";
	} else {
		$khususpremium = "";
	}

	$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_kodeaff`='".strtolower($kodeaff)."'".$khususpremium);
	if (isset($datasponsor['mem_id'])) {
		# Lempar ke URL Affiliasi
		header("Location:".$weburl.$datasponsor['mem_kodeaff']);
		die(); exit();
	} else {
		$error = 'Maaf, URL tidak valid atau sponsor anda belum melakukan upgrade';
	}
}
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>URL Sponsor Anda</title>

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body>
	<div class="container-fluid p-md-5 p-3 text-center">
	  <!-- Content here -->
	  <div class="row mt-3 align-items-center">
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-4 col-sm-12 align-self-center bg-body-tertiary rounded p-3 border">
	    	<?php 
	    	if (isset($error) && !empty($error)) { 
		    	echo '
		    	<div class="alert alert-danger alert-dismissible fade show" role="alert">
					  <strong>Error!</strong> '.$error.'.
					  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>'; 
				} 
				?>     
	      <form action="" method="post">
		      <div class="mb-3 row">
		      	<h2>Masukkan Link Sponsor Anda</h2>
		      </div>
		      <div class="mb-3 row">
				    <div class="col-sm-12">
				      <div class="input-group mb-3">
							  <span class="input-group-text" id="basic-addon1"><?= $weburl;?></span>
							  <input type="text" class="form-control" name="kodeaff" placeholder="kodeaffsponsor" aria-describedby="basic-addon1">
							</div>
				    </div>
				  </div>
				  <input type="submit" class="btn btn-success" value=" OKE ">				  
				</form>
	    </div>
	    <div class="col d-none d-md-block align-self-end">	      
	    </div>
	  </div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>