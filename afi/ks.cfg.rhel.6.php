<?php

require_once './inc/conf.php';

#specific require_onces
require_once './lib/libks.php';
require_once './lib/libpart.php';

#FIXME
$host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name()); #FIXME

#defines
# localization settings 
define("AFI_LANG", $host_conf['lang']);
define("AFI_KEYB", $host_conf['keyb']);
define("AFI_TIMEZONE", $host_conf['timezone']);

# ssl setting
$noverifyssl_string_kickstart="";
if ($host_conf['noverifyssl'] == 1) {
  $noverifyssl_string_kickstart="--noverifyssl";
}
define("AFI_NOVERIFY_SSL_STRING_KICKSTART", $noverifyssl_string_kickstart);


# intial pw hashes
define("AFI_INITIAL_PW_HASH", $host_conf['initial_pw_hash']);
define("AFI_INITIAL_PW_PBKDF2", $host_conf['initial_pw_pbkdf2']);

#FIXME partitioning files
define("AFI_INITIAL_PW", $host_conf['initial_pw']);

# bootloader settings
define("AFI_BOOTLOADER_LOCATION", $host_conf['bootloader_location']);
define("AFI_QUIET_BOOT", $host_conf['quiet_boot']);
define("AFI_RHGB_BOOT", $host_conf['rhgb_boot']);


#partitioning settings
#FIXME partitioning files
define("AFI_PART_INITIAL", $host_conf['part_initial']);


#FIXME partitioning# define("AFI_INITIAL_PW", $host_conf['initial_pw']);
#FIXME partitioning# define("AFI_PART_INITIAL", $host_conf['part_initial']);



$afi_pre_dir = "/tmp/afi_pre";
$afi_post_dir = "/tmp/afi_post";

if ( isset($host_conf['partition_class'])) {
  $partition_class=$host_conf['partition_class'];
  afi_require_file_and_override ("part/${partition_class}.main",false);
} else {
  print "error loading partition_class\n";
  exit(1);
}
if (! function_exists('afi_partition_main')) {
  print "error loading function afi_partition_main()\n";
  exit(1);
}

$afi_install_disks_comma=implode(",",$host_conf['install_disks']);
$afi_install_disks_space=implode(" ",$host_conf['install_disks']);
foreach ($host_conf['install_disks'] as $afi_install_disk_number => $afi_install_disk) {
 $afi_install_disks[$afi_install_disk_number]['disk']=$afi_install_disk;
 $afi_install_disks[$afi_install_disk_number]['partprefix']=afi_part_get_partprefix($afi_install_disk);
}

?>

# Perform kickstart installation in text mode
text
install
skipx

# Agree to EULA
#eula --agreed

logging --level=debug

reboot

<?php
print "# Partitioning Information and bootloader\n";

# Boot Loader Configuration and  Boot Loader Password
print "bootloader --append=\"console=tty0 net.ifnames=0\" --timeout=15 --location=".AFI_BOOTLOADER_LOCATION." --iscrypted --password=".AFI_INITIAL_PW_PBKDF2." --driveorder=$afi_install_disks_comma\n";

afi_partition_main($afi_install_disks, $afi_install_disks_comma, $afi_install_disks_space);
print "\n";

print "#network\n";
#print "%include ".$afi_pre_dir."/afi_pre_network\n\n";
#or
#print "network  --bootproto=dhcp\n";
#todo
print "\n";


print "# url and repos\n";

create_repo_config_ks();
print "\n";



print "# simple settings\n";

print "#localization\n";
print "lang ".AFI_LANG."\n";
print "keyboard ".AFI_KEYB."\n";

#sshd during installation (needs sshd kernel parameter for anaconda)
print "sshpw --username=root ".AFI_INITIAL_PW_HASH." --iscrypted\n";
# Authorization Configuration
print "auth --enableshadow --passalgo=sha512\n";
# initial passwd
print "rootpw --iscrypted ".AFI_INITIAL_PW_HASH."\n";

# Firewall/Security Configuration
##shoul be the goal
#print "firewall --enabled --service=ssh\n";
#print "selinux --enforcing\n";
print "firewall --disabled\n";
print "selinux --permissive\n";

# Time Zone Configuration
print "timezone --utc ".AFI_TIMEZONE."\n";

# System services
print "services --disabled=\"kdump\" --enabled=\"sshd,rsyslog,chronyd\"\n";

# disable post configuration after first boot
print "firstboot --disable\n";
print "\n";

print "#package configuration\n";
create_package_config_ks();
print "\n";



?>

# Pre
%pre --logfile=/tmp/afi_pre_full.log
#!/bin/bash

main() {
  echo "Kickstart-installed Linux - PRE-section (`/usr/bin/date`)"

  #Define ARCH
  ARCH="`uname -m`"
  case $ARCH in
    i[3456]86)
      ARCH="i386"
      ;;
    sun4*)
      ARCH="sun4"
      ;;
  esac
<?php

  if (is_array($host_conf['pre_classes'])) {
    foreach ($host_conf['pre_classes'] as $preclassvalue) {
      afi_require_file_and_override ("pre/${preclassvalue}.pre"); 
    }
  }


  afi_require_file_and_override ("part/${partition_class}.pre",false);
  if (function_exists('afi_partition_pre')) {
    afi_partition_pre($afi_install_disks);
    print "\n";
  }
  #bring back eth* network interface names
  print "rm -f /etc/udev/rules.d/70*\n"; 
  print "ln -s /dev/null /etc/udev/rules.d/80-net-setup-link.rules\n"; 
  print "ln -s /dev/null /etc/udev/rules.d/80-net-name-slot.rules\n"; 
?>
}

<?php
# Define some vars
print "AFI_PRE_DIR=\"$afi_pre_dir\"\n";
print "AFI_POST_DIR=\"$afi_post_dir\"\n";
?>

mkdir -p $AFI_PRE_DIR
mkdir -p $AFI_POST_DIR
cp $0 $AFI_PRE_DIR/afi_pre.script
chmod -x $AFI_PRE_DIR/afi_pre.script

main 2>&1|tee "$AFI_PRE_DIR/afi_pre.log"
set 2>&1|tee "$AFI_PRE_DIR/afi_pre_var.log"
export 2>&1|tee "$AFI_PRE_DIR/afi_pre_export.log"
#sleep 3300

%end

<?php
#POST NOCHROOT 1st
print "%post --nochroot --log=".$afi_post_dir."/afi_post_full_nochroot_first.log\n";
?>
echo "Kickstart-installed Linux - POST -section (nochroot-first) (`/usr/bin/date`)"

main() {
  echo "Kickstart-installed Linux - POST-section (`/bin/date`)"

  #Define ARCH
  ARCH="`uname -m`"
  case $ARCH in
    i[3456]86)
      ARCH="i386"
      ;;
    sun4*)
      ARCH="sun4"
      ;;
  esac

<?php
  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      afi_require_file_and_override ("post/${postclassvalue}.nochroot"); 
    }
  }
?>

}
<?php
print "AFI_POST_DIR=\"$afi_post_dir\"\n";
?>

main 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first.log"
set 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first_var.log"
export 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first_export.log"

%end

<?php
print "%post --interpreter /bin/bash --log=/tmp/afi_post_full.log\n";
?>

main() {
  echo "Kickstart-installed Linux - POST-section (`/bin/date`)"

  #Define ARCH
  ARCH="`uname -m`"
  case $ARCH in
    i[3456]86)
      ARCH="i386"
      ;;
    sun4*)
      ARCH="sun4"
      ;;
  esac


<?php 

  #disable rhgb quiet
  if (AFI_QUIET_BOOT == 0){
    print "sed -i -r 's/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*[^[:alnum:]])quiet([^[:alnum:]])/\\1\\2/g' /etc/default/grub\n";
  }
  if (AFI_RHGB_BOOT == 0){
    print "sed -i -r 's/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*[^[:alnum:]])rhgb([^[:alnum:]])/\\1\\2/g' /etc/default/grub\n";
  }

  #reorder console entries
  print "sed -i -r \"s/^[[:blank:]]*(GRUB_CMDLINE_LINUX[[:blank:]]*=[[:blank:]]*[\\\"'])(.*[^[:alnum:]])(console=tty[0-9]*)([^[:alnum:]])/\\1 \\3 \\2 \\4/g\" /etc/default/grub\n";
  print "sed -i -r \"s/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*)([^[:alnum:]]console=ttyS[0-9]*,[[:alnum:]]+[^[:alnum:]])(.*)([\\\"'])/\\1 \\3 \\2 \\4/g\" /etc/default/grub\n";


  #generate new grub config
  print "grub2-mkconfig -o /boot/grub2/grub.cfg\n";

  #bring back eth* network interface names
  print "rm -f /etc/udev/rules.d/70*\n"; 
  print "ln -s /dev/null /etc/udev/rules.d/80-net-setup-link.rules\n"; 
  print "ln -s /dev/null /etc/udev/rules.d/80-net-name-slot.rules\n"; 

  #remove eth config files - nm does dhcp per default on all interfaces
  print "rm -f /etc/sysconfig/network-scripts/ifcfg-eth*\n";

  #firstreboot-scripts
  print "mkdir -p /etc/rc.firstreboot\n";
  print "chmod 700 /etc/rc.firstreboot\n";
 
  #copy/create  scripts 
  afi_require_file_and_override ("part/${partition_class}.firstreboot",false);
  if (function_exists('afi_partition_firstreboot')) {
    afi_partition_firstreboot($afi_install_disks);
    print "\n";
  }

  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      afi_require_file_and_override ("post/${postclassvalue}.firstreboot"); 
    }
  }

?>
  mkdir -p /var/log/install/firstreboot

  #firstreboot
  cat /etc/rc.local >/etc/rc.local.orig

  cat >/etc/rc.local <<-EOF
# this prevents rc.local from running twice,
# /var/lock/subsys/local will be removed on system shutdown/reboot
touch /var/lock/subsys/local

main () {
  echo "This script is executed after first reboot and destroys itself then"
  for SCRIPT in \`find /etc/rc.firstreboot -type f|sort\`; do
    echo "Executing: \$SCRIPT"
    \$SCRIPT 2>&1
    echo "Finished: \$SCRIPT"
  done
}
main 2>&1 |tee /var/log/install/firstreboot/firstreboot.log

cat /etc/rc.local > /var/log/install/firstreboot/rc.local
mv /etc/rc.firstreboot /var/log/install/firstreboot
find /var/log/install/firstreboot -type f -exec chmod -x {} \;
cat /etc/rc.local.orig >/etc/rc.local
rm /etc/rc.local.orig
sleep 10
exit 0
EOF

<?php 
  afi_require_file_and_override ("part/${partition_class}.post",false);
  if (function_exists('afi_partition_post')) {
    afi_partition_post($afi_install_disks);
    print "\n";
  }

  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      afi_require_file_and_override ("post/${postclassvalue}.chroot"); 
    }
  }

  #probably needed for some packages
  if (afi_get_const_array_key('AFI_CLIENT_CONF','dist') == "oracle") {
    print "for KERNEL in /boot/vmlinuz-2.6.32*; do /sbin/grubby --remove-kernel=\"\$KERNEL\";done\n";
    #this is not working because a few packages need the kernel package
    #print "yum -y remove kernel\n";
    print "\n";
  }

  # unset host install
  if (afi_get_const_array_key('AFI_CLIENT_CONF','unset_install_when_successfully_installed') === "1") {
    print "echo \"#unsetting host install to prevent install loop\"\n";
    print afi_get_unset_url_command()."\n";
  }

?>

}

AFI_POST_LOG_DIR="/var/log/install/afi_post"

mkdir -p "$AFI_POST_LOG_DIR"
cp $0 "$AFI_POST_LOG_DIR/afi_post.script"
chmod -x "$AFI_POST_LOG_DIR/afi_post.script"


main 2>&1|tee "$AFI_POST_LOG_DIR/afi_post_nochroot_second.log"
set 2>&1|tee "$AFI_POST_LOG_DIR/afi_post_nochroot_second_var.log"
export 2>&1|tee "$AFI_POST_LOG_DIR/afi_post_nochroot_second_export.log"
#sleep 3500

%end



<?php
#POST NOCHROOT 2nd
print "%post --nochroot --log=".$afi_post_dir."/afi_post_full_nochroot_second.log\n";
?>

main() {
  echo "Kickstart-installed Linux - POST -section (nochroot-2nd) (`/usr/bin/date`)"

  #Define ARCH
  ARCH="`uname -m`"
  case $ARCH in
    i[3456]86)
      ARCH="i386"
      ;;
    sun4*)
      ARCH="sun4"
      ;;
  esac

<?php 
  afi_require_file_and_override ("part/${partition_class}.post_nochroot",false);
  if (function_exists('afi_partition_post_nochroot')) {
    afi_partition_post_nochroot($afi_install_disks);
    print "\n";
  }
?>

}

<?php
# Define some vars
print "AFI_PRE_DIR=\"".$afi_pre_dir."\"\n";
print "AFI_POST_DIR=\"$afi_post_dir\"\n";
?>

AFI_CHROOT_DIR="/mnt/sysimage"
AFI_PRE_LOG_DIR="$AFI_CHROOT_DIR/var/log/install/`basename "$AFI_PRE_DIR"`"
AFI_POST_LOG_DIR="$AFI_CHROOT_DIR/var/log/install/afi_post"

mkdir -p $AFI_PRE_LOG_DIR
mkdir -p $AFI_POST_LOG_DIR

main 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_second.log"
set 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_second_var.log"
export 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_second_export.log"

cp -a /tmp/ks.cfg $AFI_PRE_DIR
mv /tmp/afi_pre_full.log $AFI_PRE_DIR
mv $AFI_PRE_DIR/* $AFI_PRE_LOG_DIR
chmod -R -x $AFI_PRE_LOG_DIR/*

cp -a  /tmp/afi_post* $AFI_POST_LOG_DIR
[ -f "$AFI_CHROOT_DIR/tmp/afi_post_full.log" ] && mv "$AFI_CHROOT_DIR/tmp/afi_post_full.log" $AFI_POST_LOG_DIR
cp -a $AFI_POST_DIR/afi_post* $AFI_POST_LOG_DIR
chmod -R -x $AFI_POST_LOG_DIR/*

%end
