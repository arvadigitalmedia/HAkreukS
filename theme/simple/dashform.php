<?php 
if (isset($_GET['edit'])) :
	include('dashformadd.php');
else:
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Setting Form';
showheader($head);
if (isset($_POST['sort'])) {
	$ins = '';
	foreach ($_POST['sort'] as $key => $value) {
		$ins .= "(".$value.",".$key."),";
	}

	if ($ins != '') {	  			
		$cek = db_query("INSERT INTO `sa_form` (`ff_id`,`ff_sort`) VALUES ".substr($ins, 0,-1)." 
			ON DUPLICATE KEY UPDATE `ff_sort`= VALUES(`ff_sort`)");
		if ($cek === false) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> '.db_error().'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Urutan form telah disimpan.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	}
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	$cek = db_query("DELETE FROM `sa_form` WHERE `ff_id`=".$_GET['del']);
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Isian form telah dihapus.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
}
?>
<form action="" method="post">
<div class="card">	  
  <div class="card-body">
	  <ul id="sortable" class="ui-sortable collection">
	  	<?php 	  	
	  	$data = db_select("SELECT * FROM `sa_form` ORDER BY `ff_sort`");
	  	foreach ($data as $data) :
	  	?>
	  		
	  	<li class="row form-group ui-state-default ui-sortable-handle collection-item avatar z-depth-3">
				<div class="col-9"><?php echo $data['ff_label'];?> <small>(<?php echo $data['ff_field'];?>)</small></div>				
				<div class="col-3 text-end">
				  <a href="form?edit=<?php echo $data['ff_id'];?>"><i class="fa-solid fa-pen-to-square text-success"></i></a>			  
				  &nbsp;<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="<?php echo $data['ff_label'];?>" 
						data-bs-id="<?php echo $data['ff_id'];?>"><i class="fa-solid fa-trash-can text-danger"></i></a>
				</div>
				<input type="hidden" name="sort[]" value="<?php echo $data['ff_id'];?>"/>
			</li>

			<?php endforeach; ?>
		</ul>	
		<div class="text-center">
			<input type="submit" value="Simpan" class="btn btn-success">
			<a href="form?edit=new" class="btn btn-primary">Tambah</a>
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
$footer['konfirm'] = "⚠️ Anda akan menghapus isian <strong>'+nama+'</strong> dari formulir.";
showfooter($footer);
endif;
?>