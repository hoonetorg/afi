<?php 

function print_var_recursive($item, $key)
{
    print "\n# DEBUG [".(string)$key."] -> ".(string)$item;
}


function afi_debug_out($msg,$lvl=1) {
  if (is_object($msg)) return;
  $debug = afi_get_const_array_key('AFI_INI_SETTINGS','afi_app_debug');
  if (!isset($debug)) $debug = 0;
  if ($debug <= $lvl) return;
  print "\n# DEBUG $lvl: ".$msg." \n";
}

function afi_debug_var($varname, $var,$lvl=1) {
  if (is_object($var)) return;
  $debug = afi_get_const_array_key('AFI_INI_SETTINGS','afi_app_debug');
  if (!isset($debug)) $debug = 0;
  if ($debug <= $lvl) return;

  if (!isset($var)) {
    print "\n# DEBUG $lvl ${varname}: *NotSet* ";
    return;
  }

  if (is_array($var)){
    print "\n# DEBUG $lvl ${varname}: ";
    array_walk_recursive($var,'print_var_recursive');
    
  } else {
    print "\n# DEBUG $lvl ${varname}: ".(string)$var." ";
  }
}

?>
