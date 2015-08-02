<?php 
function afi_flatten_data ($afi_data){
  $afi_data = trim($afi_data);
  $afi_data = stripslashes($afi_data);
  $afi_data = strip_tags($afi_data);
  $afi_data = htmlspecialchars($afi_data);
  $afi_data = htmlentities($afi_data);
  return $afi_data;
}

function afi_secure_print($afi_data) {
   print afi_flatten_data($afi_data);
}

function afi_is_valid_domain_name($afi_domain_name) {
  if ( preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $afi_domain_name)  === 1 
      && preg_match("/^.{1,253}$/", $afi_domain_name) === 1
      && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $afi_domain_name) === 1)  {
    return TRUE;
  }

  return FALSE;
}

?>
