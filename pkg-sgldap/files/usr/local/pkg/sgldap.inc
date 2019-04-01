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

function replace_files($action) {
    $pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);
    
    $sgldap_files = array( "squidguard.sgldap.inc", "squidguard.sgldap.xml",
        "squidguard_acl.sgldap.xml", "squidguard_configurator.sgldap.inc");
    
    $prefix = "/usr/local/pkg";
    
    switch ($action) {
        case 'install':
            print "\nReplacing squidguard files with sgldap files...";
            foreach ($sgldap_files as $sgfile) {
                $pkg_file = preg_replace("/.sgldap/", "", $sgfile);
                $orig_file = preg_replace("/.sgldap/", ".orig", $sgfile);
                if (file_exists("$prefix/$pkg_file")) {
                    copy ("$prefix/$pkg_file", "$prefix/$orig_file");
                }
                copy ("$prefix/$sgfile", "$prefix/$pkg_file");
            }
            break;
        case 'deinstall':
            print "\nRestoring squidguard files with sgldap files...";
            foreach ($sgldap_files as $sgfile) {
                $pkg_file = preg_replace("/.sgldap/", "", $sgfile);
                $orig_file = preg_replace("/.sgldap/", ".orig", $sgfile);
                if (file_exists("$prefix/$orig_file")) {
                    rename ("$prefix/$orig_file", "$prefix/$pkg_file");
                }
            }
            break;
        
    }
    print "ok\n";
}

function sgldap_install() {
    replace_files('install');
}

function sgldap_deinstall() {
    replace_files('deinstall');
}


?>
