<?php 
$head['pagetitle'] = ucwords($settings['url_produk']);
$head['container'] = 'container-fluid';
$head['scripthead'] = '';

if (!empty($datasponsor['fbpixel'])) {
    $fbpixel = htmlspecialchars($datasponsor['fbpixel'], ENT_QUOTES);
    $head['scripthead'] .= '
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,\'script\',
    \'https://connect.facebook.net/en_US/fbevents.js\');
    fbq(\'init\', \'' . $fbpixel . '\');
    fbq(\'track\', \'PageView\');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=' . $fbpixel . '&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    ';
}

if (!empty($datasponsor['gtm'])) {
    $gtm = htmlspecialchars($datasponsor['gtm'], ENT_QUOTES);
    $head['scripthead'] .= '
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
    new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
    \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,\'script\',\'dataLayer\',\'GTM-' . $gtm . '\');</script>
    <!-- End Google Tag Manager -->
    ';
}

showheader($head);
?>
    <form action="" method="get" class="px-xl-5">
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">     
          <div class="col-12">
            <div class="input-group">
              <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">
              <?php 
              $select = array('','','');
              if (isset($_GET['status']) && is_numeric($_GET['status'])) {
                $select[$_GET['status']] = ' selected';
              }
              ?>
              <input type="submit" value=" Cari " class="btn btn-secondary">
            </div>        
          </div>
        </div>
      </div>
    </div>
    </form>

    <div class="row px-xl-5">
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
          $where = "AND (`page_judul` LIKE '%".$s."%' 
                    OR `page_diskripsi` LIKE '%".$s."%'
                    OR `page_url` LIKE '%".$s."%')";
        }

        $data = db_select("SELECT * FROM `sa_page` 
          WHERE `pro_harga` IS NOT NULL 
          AND `pro_status`=1 ".$where."
          ORDER BY `page_judul` ASC
          LIMIT ".$start.",".$jmlperpage);

				if (count($data) > 0) {

          foreach ($data as $data) {					
              echo '
              <div class="col-lg-2 col-md-3 col-6 pb-1">
                <div class="bg-light mb-4 rounded-3 shadow" style="border-radius: 5px;"> <!-- Container utama dengan rounded -->
                  <div class="product-img position-relative overflow-hidden">
                    <a href="'.$weburl.$data['page_url'].'">';
              if (isset($data['pro_img']) && !empty($data['pro_img'])) {
                echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="img-fluid w-100" style="width:150px; border-top-left-radius: 5px; border-top-right-radius: 5px;" alt="'.$data['page_judul'].'"/>';
              }
              echo '
                      </a>
                  </div>
                  <div class="text-center py-4 px-2 mb-2" style="height:150px; overflow:hidden">
                    <a href="'.$weburl.$data['page_url'].'" class="text-decoration-none text-dark">
                        <h4 style="font-size:1.3em;">'.$data['page_judul'].'</h4>
                    </a>
                    <p style="font-size:1em;">'.$data['page_diskripsi'].'</p>
                  </div>
                  <div class="d-flex">
                    <a href="'.$weburl.$data['page_url'].'" class="flex-fill text-center py-2 text-white text-decoration-none" style="background-color: green; border-bottom-left-radius: 5px;">INFO</a>
                    <a href="'.$weburl.'order/'.$data['page_url'].'" class="flex-fill text-center py-2 text-white text-decoration-none" style="background-color: red; border-bottom-right-radius: 5px;">ORDER</a>
                  </div>
                </div>
              </div>
              ';
          }

				}
			?>
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
    
<?php showfooter(); ?>