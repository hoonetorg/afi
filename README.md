#This document is a WIP FIXME
##AFI (Automatically Fully Install) is mainly a wrapper around kickstart installations.

The idea for the name is from FAI (Fully Automatic Installer) which is mainly used for Debian-based-systems.
Also some general ideas are taken from FAI.

Compareble products (which mostly have much more functionality) are: FAI, Cobbler, Foreman.

AFI is written in PHP.

It creates a boot file for iPXE (boot.ipxe.php) and a kickstart file (ks.cfg.<some distro>.php)

There are so many os deployment tools, why AFI?

- Installation is done via the provided (and supported) installer of the OS (that is not the case for FAI).
  - Installed system should be correctly installed:
    - no missing mountpoints,
    - no wrong boot configuration,
    - no malfunction after updates
- Installation via web-server and DHCP-server only:
  - DHCP defines a HTTP/HTTPS - location for the boot-file (boot.ipxe.php)
  - boot.ipxe.php is an iPXE-file
  - Server boots from iPXE-boot-rom (iso, usb, chainload from PXE or directly flashed into network card)
  - dynamically generated boot.ipxe.php let's:
    - server boot from hard disk, if client is configured not to be installed
    - server boot kernel+initrd from HTTP/HTTPS - location.
- Dynamically generated kickstart file (ks.cfg.<some distro>.php):
  - allows creating:
    - profiles for difficult %pre - scripts (used by partioning f.e.)
    - profiles for all settings in the installation (language, keyboard, bootloader, initial passwords f.e)
    - profiles for installed packages
    - profiles for used software repos(including proxy-configuration per repo)
  - from simple settings
    - in per installation-host config file or 
    - default settings file

###Installation
For AFI a webserver is required, which is able to serve php content.

Install a webserver with for example nginx and php.

Extract the afi archive to a web directory on that webserver

Configure AFI (afi.ini, default.conf, repos, partitioning schemes, post classes, hosts)
 
 
