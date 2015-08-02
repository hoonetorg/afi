<?php 

require_once "./lib/libsecure.php";

function afi_get_unset_url_command() {
  $client_profile_name = afi_get_client_profile_name();
  afi_debug_var("client_profile_name", $client_profile_name, 6);
  return "curl -s -o - ".afi_get_base_url_afi(TRUE)."/unset_host_install.php?afi_client_profile_name=".$client_profile_name;
}

function afi_get_package_config_ks ($conffile) {
  $afi_dist = afi_get_const_array_key('AFI_CLIENT_CONF','dist');
  $afi_distvershort = afi_get_const_array_key('AFI_CLIENT_CONF','distvershort');
  $conffile_package_dist = "packages/".$afi_dist."/".$afi_distvershort."/".$conffile;
  return afi_get_conffile_and_override($conffile_package_dist);
}

function create_package_config_ks () {
  $afi_package_classes = afi_get_const_array_key('AFI_CLIENT_CONF','package_classes');

  print "%packages --ignoremissing\n";
  
  $afi_package_conf_dist = afi_get_package_config_ks("packages.main");
  print $afi_package_conf_dist['packages']."\n";
  print "\n";

  if (is_array($afi_package_classes)) {
    foreach ($afi_package_classes as $packageclassvalue) {
      $afi_package_conf_class = afi_get_package_config_ks($packageclassvalue.".main");
      print $afi_package_conf_class['packages']."\n";
      print "\n";
    }
  }
  
  print "%end\n";
  print "\n";
}

function afi_get_url_config_ks ($conffile) {
  $afi_dist = afi_get_const_array_key('AFI_CLIENT_CONF','dist');
  $afi_distver = afi_get_const_array_key('AFI_CLIENT_CONF','distver');
  $conffile_url_dist = "urls/".$afi_dist."/".$afi_distver."/".$conffile;
  return afi_get_conffile_and_override($conffile_url_dist);
}

function create_repo_config_ks () {

  $afi_repo_classes = afi_get_const_array_key('AFI_CLIENT_CONF','repo_classes');

  if (afi_get_const_array_key('AFI_CLIENT_CONF','noverifyssl') == 1) {
    $afi_noverifyssl_string_kickstart = "--noverifyssl";
  } else {
    $afi_noverifyssl_string_kickstart = "";
  }

  $afi_url_conf_dist = afi_get_url_config_ks("url.conf");
  $afi_url_proxy_string="";
  if (isset($afi_url_conf_dist['proxy']) && $afi_url_conf_dist['proxy'] != "" ) {
    $afi_url_proxy_string = "--proxy=".$afi_url_conf_dist['proxy'];
  }
  print "url --url=".$afi_url_conf_dist['url']." ".$afi_url_proxy_string." ".$afi_noverifyssl_string_kickstart."\n";
  print "\n";

  $afi_repo_conf_dist = afi_get_url_config_ks("repo.conf");

  foreach ($afi_repo_conf_dist as $key => $value) {
    afi_debug_var("afi_repo_conf_dist key",$key,6);
    afi_debug_var("afi_repo_conf_dist value",$value,6);
    $afi_repo_proxy_string="";
    if (isset($value['proxy']) && $value['proxy'] != "" ) {
      $afi_repo_proxy_string = "--proxy=".$value['proxy'];
    }
    $afi_repo_includepkgs_string="";
    if (isset($value['includepkgs']) && $value['includepkgs'] != "" ) {
      $afi_repo_includepkgs_string = "--includepkgs=".$value['includepkgs'];
    }
    print "repo --name=".$key." --baseurl=".$value['url']." ".$afi_repo_proxy_string." ".$afi_repo_includepkgs_string." ".$afi_noverifyssl_string_kickstart."\n";
  }  


  if (is_array($afi_repo_classes)) {
    foreach ($afi_repo_classes as $repoclassvalue) {
        $afi_repo_conf_class = afi_get_url_config_ks("/additional/".$repoclassvalue.".conf");
        foreach ($afi_repo_conf_class as $key => $value) {
          afi_debug_var("afi_repo_conf_class key",$key,6);
          afi_debug_var("afi_repo_conf_class value",$value,6);
          $afi_repo_proxy_string="";
          if (isset($value['proxy']) && $value['proxy'] != "" ) {
            $afi_repo_proxy_string = "--proxy=".$value['proxy'];
          }
          $afi_repo_includepkgs_string="";
          if (isset($value['includepkgs']) && $value['includepkgs'] != "" ) {
            $afi_repo_includepkgs_string = "--includepkgs=".$value['includepkgs'];
          }
          print "repo --name=".$key." --baseurl=".$value['url']." ".$afi_repo_proxy_string." ".$afi_repo_includepkgs_string." ".$afi_noverifyssl_string_kickstart."\n";
        }
    }
  }
}
?>
