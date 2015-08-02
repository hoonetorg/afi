<?php 

#### require_once libraries ####
require_once './lib/libdebug.php';
require_once './lib/libconf.php';

#### process ini file ####
$afi_ini = parse_ini_file("./afi.ini");
afi_set_const_array("AFI_INI_SETTINGS",parse_ini_file("./afi.ini"));

#### host config ####
#create host config 
afi_set_const_array('AFI_CLIENT_CONF',afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name()));

# check if host config worked
if (!afi_get_const_array_key('AFI_CLIENT_CONF','environment') || afi_get_const_array_key('AFI_CLIENT_CONF','environment') == "" ) {
  print "error loading host_conf\n";
  exit(1);
}

?>
