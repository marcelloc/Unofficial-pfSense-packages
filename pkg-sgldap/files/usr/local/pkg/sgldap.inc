<?php
/*
	sgldap.inc
	part of Unofficial packages for pfSense
	Copyright (C) 2019 Marcello Coutinho
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted under apache2 license.
*/
/* ========================================================================== */

$pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);

$sgldap_files = array( "squidguard.sgldap.inc", "squidguard.sgldap.xml",
                       "squidguard_acl.sgldap.xml", "squidguard_configurator.sgldap.inc");

$prefix = "/usr/local/pkg";

if($argv[1] == "i") {
    sgldap_install();
}
if($argv[1] == "d") {
    sgldap_deinstall();
}


function sgldap_install() {
    global $prefix, $sgldap_files;
    print "Replacing squidguard files with sgldap files...";
    foreach ($sgldap_files as $sgfile) {
        $pkg_file = preg_replace("/.sgldap/", "", $sgfile);
        $orig_file = preg_replace("/.sgldap/", ".orig", $sgfile);
        if (file_exists("$prefix/$pkg_file")) {
            copy ("$prefix/$pkg_file", "$prefix/$orig_file");
        }
        copy ("$prefix/$sgfile", "$prefix/$pkg_file");
    }
    print "ok\n";
}

function sgldap_deinstall() {
    global $prefix, $sgldap_files;
    print "Restoring squidguard files with sgldap files...";
    foreach ($sgldap_files as $sgfile) {
        $pkg_file = preg_replace("/.sgldap/", "", $sgfile);
        $orig_file = preg_replace("/.sgldap/", ".orig", $sgfile);
        if (file_exists("$prefix/$orig_file")) {
            rename ("$prefix/$orig_file", "$prefix/$pkg_file");
        }
    }
    print "ok\n";
}


?>
