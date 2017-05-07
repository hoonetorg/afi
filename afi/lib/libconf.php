<?php 

require_once "./lib/libsecure.php";

function afi_set_const_array($aficonstant, $afiarray) {
  define($aficonstant,serialize($afiarray));
}
function afi_get_const_array_key($aficonstarray, $aficonstarraykey) {
  $afitmp = unserialize(constant($aficonstarray));
  return $afitmp[$aficonstarraykey];
}
 
function afi_get_base_url_origin($use_forwarded_host=false) {
  # protocol
  $afi_ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
  $afi_ssl = ( (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || $afi_ssl) ? true:false;
  $afi_sp = strtolower($_SERVER['SERVER_PROTOCOL']);
  $afi_protocol = substr($afi_sp, 0, strpos($afi_sp, '/')) . (($afi_ssl) ? 's' : '');
  if ( ! preg_match("/\A[a-z]+\z/i",$afi_protocol) ) {
    afi_debug_var("afi_get_base_url_origin: afi_protocol is not a string", $afi_protocol ,5);
    return FALSE ;
  }

  # port
  $afi_port = $_SERVER['SERVER_PORT'];
  $afi_port = (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) ? $_SERVER['HTTP_X_FORWARDED_PORT'] : $afi_port;
  if ( ! filter_var($afi_port, FILTER_VALIDATE_INT) ) {
    afi_debug_var("afi_get_base_url_origin: afi_port is not an int", $afi_port ,5);
    return FALSE ;
  }
  $afi_port = ((!$afi_ssl && $afi_port=='80') || ($afi_ssl && $afi_port=='443')) ? '' : ':'.$afi_port;
  afi_debug_var("afi_get_base_url_origin: afi_port", $afi_port ,5);

  # host
  $afi_host = ($use_forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
  if ( ( ! filter_var('http://'.$afi_host, FILTER_VALIDATE_URL) ) || ( ! afi_is_valid_domain_name($afi_host) ) ) {
    afi_debug_var("afi_get_base_url_origin: afi_host (part 1) is not a valid hostname", $afi_host ,5);
    return FALSE ;
  }
  if ( ( ! filter_var('http://'.$_SERVER['SERVER_NAME'], FILTER_VALIDATE_URL) ) || ( ! afi_is_valid_domain_name($_SERVER['SERVER_NAME']) ) ) {
    afi_debug_var("afi_get_base_url_origin: afi_host (part 2,_SERVER['SERVER_NAME']) is not a valid hostname", $_SERVER['SERVER_NAME'] ,5);
    return FALSE ;
  }
  $afi_host = isset($afi_host) ? $afi_host : $_SERVER['SERVER_NAME'] . $afi_port;
  afi_debug_var("afi_get_base_url_origin: afi_host", $afi_host ,5);

  return $afi_protocol . '://' . $afi_host;
}

function afi_get_base_url_afi($use_forwarded_host=false){

  $afi_dyn_baseurl_afi    = afi_get_base_url_origin($use_forwarded_host) . dirname($_SERVER['SCRIPT_NAME']);

  if ( afi_get_const_array_key('AFI_INI_SETTINGS','afi_trust_global_server_variable') ) {
    afi_debug_var("afi_base_url_afi defined from afi_get_base_url_afi: ",$afi_dyn_baseurl_afi,5);
    return $afi_dyn_baseurl_afi;
  }

  $afi_valid_baseurls_afi = afi_get_const_array_key('AFI_INI_SETTINGS','afi_valid_baseurls_afi');
  if ( in_array($afi_dyn_baseurl_afi ,$afi_valid_baseurls_afi) ) {
    afi_debug_var("afi_base_url_afi defined from afi_get_base_url_afi: ",$afi_dyn_baseurl_afi,5);
    return $afi_dyn_baseurl_afi;
  }

  afi_debug_var("afi_base_url_afi defined from afi_valid_baseurls_afi[0]: ",$afi_valid_baseurls_afi[0],5);
  return $afi_valid_baseurls_afi[0];

}


function afi_get_client_profile_name() {

  #!do not fix input ! filter input
  #$afi_client_profile_name = filter_input(INPUT_GET, 'afi_client_profile_name', FILTER_SANITIZE_URL);

  #or manually process $_GET
  if (isset($_GET['afi_client_profile_name'])) {
    if ( $_GET['afi_client_profile_name'] == "" ) {
      return FALSE ; 
    }
    #don't fix input    
    #$afi_client_profile_name = filter_var($_GET['afi_client_profile_name'], FILTER_SANITIZE_STRING);
    $afi_client_profile_name = $_GET['afi_client_profile_name'];
    afi_debug_var("afi_client_profile_name gotten from _GET ", $afi_client_profile_name ,5);
  } else {
    #get raw host IP
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $afi_host_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $afi_host_ip = $_SERVER['REMOTE_ADDR'];
    }
    #or
    #$afi_host_ip = (getenv ( "HTTP_X_FORWARDED_FOR" )) ? getenv ( "HTTP_X_FORWARDED_FOR" ) : getenv ( "REMOTE_ADDR" );

    afi_debug_var("afi_host_ip before flattening ", $afi_host_ip ,5);

    $afi_host_ip = afi_flatten_data($afi_host_ip);

    afi_debug_var("afi_host_ip after flattening ", $afi_host_ip ,5);

    # validate if afi_host_ip is a valid IP
    if ( ! filter_var($afi_host_ip, FILTER_VALIDATE_IP) ) {
      afi_debug_var("afi_host_ip not valid IP ", $afi_host_ip ,5);
      return FALSE ; 
    }

    #broken
    #if ( ! checkdnsrr($afi_host_ip, 'PTR') ) {
    #  afi_debug_var("afi_host_ip not resolveable to PTR", $afi_host_ip ,5);
    #  return FALSE ; 
    #}
    $afi_client_profile_name = gethostbyaddr ($afi_host_ip); 
  }  
  afi_debug_var("afi_client_profile_name before flattening ", $afi_client_profile_name ,5);
  $afi_client_profile_name = afi_flatten_data($afi_client_profile_name);
  afi_debug_var("afi_client_profile_name after flattening ", $afi_client_profile_name ,5);

  # validate if afi_client_profile_name is a valid hostname
  if ( ( ! filter_var('http://'.$afi_client_profile_name, FILTER_VALIDATE_URL) ) || ( ! afi_is_valid_domain_name($afi_client_profile_name) ) ) {
    afi_debug_var("afi_client_profile_name is not a valid url or not a valid hostname  ", $afi_client_profile_name ,5);
    return FALSE ; 
  }
  return $afi_client_profile_name;
}

function afi_require_file_and_override ($requirefile, $have_both=true) {
  $afi_conf_dir = afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir');
  $afi_environment = afi_get_const_array_key('AFI_CLIENT_CONF','environment');
  $requirefile_override = $afi_conf_dir."/overrides/".$afi_environment."/".$requirefile;

  afi_debug_var("requirefile",$requirefile,6);
  afi_debug_var("requirefile_override",$requirefile_override,6);

  if (file_exists($requirefile)) {
    require_once $requirefile;
    if ( $have_both === false ) { 
      return 0;
    }
  }

  if (file_exists($requirefile_override)) {
    require_once $requirefile_override;
  }

}

function afi_get_conffile_and_override ($conffile) {
  $afi_conf_dir = afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir');
  $afi_environment = afi_get_const_array_key('AFI_CLIENT_CONF','environment');
  $conffile_override = $afi_conf_dir."/overrides/".$afi_environment."/".$conffile;

  $conf = array();
  if (file_exists($conffile)) {
    $conf = parse_ini_file($conffile);
  }

  $conf_override = array();
  if (file_exists($conffile_override)) {
    $conf_override = parse_ini_file($conffile_override);
  }

  afi_debug_var("conffile",$conffile,6);
  afi_debug_var("conffile_override",$conffile_override,6);
  return array_replace_recursive( (array)$conf, (array)$conf_override );
}

function afi_get_host_install() {

  $afi_conf_dir = afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir');
  $hostname = afi_get_client_profile_name();
  $afi_environment = afi_get_const_array_key('AFI_CLIENT_CONF','environment');

  $installsymlink = $afi_conf_dir."/hosts/".$afi_environment."/install/".$hostname.".conf";
  $testfile = $afi_conf_dir."/hosts/".$afi_environment."/install/testfile";
  $testlink = $afi_conf_dir."/hosts/".$afi_environment."/install/testlink";

  if (! is_writeable(dirname($installsymlink))) { return FALSE; }
  if (file_exists($installsymlink)) {
    if (is_link($installsymlink)) {
      return TRUE;
    }
  }

  return FALSE;

}

function afi_unset_host_install() {

  $afi_conf_dir = afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir');
  $hostname = afi_get_client_profile_name();
  $afi_environment = afi_get_const_array_key('AFI_CLIENT_CONF','environment');

  $installsymlink = $afi_conf_dir."/hosts/".$afi_environment."/install/".$hostname.".conf";

  if (file_exists($installsymlink)) {
    if (is_link($installsymlink)) {
      unlink($installsymlink);
    }
  }
      $afi_unset_url = afi_get_base_url_afi(TRUE)."/unset_host_install.php";
}


function afi_get_host_config($afi_conf_dir, $hostname) {
  //load global conf
  if (file_exists("./defaults/defaults.conf")) {
    $default_conf_global = parse_ini_file("./defaults/defaults.conf");
  }
 
  //find host config file
  $hostconffile = glob("${afi_conf_dir}/hosts/*/${hostname}.conf");

  afi_debug_var("hostconffile", $hostconffile ,5);

  if ( !$hostconffile || !is_array($hostconffile) || count($hostconffile) != 1 ) {
    print "#none or more than one host config file found for ${hostname}\n";
    print_r($hostconffile);
    return NULL;
  }

  //determine environment
  $environment=basename(dirname($hostconffile[0]));

  if (!isset($environment) || empty($environment) || $environment == "" ) {
    print "#no environment set: ".$environment."\n";
    return NULL;
  }

  //load environment specific conf
  if (!file_exists($afi_conf_dir."/overrides/".$environment."/defaults/defaults.conf")) {
    $default_conf_environment = array();
  } else {
    $default_conf_environment = parse_ini_file($afi_conf_dir."/overrides/".$environment."/defaults/defaults.conf");
  }

  if (!file_exists($hostconffile[0])) {
    print "#no hostconf file found: ".$hostconffile[0]."\n";
    return NULL;
  }
  $host_conf = parse_ini_file("$hostconffile[0]");

  $host_conf['environment'] = $environment;

  if (strstr($host_conf['distver'], '.')) {
    $host_conf['distvershort'] = strstr($host_conf['distver'], '.', true);
  } else {
    $host_conf['distvershort'] = $host_conf['distver'];
  }

  afi_debug_var("host_conf", $host_conf,8);

  //debug
  afi_debug_var("default_conf_global", $default_conf_global,6);
  afi_debug_var("default_conf_environment", $default_conf_environment,6);
  afi_debug_var("host_conf", $host_conf,6);
  afi_debug_var("environment", $environment, 6);
  afi_debug_out(PHP_EOL,6);

  return array_replace_recursive( (array)$default_conf_global, (array)$default_conf_environment, (array)$host_conf);

}
?>
