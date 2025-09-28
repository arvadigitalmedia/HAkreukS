<?php
# Hapus dulu menu materi
if (isset($settings['url_materi'])) {
  unset($menu[$settings['url_materi']]);
}
if (isset($datamember['mem_role']) && $datamember['mem_role'] >= 5) {
  foreach ($menu as $keymenu => $menuadmin) {
    if (isset($menuadmin['label'])) {      
      if (isset($menuadmin['submenu'])) { 
        $submenu = '';
        foreach ($menuadmin['submenu'] as $key => $value) {          
          if (isset($value[2])) {
            if ($datamember['mem_role'] >= $value[2]) {
              $submenu .= '<li><a class="dropdown-item" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a></li>';
            }
          } else {
            $submenu .= '<li><a class="dropdown-item" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a></li>';
          }
        }
        if ($submenu != '') {
          echo '
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              '.$menuadmin['label'].'
            </a>
            <ul class="dropdown-menu">'.$submenu.'</ul>
          </li>
          ';
        }
      } else {
        echo '
        <li class="nav-item">
          <a class="nav-link" href="'.$weburl.$keymenu.'">'.$menuadmin['label'].'</a>
        </li>';
      }
    } 
  }
} else {
  foreach ($menu as $keymenu => $menuadmin) {
    if (isset($menuadmin['label'])) {
      if ($keymenu == 'membermenu') {
        $menumember = $menu['membermenu']['submenu'];

        if (isset($settings['klienoff']) && $settings['klienoff'] == 1) {
          unset($menumember['klien']);
        }
        if (isset($settings['networkoff']) && $settings['networkoff'] == 1) {
          unset($menumember['jaringan']);
        }        
        
        foreach ($menumember as $key => $value) {
          if (isset($value[2])) {
            if (isset($datamember['mem_role']) && $datamember['mem_role'] >= $value[2]) {
              echo '
              <li class="nav-item">
                <a class="nav-link" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a>
              </li>
              ';
            }
          } else {
            echo '
              <li class="nav-item">
                <a class="nav-link" href="'.$weburl.'dashboard/'.$key.'">'.$value[0].'</a>
              </li>
              ';
          }
        }
      } else {
        if (!isset($menuadmin['submenu'])) {
          echo '
            <li class="nav-item">
              <a class="nav-link" href="'.$weburl.$keymenu.'">'.$menuadmin['label'].'</a>
            </li>
            ';
        }
      }
    }
  }
}

do_action('nav_menu');