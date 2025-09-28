<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $titlepage;?></title>
	<meta name="description" content="<?php echo $discpage;?>" />
	<link rel="shortcut icon" type="image/x-icon" href="<?= $weburl;?>img/<?= $favicon;?>" />
	<!-- Bootstrap Core CSS -->
  <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" /> 
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="<?php echo $weburl;?>theme/simple/style.css">
	<style type="text/css">
		.social-proof {
		  font-family: 'Open Sans', sans-serif;
		  position: fixed;
		  bottom: <?php echo $settings['jarakbwh'] ??= '80';?>px;
		  right: 10px;
		  z-index: 9999;
		  padding: 10px;
		}
		.social-proof-box { 
			background: <?php 
			$hex = $settings['bgsocialproof'] ??= '#000000';
			list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
			echo 'rgba('.$r.', '.$g.', '.$b.', 0.8)'; ?>;
			color: <?php echo $settings['txtsocialproof'] ??= '#ffffff';?>;			
		}
		.social-proof-box a { color: <?php echo $settings['txtsocialproof'] ??= '#ffffff';?>; }
		.box { 
			background: <?php 
			$hex = $settings['bgsponsor'] ??= '#000000';
			list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
			echo 'rgba('.$r.', '.$g.', '.$b.', 0.6)'; ?>;
			color: <?php echo $settings['txtsponsor'] ??= '#ffffff';?>;			
		}
		
		.box a { color: <?php echo $settings['txtsponsor'] ??= '#ffffff';?>; }
		
	</style>
</head>
<body>
	<div id="myDiv">
		<iframe id="myIframe" src="<?php echo $showpage??='';?>"></iframe>
	</div>	
	<?php		
	if (isset($settings['boxsocialproof']) && !empty($settings['boxsocialproof'])) {
		$newmember = db_select("SELECT * FROM `sa_member` ORDER BY `mem_tgldaftar` DESC LIMIT 0,10");
		$listproof = '';
		foreach ($newmember as $newmember) {
			$member = $settings['boxsocialproof'];			
			$memdata = extractdata($newmember);			
			foreach ($memdata as $key => $value) {				
				if (!empty($value)) {
					$member = str_replace('['.$key.']',addslashes($value),$member);
				} else {
					$member = str_replace('['.$key.']','',$member);
				}
			}
			$listproof .= "'".$member."',";
		}

		$listproof = substr($listproof, 0,-1);
		echo '
	<div class="social-proof">
	  <div class="social-proof-box">
	    <span class="social-proof-name"></span>	    
	  </div>
	</div>';
	}

	if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor']) && isset($datasponsor)) {
		$sponsor = extractdata($datasponsor);
		$isibox = $settings['boxsponsor'];
		foreach ($sponsor as $key => $value) {
			$isibox = str_replace('['.$key.']', ($value??=''), $isibox);
		}

		echo '<div class="box"><div id="textbox">'.$isibox.'</div></div>';
	}
	?>

	<script type="text/javascript">		
		var screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		document.getElementById("myIframe").style.height = (screenHeight) + "px";
		const names = [<?php echo ($listproof??='');?>];
		function getRandomName() {
		  const randomIndex = Math.floor(Math.random() * names.length);
		  return names[randomIndex];
		}

		function displaySocialProof() {
		  const box = document.querySelector('.social-proof-box');
		  const name = document.querySelector('.social-proof-name');
		  const randomName = getRandomName();
		  name.innerText = randomName;
		  box.style.display = 'inline-block';
		  setTimeout(function() {
		    box.style.display = 'none';
		    setTimeout(displaySocialProof, 2000);
		  }, 5000);
		}

		window.onload = displaySocialProof;
	</script>
</body>
</html>