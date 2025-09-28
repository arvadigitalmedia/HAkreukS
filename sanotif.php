<?php 
$data = db_row("SELECT * FROM `sa_member` 
LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` 
WHERE `mem_id`=".$iduser);
$datamember = extractdata($data);
$datamember['kodeaff'] = $weburl.$datamember['kodeaff'];
$dataadmin = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=1");
$dataadmin = extractdata($dataadmin);

if (isset($data['sp_sponsor_id'])) {    
  $data = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$data['sp_sponsor_id']);
  if (isset($data['mem_id'])) {
    $datasponsor = extractdata($data);
    $datasponsor['kodeaff'] = $weburl.$datasponsor['kodeaff'];
  }

  # Handle Password dulup
  if (isset($datalain['newpass']) && $datalain['newpass'] != '') {
    $settings = str_replace('[member_password]',$datalain['newpass'],$settings);
    $datamember['password'] = $datalain['newpass'];
  }

  # Handle Data Default
  $arrfield = array('nama','email','whatsapp','kodeaff');
  foreach ($arrfield as $arrfield) {      
    $settings = str_replace('[member_'.$arrfield.']',$datamember[$arrfield],$settings);
    $settings = str_replace('[sponsor_'.$arrfield.']',($datasponsor[$arrfield]??=''),$settings);
  }

  # Handle data lain
  $form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password')");

  foreach ($form as $form) {
    $valmember = $valsponsor = '';
    if (isset($datamember[$form['ff_field']])) {
      $valmember = $datamember[$form['ff_field']]??='';
    }
    if (isset($datasponsor[$form['ff_field']])) {
      $valsponsor = $datasponsor[$form['ff_field']]??='';
    }
    
    $settings = str_replace('[member_'.$form['ff_field'].']',$valmember,$settings);
    $settings = str_replace('[sponsor_'.$form['ff_field'].']',$valsponsor,$settings);
  }

  # Handle data tambahan lain
  if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
    foreach ($datalain as $key => $value) {
      $settings = str_replace('['.$key.']',$value,$settings);
    }
  }

  # Kirim Email
  if (isset($settings['judul_'.$event.'_member']) && !empty($settings['judul_'.$event.'_member'])) {
    if (isset($datamember['email'])) {      
      smtpmailer($datamember['email'],$settings['judul_'.$event.'_member'],$settings['isi_'.$event.'_member']);
    }
  }

  if (isset($settings['judul_'.$event.'_sponsor']) && !empty($settings['judul_'.$event.'_sponsor'])) {
    if (isset($datasponsor['email'])) {
      smtpmailer($datasponsor['email'],$settings['judul_'.$event.'_sponsor'],$settings['isi_'.$event.'_sponsor']);
    }
  }

  if (isset($settings['judul_'.$event.'_admin']) && !empty($settings['judul_'.$event.'_admin'])) {    
    if (isset($dataadmin['email'])) {
      smtpmailer($dataadmin['email'],$settings['judul_'.$event.'_admin'],$settings['isi_'.$event.'_admin']);
    }
  }

  # Kirim WhatsApp
  
  if (!empty($datamember['whatsapp']) && isset($settings['wa_'.$event.'_member']) && !empty($settings['wa_'.$event.'_member'])) {
    kirimwa($datamember['whatsapp'],$settings['wa_'.$event.'_member']);
  }

  if (!empty($datasponsor['whatsapp']) && isset($settings['wa_'.$event.'_sponsor']) && !empty($settings['wa_'.$event.'_sponsor'])) {
    kirimwa($datasponsor['whatsapp'],$settings['wa_'.$event.'_sponsor']);
  }  

  if (!empty($dataadmin['whatsapp']) && isset($settings['wa_'.$event.'_admin']) && !empty($settings['wa_'.$event.'_admin'])) {
    kirimwa($dataadmin['whatsapp'],$settings['wa_'.$event.'_admin']);
  }

  # Kirim data ke WAFUCB

  if (isset($settings['wafucb_'.$event]) && is_numeric($settings['wafucb_'.$event])) {
    if (isset($settings['wafucb_val_'.$event]) && $settings['wafucb_val_'.$event] == 1) {
      $validate = 1;
    } else {
      $validate = 0;
    }

    $nearray = array_map(function($key) {
        return 'sp' . $key;
    }, array_keys($datasponsor));

    $newsponsor = array_combine($nearray, array_values($datasponsor));

    $kirimdata = array_merge($newsponsor, $datamember);

    if (isset($datalain) && is_array($datalain) && count($datalain) > 0) {
      $kirimdata = array_merge($datalain,$kirimdata);
    }

    $data = array(
      'wafucb_key' => $settings['wafucb_key'],
      'wafucb_id' => $settings['wafucb_id'],
      'chan_id' => $settings['wafucb_'.$event], 
      'whatsapp' => $datamember['whatsapp'],
      'validate' => $validate,
      'data' => $kirimdata
    );
    
    $postfield = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://wafucb.my.id/api',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $postfield
    ));

    $return = curl_exec($curl);
    curl_close($curl);
  }

  # Kirim data ke Autoresponder 
  /* 
  if (isset($settings['form_action_'. $event]) && !empty($settings['form_action_'. $event])) {
    $form = '<form id="myForm" action="'.$settings['form_action_'. $event].'" method="post">';
    for ($i=1; $i <= 10 ; $i++) { 
      if (isset($settings['form_field_'. $event.$i]) && !empty($settings['form_field_'. $event.$i])) {
        $form .= '<input type="hidden" name="'.$settings['form_field_'. $event.$i].'" value="'.$settings['form_value_'. $event.$i].'">';
      }
    }
    
    $form .= '</form>
    <script>
    document.addEventListener(\'DOMContentLoaded\', function () {
        // Auto-submit form when the page is loaded
        document.getElementById(\'myForm\').submit();
    });
    </script>
    ';
    
    echo $form;
  }
  */
  if (isset($settings['form_action_'. $event]) && !empty($settings['form_action_'. $event])) {
  	for ($i=1; $i <= 10 ; $i++) { 
      if (isset($settings['form_field_'. $event.$i]) && !empty($settings['form_field_'. $event.$i])) {
        $post[$settings['form_field_'. $event.$i]] = $settings['form_value_'. $event.$i];
      }
    }

    if (isset($post) && count($post) > 0) {
      postData($settings['form_action_'. $event], $post);
    }
  }
}
?>