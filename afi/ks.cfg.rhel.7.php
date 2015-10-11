<?php

require_once './inc/conf.php';

#specific require_once's
require_once './lib/libks.php';
require_once './lib/libpart.php';

$host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());

$afi_pre_dir = "/tmp/afi_pre";
$afi_post_dir = "/tmp/afi_post";

print "# Partitioning Information and bootloader\n";
afi_part_bootloader();
print "\n";
afi_part_main();
print "\n";
?>

# Perform kickstart installation in text mode
text
install
skipx

# Agree to EULA
eula --agreed

# enable debug logging
logging --level=debug

# reboot after installation
reboot

<?php
print "#network\n";
#FIXME
#print "%include ".$afi_pre_dir."/afi_pre_network\n\n";
#or
#print "network  --bootproto=dhcp\n";
print "\n";

print "# url and repos\n";
create_repo_config_ks();
print "\n";

print "# simple settings\n";

print "#localization\n";
print "lang ".$host_conf['lang']."\n";
print "keyboard --vckeymap=".$host_conf['keyb']." --xlayouts='".$host_conf['keyb']."'\n";
print "\n";

print "# Time Zone Configuration\n";
print "timezone --utc ".$host_conf['timezone']."\n";
print "\n";

print "# Authconf\n";
print "auth --enableshadow --passalgo=sha512\n";
print "# setting rootpw is required for kickstart - disable it via a post script, if needed\n";
print "rootpw --iscrypted ".$host_conf['initial_pw_hash']."\n";
print "\n";

if ( $host_conf['instsshd'] == 1) {
  print "# Enable sshd during installation (needs sshd kernel parameter for anaconda and initial_pw_hash)\n";
  print "sshpw --username=root ".$host_conf['initial_pw_hash']." --iscrypted\n";
  print "\n";
}

print "# Firewall configuration\n";
if ( $host_conf['firewall'] == 1) {
  $firewall="--enabled";
} else {
  $firewall="--disabled";
}
$firewallservice="";
if ( $host_conf['firewallservice'] != "" ) {
  $firewallservice="--service=".$host_conf['firewallservice'];
}
$firewallport="";
if ( $host_conf['firewallport'] != "" ) {
  $firewallport="--port=".$host_conf['firewallport'];
}
print "firewall ".$firewall." ".$firewallservice." ".$firewallport."\n";
print "\n";

print "# Selinux configuration\n";
$selinux=$host_conf['selinux'];
print "selinux --".$selinux."\n";
print "\n";

print "# System services\n";
$servicesdisabled="";
if ( $host_conf['servicesdisabled'] != "" ) {
  $servicesdisabled="--disabled=".$host_conf['servicesdisabled'];
}
$servicesenabled="";
if ( $host_conf['servicesenabled'] != "" ) {
  $servicesenabled="--enabled=".$host_conf['servicesenabled'];
}
print "services ".$servicesdisabled." ".$servicesenabled."\n";
print "\n";

#FIXME end }

print "# disable post configuration after first boot\n";
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
  print "# Trying to load preclasses\n";
  if (is_array($host_conf['pre_classes'])) {
    foreach ($host_conf['pre_classes'] as $preclassvalue) {
      print "# Trying to load ".$preclassvalue.".pre\n";
      afi_require_file_and_override ("pre/${preclassvalue}.pre"); 
      print "# End ".$preclassvalue.".pre \n";
      print "\n";
    }
  }
  print "# End preclasses\n";
  print "\n";

  print "# Trying to load pre partition class \n";
  afi_part_pre();
  print "# End pre partition class \n";
  print "\n";

  if ($host_conf['disableconsistennetworkdevicenaming'] == 1){
    print "# bring back eth* network interface names instead of consistent ethernet device naming\n";
    print "# requires kernel parameters net.ifnames=0 biosdevname=0\n";
    print "rm -f /etc/udev/rules.d/70*\n"; 
    print "ln -s /dev/null /etc/udev/rules.d/80-net-setup-link.rules\n"; 
    print "ln -s /dev/null /etc/udev/rules.d/80-net-name-slot.rules\n"; 
    print "\n";
  }
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

#POST NOCHROOT 1st
<?php
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
  print "# Trying to load postclasses - nochroot\n";
  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      print "# Trying to load ".$postclassvalue.".nochroot\n";
      afi_require_file_and_override ("post/${postclassvalue}.nochroot"); 
      print "# End ".$postclassvalue.".nochroot \n";
      print "\n";
    }
  }
  print "# End postclasses - nochroot\n";
  print "\n";
?>

}
<?php
print "AFI_POST_DIR=\"$afi_post_dir\"\n";
?>

main 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first.log"
set 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first_var.log"
export 2>&1|tee "$AFI_POST_DIR/afi_post_nochroot_first_export.log"

%end

#POST CHROOT
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

  if ($host_conf['quiet_boot'] == 0){
    print "# disable quiet boot\n";
    print "sed -i -r 's/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*[^[:alnum:]])quiet([^[:alnum:]])/\\1\\2/g' /etc/default/grub\n";
    print "\n";
  }
  if ($host_conf['rhgb_boot'] == 0){
    print "# disable rhgb boot\n";
    print "sed -i -r 's/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*[^[:alnum:]])rhgb([^[:alnum:]])/\\1\\2/g' /etc/default/grub\n";
    print "\n";
  }

  print "#reorder console entries for tty* and ttyS*(serial console) to make ttyS*(serial console) the primary console\n";
  print "sed -i -r \"s/^[[:blank:]]*(GRUB_CMDLINE_LINUX[[:blank:]]*=[[:blank:]]*[\\\"'])(.*[^[:alnum:]])(console=tty[0-9]*)([^[:alnum:]])/\\1 \\3 \\2 \\4/g\" /etc/default/grub\n";
  print "sed -i -r \"s/^[[:blank:]]*(GRUB_CMDLINE_LINUX.*)([^[:alnum:]]console=ttyS[0-9]*,[[:alnum:]]+[^[:alnum:]])(.*)([\\\"'])/\\1 \\3 \\2 \\4/g\" /etc/default/grub\n";
  print "\n";
  print "# this requires regenerating the grub config\n";
  print "grub2-mkconfig -o /boot/grub2/grub.cfg\n";
  print "\n";

  if ($host_conf['disableconsistennetworkdevicenaming'] == 1){
    print "# bring back eth* network interface names instead of consistent ethernet device naming\n";
    print "# requires kernel parameters net.ifnames=0 biosdevname=0\n";
    print "rm -f /etc/udev/rules.d/70*\n"; 
    print "ln -s /dev/null /etc/udev/rules.d/80-net-setup-link.rules\n"; 
    print "ln -s /dev/null /etc/udev/rules.d/80-net-name-slot.rules\n"; 
    print "\n";
  }

  print "# removing ifcfg-* config files(except ifcfg-lo) - nm does dhcp per default on all interfaces\n";
  print "for f in  /etc/sysconfig/network-scripts/ifcfg-*;do \n";
  print "  [ \"\$f\" != \"/etc/sysconfig/network-scripts/ifcfg-lo\" ] && rm -f \"\$f\"\n";
  print "done \n";
  print "\n";

  print "# Firstreboot\n";
  print "\n";
  print "mkdir -p /etc/rc.firstreboot\n";
  print "chmod 700 /etc/rc.firstreboot\n";
  print "mkdir -p /var/log/install/firstreboot\n";
  print "\n";
 
  print "# firstreboot-scripts\n";
  
  print "# Trying to load post - firstreboot partition class \n";
  print "\n";
  print "#function afi_part_firsteboot must create at least one file in\n";
  print "# /etc/rc.firstreboot/[0-9][0-9][0-9]-<a-filename>\n";
  print "# and can therefore decide the ordering to other scripts\n";
  print "# !!! THE CREATED FILES MUST BE MADE EXECUTABLE !!!\n";
  print "\n";
  afi_part_firstreboot();
  print "# End post - firstreboot partition class \n";
  print "\n";

  print "# Trying to load postclasses - firstreboot\n";
  print "\n";
  print "# functions in *.firstreboot must create at least one file in\n";
  print "# /etc/rc.firstreboot/[0-9][0-9][0-9]-<a-filename>\n";
  print "# and can therefore decide the ordering to other scripts\n";
  print "# !!! THE CREATED FILES MUST BE MADE EXECUTABLE !!!\n";
  print "\n";
  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      print "# Trying to load ".$postclassvalue.".firstreboot\n";
      afi_require_file_and_override ("post/${postclassvalue}.firstreboot"); 
      print "# End ".$postclassvalue.".firstreboot \n";
      print "\n";
    }
  }
  print "# End postclasses - firstreboot\n";
  print "\n";

?>

# Creating firstreboot script
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

# End creating firstreboot script
# End firstreboot

<?php 
  print "# Trying to load post - chroot partition class \n";
  afi_part_post();
  print "# End post - chroot partition class \n";
  print "\n";

  print "# Trying to load postclasses - chroot\n";
  if (is_array($host_conf['post_classes'])) {
    foreach ($host_conf['post_classes'] as $postclassvalue) {
      print "# Trying to load ".$postclassvalue.".chroot\n";
      afi_require_file_and_override ("post/${postclassvalue}.chroot"); 
      print "# End ".$postclassvalue.".chroot \n";
      print "\n";
    }
  }
  print "# End postclasses - chroot\n";
  print "\n";

  if (afi_get_const_array_key('AFI_CLIENT_CONF','dist') == "oracle") {
    print "# hacky workaround to remove grub entry for kernel (we only want kernel-uek on oracle linux)\n";
    print "for KERNEL in /boot/vmlinuz-2.6.32*; do /sbin/grubby --remove-kernel=\"\$KERNEL\";done\n";
    print "\n";
  }

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


#POST NOCHROOT 2nd
<?php
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
  print "# Trying to load post - nochroot partition class \n";
  afi_part_post_nochroot();
  print "# End post - nochroot partition class \n";
  print "\n";
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
