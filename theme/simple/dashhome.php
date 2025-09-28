<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
showheader();
do_action('home_top');
$showdefault = 'YES';

if (isset($settings['homecanvas']) && !empty($settings['homecanvas'])) {
  $homecanvas = json_decode($settings['homecanvas'],TRUE);
  if (is_array($homecanvas) && count($homecanvas) > 0) {
    echo '<div class="row">';
    foreach ($homecanvas as $modul) {      
      if (function_exists('modul_'.$modul['moduleId'])) {
        if (isset($settings[$modul['canvasId']])) {
          $modsettings = json_decode($settings[$modul['canvasId']],TRUE);
          $modsettings = $modsettings['data'];
          if (isset($modsettings['lebar'])) {
            echo '<div class="col-md-'.($modsettings['lebar']*4).'">';
          } else {
            echo '<div class="col-md-4">';
          }
        } else {
          echo '<div class="col-md-4">';
        }
        
        echo call_user_func('modul_'.$modul['moduleId'],$modul['canvasId']);
      } else {
        echo '<div class="col-md-4">
        Modul '.$modul['moduleId'].' tidak ada';
      }
      echo '</div>';
    } 
    echo '</div>';  
    $showdefault = 'NO';
  }
}

if ($showdefault == 'YES') :
  # Tampilkan Home Default
?>

<div class="row">
  <div class="col">
    <?= modul_informasi(); ?>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <?= modul_affiliasi(); ?>
  </div>
  <div class="col-md-6">
    <?= modul_landingpage(); ?>
  </div>
</div>

<div class="row">
  <div class="col-md-6">    
    <?= modul_klienbaru('Klien Baru',5,'[nama] - <a href="https://wa.me/[whatsapp]" target="_blank">[whatsapp]</a> <small>([tgldaftar])</small>'); ?>
  </div>
  <div class="col-md-6">
    <?= modul_akses(); ?>
  </div>
</div>

<div class="row">
  <div class="col">
    <?= modul_grafikvisitor(); ?>
  </div>
</div>

<?php 
endif;
do_action('home_bottom');
showfooter(); ?>