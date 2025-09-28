<!-- Content End -->
</div>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
<!-- <script src='https://code.jquery.com/mobile/git/jquery.mobile-git.js'></script> -->
<script src='https://code.jquery.com/ui/1.10.3/jquery-ui.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js'></script>
<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

<?php
echo $footer['scriptfoot'] ??='';
$notif = $footer['konfirm']??='';
?>
<script type="text/javascript">
	$(document).ready(function () {
		// Listen for the modal show event
	  $('#konfirmasi').on('show.bs.modal', function (event) {
	    // Get the button that triggered the modal
	    var button = $(event.relatedTarget);
	    
	    // Get the value of the data-bs-name attribute
	    var nama = button.data('bs-nama');
	    var id = button.data('bs-id');
	    
	    // Change the content of the modal body based on the value of the data-bs-name attribute
	    $(".modal-title").text('Hapus '+nama);
	    $(".modal-body").html('<?= $notif;?>');
	    $(".delbutton").attr("href", "?del="+id)
  	});


    $(".konten").hide(); // sembunyikan semua konten pada awalnya

    $('.info').click(function() {
        // Temukan konten terkait dengan data-target yang sesuai
        var target = $(this).data('target');
        var konten = $('.' + target);

        // Sembunyikan semua konten terlebih dahulu
        $(".konten").not(konten).slideUp();

        // Toggle (sembunyikan/tampilkan) konten yang diklik
        konten.slideToggle();
    });

    $('#nameInput').on('change', function() {
      if ($(this).val() === 'custom') {
        $('#customInputContainer').removeClass('d-none');
      } else {
        $('#customInputContainer').addClass('d-none');
      }
    });

    <?php     
    if (isset($footer['custom'])) {
      echo "$('#customInputContainer').removeClass('d-none');"."\n";
    }
    ?>

    $('#typeInput').on('change', function() {
      if ($(this).val() === 'select') {
        $('#optionsInputContainer').removeClass('d-none');
      } else {
        $('#optionsInputContainer').addClass('d-none');
      }
    });

    <?php     
    if (isset($editform['ff_type']) && $editform['ff_type'] == 'select') {
      echo "$('#optionsInputContainer').removeClass('d-none');"."\n";
    }
    ?>

    $('input[type=file]').on('change', function() {
        // Mendapatkan nama input file yang dipilih
        var inputName = $(this).attr('name');
        // Mendapatkan file yang dipilih
        var file = $(this).prop('files')[0];
        // Membuat elemen gambar untuk menampilkan preview
        var img = $('<img>', {
            class: 'img-fluid img-thumbnail',
            style: 'max-width: 200px',
            alt: inputName
        });
        // Membuat objek URL untuk file yang dipilih
        var url = URL.createObjectURL(file);
        // Menambahkan URL ke elemen gambar
        img.attr('src', url);
        // Menambahkan elemen gambar ke div preview yang sesuai
        $('#preview' + inputName).empty().append(img);
    });

    $("#fieldblock1, #fieldblock2, #fieldblock3, #fieldblock4").hide();
    <?php if (isset($footer['showfield']) && $footer['showfield'] != '') { echo $footer['showfield']; } ?>

    $("#service").change(function() {            
      field1 = field2 = field3 = field4 ='hide';
      switch ($('#service').val()) {
      <?php
      if (isset($footer['services']) && is_array($footer['services'])) { 
        foreach ($footer['services'] as $service) {
          echo '
          case \''.$service['file'].'\':';
          if (isset($service['data']) && count($service['data']) > 0) {
            $f = 1;
            foreach ($service['data'] as $datafield) {
              echo '
            field'.$f.' = \''.$datafield['label'].'\';';
              $f++;
            }
              echo '
            url = \''.str_replace('https', 'https:', $service['url']).'\';';
          }
          echo '
            break;';
        }
      }
      ?>
      }

      $("#fieldblock1, #fieldblock2, #fieldblock3, #fieldblock4").hide();

      if (field1 != 'hide'){ $("#fieldblock1").show(); $("#field1").html(field1);}
      if (field2 != 'hide'){ $("#fieldblock2").show(); $("#field2").html(field2);}
      if (field3 != 'hide'){ $("#fieldblock3").show(); $("#field3").html(field3);}
      if (field4 != 'hide'){ $("#fieldblock4").show(); $("#field4").html(field4);}
      $("#url").html('<a href="'+url+'" target="_blank">'+url+'</a>');

    });
  });
</script>

<script id="rendered-js" >
    $(function () {
      $("#sortable").sortable();
      $("#sortable").disableSelection();
    });
</script>
</body>
</html>