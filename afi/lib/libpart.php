<?php

function afi_part_bootloader() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());

  $bootloader_append_string = "console=tty0";

  if ($host_conf['disableconsistennetworkdevicenaming'] == 1){
    print "\n";
    print "# bring back eth* network interface names instead of consistent ethernet device naming\n";
    print "# requires kernel parameters net.ifnames=0 biosdevname=0\n";
    print "# biosdevname=0 will be taken from kickstart kernel parameters, but not net.ifnames=0, \n";
    print "# that's why we add it manually here\n";
    $bootloader_append_string = $bootloader_append_string." net.ifnames=0";
  }

  print "bootloader --append=\"".$bootloader_append_string."\" --timeout=15 --location=".$host_conf['bootloader_location']." --iscrypted --password=".$host_conf['initial_pw_pbkdf2']." --driveorder=".implode(",",$host_conf['install_disks'])."\n";
}

function afi_part_main() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());

  $partition_class=afi_part_get_partitionclass();
  afi_require_file_and_override ("part/${partition_class}.main",false);

  if (! function_exists('afi_partition_main')) {
    print "error loading function afi_partition_main()\n";
    exit(1);
  }

  afi_partition_main(afi_part_get_installdisks(), afi_part_get_installdisks_comma(), afi_part_get_installdisks_space() );
}

function afi_part_pre() {
  $partition_class=afi_part_get_partitionclass();

  afi_require_file_and_override ("part/${partition_class}.pre",false);
  if (function_exists('afi_partition_pre')) {
    afi_partition_pre(afi_part_get_installdisks());
  }
}

function afi_part_firstreboot() {
  $partition_class=afi_part_get_partitionclass();

  afi_require_file_and_override ("part/${partition_class}.firstreboot",false);
  if (function_exists('afi_partition_firstreboot')) {
    afi_partition_firstreboot(afi_part_get_installdisks());
  }
}

function afi_part_post() {
  $partition_class=afi_part_get_partitionclass();

  afi_require_file_and_override ("part/${partition_class}.post",false);
  if (function_exists('afi_partition_post')) {
    afi_partition_post(afi_part_get_installdisks());
  }
}

function afi_part_post_nochroot() {
  $partition_class=afi_part_get_partitionclass();

  afi_require_file_and_override ("part/${partition_class}.post_nochroot",false);
  if (function_exists('afi_partition_post_nochroot')) {
    afi_partition_post_nochroot(afi_part_get_installdisks());
  }
}

function afi_part_get_partitionclass() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());

  if ( isset($host_conf['partition_class'])) {
    $partition_class=$host_conf['partition_class'];
  } else {
    print "error loading partition_class\n";
    exit(1);
  }
  return $partition_class;
}

function afi_part_get_installdisks() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());

  foreach ($host_conf['install_disks'] as $afi_install_disk_number => $afi_install_disk) {
    $afi_install_disks[$afi_install_disk_number]['disk']=$afi_install_disk;
    $afi_install_disks[$afi_install_disk_number]['partprefix']=afi_part_get_partprefix($afi_install_disk);
  }
  afi_debug_var("afi_install_disks", $afi_install_disks,6);
  return $afi_install_disks;
}

function afi_part_get_installdisks_comma() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());
  return implode(",",$host_conf['install_disks']);
}

function afi_part_get_installdisks_space() {
  $host_conf= afi_get_host_config(afi_get_const_array_key('AFI_INI_SETTINGS','afi_conf_dir'), afi_get_client_profile_name());
  return implode(" ",$host_conf['install_disks']);
}

function afi_part_get_partprefix ($afi_install_disk) {
  $pattern = '/^disk\/by-/';
  $partprefix = "";
  if (preg_match($pattern, $afi_install_disk)) {
    $partprefix = "-part";
  }
  return $partprefix;
}

function afi_disk_settle($afi_install_disk_full) {
  print "\n";
  print "echo \"settle disk device ${afi_install_disk_full} with udev and fdisk\"\n";
  print "echo \"Executing: blockdev --rereadpt ${afi_install_disk_full}\"\n";
  print "blockdev --rereadpt ${afi_install_disk_full}\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "\n";
}

#parted
function afi_make_partitions($afi_install_disk , $afi_part_bootable, $afi_disklabel_type, $afi_install_disk_layout ) {

  $afi_part_size_unit="B";
  $afi_part_size_factor="1048576";
  $afi_part_size_factor_logical=$afi_part_size_factor * 32;
  print "\n";
  print "AFI_PART_START=\"${afi_part_size_factor}\"\n";
  print "echo \"AFI_PART_START: \$AFI_PART_START\"\n";
  print "\n";

  $afi_install_disk_full="/dev/${afi_install_disk}";

  $afi_parted_string_init="parted -s \"$afi_install_disk_full\" unit $afi_part_size_unit";

  $afi_parted_string="$afi_parted_string_init mklabel $afi_disklabel_type";
  
  print "\n";
  print "echo 'Executing: ${afi_parted_string}'\n";
  print "${afi_parted_string}\n";
  print "\n";
  afi_disk_settle($afi_install_disk_full);

  print "\n";
  print "AFI_PART_DISK_SIZE=\"`parted -s \"${afi_install_disk_full}\" unit ${afi_part_size_unit} print|grep \"^Disk[[:blank:]].*\\:\"|awk '{print \$NF}'|sed 's/[^0-9]//g'`\"\n";
  print "AFI_PART_DISK_SIZE_FACTOR=\"`expr \$AFI_PART_DISK_SIZE \\/ ${afi_part_size_factor}`\"\n";
  print "AFI_PART_DISK_SIZE_ALIGNED=\"`expr \$AFI_PART_DISK_SIZE_FACTOR \\* ${afi_part_size_factor}`\"\n";
  print "echo \"AFI_PART_DISK_SIZE: \$AFI_PART_DISK_SIZE\"\n";
  print "echo \"AFI_PART_DISK_SIZE_FACTOR: \$AFI_PART_DISK_SIZE_FACTOR\"\n";
  print "echo \"AFI_PART_DISK_SIZE_ALIGNED: \$AFI_PART_DISK_SIZE_ALIGNED\"\n";
  print "\n";

  foreach ($afi_install_disk_layout as $afi_install_disk_partition) {

    $afi_part_type=$afi_install_disk_partition['part_type'];
    print "\n";
    echo "AFI_PART_SIZE=\"${afi_install_disk_partition['part_size']}\"\n";
    echo "[ \"\$AFI_PART_SIZE\" = \"end\" ] || AFI_PART_SIZE=\"`expr \$AFI_PART_SIZE \\* ${afi_part_size_factor}`\"\n";
    print "echo \"AFI_PART_SIZE: \$AFI_PART_SIZE\"\n";
    print "\n";

    print "\n";
    if ( $afi_install_disk_partition['part_size'] == "end" ) {
      print "AFI_PART_END=\"`expr \$AFI_PART_DISK_SIZE_ALIGNED \\- 1`\"\n";
      if ( $afi_part_type == "logical" ) {
        print "AFI_PART_END=\"`expr \$AFI_PART_END \\- ${afi_part_size_factor_logical}`\"\n";
      }
    } else {
      print "AFI_PART_END=\"`expr \$AFI_PART_START \\+ \$AFI_PART_SIZE \\- 1`\"\n";
    }
    print "echo \"AFI_PART_END: \$AFI_PART_END\"\n";
    print "\n";

    $afi_parted_string="${afi_parted_string_init} mkpart ${afi_part_type} \"\" \${AFI_PART_START}${afi_part_size_unit} \${AFI_PART_END}${afi_part_size_unit}";

    print "\n";
    print "echo 'Executing: ${afi_parted_string}'\n";
    print "${afi_parted_string}\n";
    print "\n";
    afi_disk_settle($afi_install_disk_full);

    print "\n";
    if ( $afi_part_type == "extended" ) {
      print "AFI_PART_START=\"`expr \$AFI_PART_START \\+ ${afi_part_size_factor_logical}`\"\n";
    } elseif ( $afi_part_type == "logical" ) {
      print "AFI_PART_START=\"`expr \$AFI_PART_END \\+ 1 \\+ ${afi_part_size_factor_logical}`\"\n";
    } else {
      print "AFI_PART_START=\"`expr \$AFI_PART_END \\+ 1`\"\n";
    }
    print "echo \"AFI_PART_START: \$AFI_PART_START\"\n";
    print "\n";

  }

  if (preg_match('/^[0-9][0-9]*$/', $afi_part_bootable)) {  
    print "\n";
    print "echo \"Executing: parted -s \\\"${afi_install_disk_full}\\\" unit ${afi_part_size_unit} print set ${afi_part_bootable} boot on print\"\n";
    print "parted -s \"${afi_install_disk_full}\" unit ${afi_part_size_unit} print set ${afi_part_bootable} boot on print\n";
    print "\n";
  } 

  afi_disk_settle($afi_install_disk_full);
}

#md
function afi_mkraid1($afi_mkraid1_disk1, $afi_mkraid1_disk2, $afi_mkraid1_partnumber, $afi_mkraid1_superblock_version) {
  #create md array
  $afi_mkraid1_string_create="echo y|mdadm -C --force /dev/md".$afi_mkraid1_partnumber." -l 1 -n 2 -e ".$afi_mkraid1_superblock_version." /dev/".$afi_mkraid1_disk1.afi_part_get_partprefix($afi_mkraid1_disk1).$afi_mkraid1_partnumber." /dev/".$afi_mkraid1_disk2.afi_part_get_partprefix($afi_mkraid1_disk2).$afi_mkraid1_partnumber;
  print "\n";
  print "echo \"Executing: ".$afi_mkraid1_string_create."\"\n";
  print $afi_mkraid1_string_create."\n";
  afi_disk_settle("/dev/".$afi_mkraid1_disk1);
  afi_disk_settle("/dev/".$afi_mkraid1_disk2);
  print "\n";
}

function afi_asraid1($afi_asraid1_disk1, $afi_asraid1_disk2, $afi_asraid1_partnumber ) {
  # assemble md array if possible
  $afi_asraid1_string_activate="[ ! -b /dev/md".$afi_asraid1_partnumber." ] && echo y|mdadm -A --force /dev/md".$afi_asraid1_partnumber."  /dev/".$afi_asraid1_disk1.afi_part_get_partprefix($afi_asraid1_disk1).$afi_asraid1_partnumber." /dev/".$afi_asraid1_disk2.afi_part_get_partprefix($afi_asraid1_disk2).$afi_asraid1_partnumber;
  print "echo \"Executing: ".$afi_asraid1_string_activate."\"\n";
  print $afi_asraid1_string_activate."\n";
  afi_disk_settle("/dev/".$afi_asraid1_disk1);
  afi_disk_settle("/dev/".$afi_asraid1_disk2);
  print "\n";
}

function afi_disasraid1($afi_disasraid1_device ) {
  # disassemble md array if possible
  $afi_disasraid1_string_deactivate="[ -b '".$afi_disasraid1_device."' ] && mdadm --stop --force ".$afi_disasraid1_device;
  print "echo \"Executing: ".$afi_disasraid1_string_deactivate."\"\n";
  print $afi_disasraid1_string_deactivate."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "echo \"Executing: blkid -g\"\n";
  print "blkid -g\n";
  print "echo \"Executing: blkid\"\n";
  print "blkid\n";
  print "\n";
}

function afi_wipemd($afi_wipemd_devices) {
  $afi_wipemd_string="mdadm --zero-superblock --force ".$afi_wipemd_devices;
  print "\n";
  print "echo \"Executing: ".$afi_wipemd_string."\"\n";
  print $afi_wipemd_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "echo \"Executing: blkid -g\"\n";
  print "blkid -g\n";
  print "echo \"Executing: blkid\"\n";
  print "blkid\n";
  print "\n";
}

#luks
function afi_mkluks($afi_mkluks_device, $afi_mkluks_password) {
  $afi_mkluks_string="echo '".$afi_mkluks_password."'|cryptsetup luksFormat ".$afi_mkluks_device." --force-password -c aes-xts-plain64 -s 512 -h sha512 -i 5000 --use-random --align-payload=2048";
  print "\n";
  print "echo \"Executing: ".$afi_mkluks_string."\"\n";
  print $afi_mkluks_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "\n";
}

function afi_openluks($afi_openluks_device, $afi_openluks_luksname, $afi_openluks_password) {
  $afi_openluks_string="echo '".$afi_openluks_password."'|cryptsetup luksOpen ".$afi_openluks_device." ".$afi_openluks_luksname;
  print "\n";
  print "echo \"Executing: ".$afi_openluks_string."\"\n";
  print $afi_openluks_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "\n";
}

function afi_closeluks($afi_closeluks_luksname) {
  $afi_closeluks_string="cryptsetup luksClose ".$afi_closeluks_luksname;
  print "\n";
  print "echo \"Executing: ".$afi_closeluks_string."\"\n";
  print $afi_closeluks_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "\n";
}

function afi_wipeluks($afi_wipeluks_device) {
  $afi_wipeluks_string="echo -n SKUL | dd of=".$afi_wipeluks_device;
  print "\n";
  print "echo \"Executing: ".$afi_wipeluks_string."\"\n";
  print $afi_wipeluks_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "echo \"Executing: blkid -g\"\n";
  print "blkid -g\n";
  print "echo \"Executing: blkid\"\n";
  print "blkid\n";
  print "\n";
}

#fs
function afi_mkfs($afi_mkfs_devices, $afi_mkfs_fs, $afi_mkfs_options) {
  $afi_mkfs_string="mkfs.".$afi_mkfs_fs." ".$afi_mkfs_options." ".$afi_mkfs_devices;
  print "\n";
  print "echo \"Executing: ".$afi_mkfs_string."\"\n";
  print $afi_mkfs_string."\n";
  print "\n";
}

function afi_tunefs($afi_tunefs_device, $afi_tunefs_fs, $afi_tunefs_options) {
  switch (true) {
    case preg_match("/^ext[2-4]$/", $afi_tunefs_fs):
      $afi_tunefs_command="tune2fs";
      break;
    case ($afi_tunefs_fs == "btrfs"):
      $afi_tunefs_command="btrfstune";
      break;
    case ($afi_tunefs_fs == "xfs"):
      print $afi_tunefs_fs." has no tuning command\n";
      return(1);
      break;
    default: 
      print "unknown fs \"".$afi_tunefs_fs."\" for tuning, no tuning command available\n";
      return(1);
      break;
  }  

  if ( $afi_tunefs_options != "" && $afi_tunefs_command != "" ) {
    print "\n";
    $afi_tunefs_string=$afi_tunefs_command." ".$afi_tunefs_options." ".$afi_tunefs_device;
    print "echo \"Executing: ".$afi_tunefs_string."\"\n";
    print $afi_tunefs_string."\n";
    print "\n";
  }
}

function afi_wipefs($afi_wipefs_device) {
  $afi_wipefs_string="wipefs -a ".$afi_wipefs_device;
  print "\n";
  print "echo \"Executing: ".$afi_wipefs_string."\"\n";
  print $afi_wipefs_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "echo \"Executing: blkid -g\"\n";
  print "blkid -g\n";
  print "echo \"Executing: blkid\"\n";
  print "blkid\n";
  print "\n";
}

function afi_setup_grub($afi_grub_device, $afi_chroot_dir, $afi_boot_partition_number) {
  print "BLOCKDEVICE=\"/dev/$(ls -l ".$afi_grub_device."|awk '{print \$NF}'|awk -F '/' '{print \$NF}')\"\n";
  $afi_setup_grub_string="chroot ".$afi_chroot_dir." grub --device-map=/dev/null --no-floppy --no-curses --batch <<-EOF\ndevice (hd0) \$BLOCKDEVICE\nroot (hd0,".$afi_boot_partition_number.")\nsetup (hd0)\nquit\nEOF\n";
  print "\n";
  #print "echo \"Executing: ".$afi_setup_grub_string."\"\n";
  print $afi_setup_grub_string."\n";
  print "echo \"Executing: udevadm settle\"\n";
  print "udevadm settle\n";
  print "sleep 1\n";
  print "\n";
}

?>
