<?php 

function afi_get_kernel_conf_dist() {
  $afi_dist = afi_get_const_array_key('AFI_CLIENT_CONF','dist');
  $afi_distver = afi_get_const_array_key('AFI_CLIENT_CONF','distver');
  $conffile_kernel_dist = "urls/".$afi_dist."/".$afi_distver."/kernel.conf";
  afi_debug_var("$conffile_kernel_dist",$conffile_kernel_dist,6);
  return afi_get_conffile_and_override($conffile_kernel_dist);
}

function create_ipxe_kernel_string() {

  $afi_rhel_clones_regex="/centos|rhel|oracle|scientific|puias|springdale|rosa|stella/";

  # client conf opts
  $afi_dist = afi_get_const_array_key('AFI_CLIENT_CONF','dist');
  $afi_distver = afi_get_const_array_key('AFI_CLIENT_CONF','distver');
  $afi_distvershort = afi_get_const_array_key('AFI_CLIENT_CONF','distvershort');

  $afi_kernel_conf_dist = afi_get_kernel_conf_dist();

  # create kernel url
  $afi_kernel_string = $afi_kernel_conf_dist['kernel'];

  # ensure that installer uses same interface than iPXE
  $afi_kernel_string = $afi_kernel_string." BOOTIF=01-\${netX/mac}";

  # dist specific extra kernel parameters
  # no good magic FIXME
  $afi_dist_kernel_opts = "";
  if ( (preg_match($afi_rhel_clones_regex, $afi_dist)) &&  (preg_match("/^6\.[0-9]+$/", $afi_distver)) ) {
    $afi_dist_kernel_opts = "ramdisk_size=100000 ksdevice=bootif";
  }
#  # FIXME do not use fai - rather use preseed
#  if ( (preg_match("/ubuntu|debian/", $afi_dist)) ) {
#    $afi_dist_kernel_opts ="boot=live fetch=".afi_get_base_url_afi(TRUE)."/faiserver/UBUNTUPRECISE/base.squashfs ip=dhcp FAI_CONFIG_SRC=git+https://gitserver/fai.git FAI_ACTION=install FAI_FLAGS=verbose,sshd";
#  }

  $afi_kernel_string = $afi_kernel_string." ".$afi_dist_kernel_opts;

  # define provisioning file
  switch (true) {
    case (preg_match($afi_rhel_clones_regex, $afi_dist)):
      $afi_provisioning_file_post = "rhel.".$afi_distvershort.".php";
      break;
    default:
      $afi_provisioning_file_post = $afi_dist.".".$afi_distvershort.".php";
      break;
  }
  afi_debug_var("$afi_provisioning_file_post",$afi_provisioning_file_post,6);

  # provisioning parameter for boot
  switch (true) {
    case (preg_match($afi_rhel_clones_regex, $afi_dist)):
      $afi_provisioning_file_string = "ks=".afi_get_base_url_afi(TRUE)."/ks.cfg.".$afi_provisioning_file_post;
      break;
    case (preg_match("/fedora/", $afi_dist)):
      $afi_provisioning_file_string = "ks=".afi_get_base_url_afi(TRUE)."/ks.cfg.".$afi_provisioning_file_post;
      break;
    default:
      $afi_provisioning_file_string = "";
      break;
  }
  
  $client_profile_name = afi_get_client_profile_name();
  afi_debug_var("client_profile_name", $client_profile_name, 6);

  $afi_provisioning_file_string = $afi_provisioning_file_string."?afi_client_profile_name=".$client_profile_name;
  afi_debug_var("afi_provisioning_file_string", $afi_provisioning_file_string, 6);
    
  $afi_kernel_string = $afi_kernel_string." ".$afi_provisioning_file_string;

  if (afi_get_const_array_key('AFI_CLIENT_CONF','instsshd') == 1) {
    if ( (preg_match($afi_rhel_clones_regex, $afi_dist)) &&  (preg_match("/^6\.[0-9]+$/", $afi_distver)) ) {
      $instsshd_string = "sshd=1";
    } else {
      $instsshd_string = "inst.sshd";
    }
    $afi_kernel_string = $afi_kernel_string." ".$instsshd_string;
  }


  # ssl kernel parameter
  if (afi_get_const_array_key('AFI_CLIENT_CONF','noverifyssl') == 1) {
    #FIXME replace by call for noverifysslstring (distro dependent)
    $afi_kernel_string = $afi_kernel_string." noverifyssl";
  }

  # serial console kernel parameter
  if (afi_get_const_array_key('AFI_CLIENT_CONF','use_serial_console') == 1) {
    $serial_console_port_linux_kernel = afi_get_const_array_key('AFI_CLIENT_CONF','serial_console_port') - 1;
    $serial_console_port_speed = afi_get_const_array_key('AFI_CLIENT_CONF','serial_console_port_speed');
    $serial_console_string_linux_kernel = "console=tty0 console=ttyS".$serial_console_port_linux_kernel.",".$serial_console_port_speed."n8";

    # no good magic FIXME
    if (preg_match($afi_rhel_clones_regex, $afi_dist) && preg_match("/^6\.[0-9]+$/", $afi_distver)) {
      $serial_console_string_linux_kernel = "serial ".$serial_console_string_linux_kernel;
    }
  
    $afi_kernel_string = $afi_kernel_string." ".$serial_console_string_linux_kernel;
  }

  # create kernel parameter strings from host conf settings
  if (afi_get_const_array_key('AFI_CLIENT_CONF','ipv6disable') == 1) {
    #FIXME replace by call for ipv6disable-string (distro dependent)
    $afi_kernel_string = $afi_kernel_string." ipv6.disable=1";
  }

  # disable consistent device naming ?
  if (afi_get_const_array_key('AFI_CLIENT_CONF','disableconsistennetworkdevicenaming') == 1) {
    #FIXME replace by call for disableconsistennetworkdevicenaming-string (distro dependent)
    $afi_kernel_string = $afi_kernel_string." biosdevname=0 net.ifnames=0";
  }

  #special kernel boot options
  $afi_kernel_string = $afi_kernel_string." ".afi_get_const_array_key('AFI_CLIENT_CONF','kernelbootoptions');
  
  return $afi_kernel_string;
}

function create_ipxe_initrd_string() {
  # create initrd url
  $afi_kernel_conf_dist = afi_get_kernel_conf_dist();
  return $afi_kernel_conf_dist['initrd'];
}

?>
