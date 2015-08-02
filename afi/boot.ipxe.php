<?php 
require_once './inc/conf.php';
require_once './lib/libipxe.php';

print "#!ipxe\n";

#if (afi_get_const_array_key('AFI_CLIENT_CONF','install') == 1) {
if (afi_get_host_install()) {
  $afi_client_profile_name = afi_get_client_profile_name();
  $afi_ipxe_kernel_string = create_ipxe_kernel_string();
  $afi_ipxe_initrd_string = create_ipxe_initrd_string();

  #debug output for iPXE
  print "echo 'Client profile name: \"".$afi_client_profile_name."\"'\n";

  print "echo 'kernel ".$afi_ipxe_kernel_string."'\n";
  print "echo 'initrd ".$afi_ipxe_initrd_string."'\n";

  #kernel + options and initrd
  print "kernel ".$afi_ipxe_kernel_string."\n";
  print "initrd ".$afi_ipxe_initrd_string."\n";

  #boot kernel
  print "boot\n";

} else {
  #take next device in BIOS boot order
  print "exit\n";
}
?>
