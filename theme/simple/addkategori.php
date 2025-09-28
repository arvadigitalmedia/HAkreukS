<?php
if (isset($_POST['kat_nama']) && !empty($_POST['kat_nama']) && isset($_POST['kat_slug'])) {
	if (empty($_POST['kat_slug'])) {
		$kat_slug = txtonly(strtolower($_POST['kat_nama']));
	} else {
		$kat_slug = txtonly(strtolower($_POST['kat_slug']));		
	}

	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		$cek = db_query("UPDATE `sa_kategori` SET 
			`kat_nama`='".cek($_POST['kat_nama'])."',`kat_slug`='".cek($kat_slug)."'
			WHERE `kat_id`=".$_GET['edit']);
	} else {
		$cek = db_insert("INSERT INTO `sa_kategori` (`kat_parent_id`,`kat_nama`,`kat_slug`) 
				VALUES (".numonly($_POST['kat_parent_id']).",'".cek($_POST['kat_nama'])."','".cek($kat_slug)."')");
	}

	if ($cek === false) {
		echo '
    <div class="alert alert-danger alert-dismissible fade show" id="peringatan">
      <strong>Error!</strong> '.db_error().'
      <button type="button" class="btn-close" id="tutup"></button>
    </div>';
	} else {
		echo '
		<div class="alert alert-success alert-dismissible fade show" id="peringatan">
		  <strong>Ok!</strong> Kategori telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	$kat = db_row("SELECT * FROM `sa_kategori` WHERE `kat_id`=".$_GET['edit']);
	$parent_id = $kat['kat_parent_id'];
} else {
	$parent_id = $_GET['add']??=0;
}
?>
<div class="card">
  <div class="card-header">
    Tambah Kategori
  </div>
  <div class="card-body">
		<form action="" method="post">
			<div class="mb-3 row">
		    <label class="col-sm-2 col-form-label">Nama Kategori</label>
		    <div class="col-sm-10">
		      <input type="text" class="form-control" name="kat_nama" value="<?= $kat['kat_nama']??='';?>" required>
		    </div>
		  </div>
		  <div class="mb-3 row">
		    <label class="col-sm-2 col-form-label">URL Kategori</label>
		    <div class="col-sm-10">
		      <div class="input-group">
		        <span class="input-group-text" id="basic-addon3"><?= $weburl;?>kategori/</span>
		        <input type="text" class="form-control" name="kat_slug" value="<?= $kat['kat_slug']??='';?>">
		      </div>
		    </div>
		  </div>
		  <input type="hidden" name="kat_parent_id" value="<?= $parent_id; ?>"/>
		  <input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
		</form>
	</div>
</div>