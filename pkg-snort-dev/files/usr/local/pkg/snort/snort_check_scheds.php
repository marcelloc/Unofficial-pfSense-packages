#!/usr/local/bin/php
<?php
/*
 * check_snort_scheds.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2016 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2009-2010 Robert Zelaya
 * Copyright (c) 2013-2016 Bill Meeks
 * Copyright (C) 2017      Marcello Coutinho
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("/usr/local/pkg/snort/snort.inc");
require_once("/etc/inc/util.inc");

$temp_file="/tmp/check_snort_scheds.tmp";

if ($argv[1] == "pause") {
        echo "Maintainance mode, killling snort and block database...";
        exec('/usr/bin/killall snort');
        exec('/sbin/pfctl -t snort2c -T flush');
        exit;
}

if (file_exists ($temp_file)) {
        echo "Another check_snort_scheds.php is still running...\n";
        log_error("Another check_snort_scheds.php is still running. Consider increasing schedule time on cron.");
        exit (1);
} else {
        file_put_contents($temp_file,date("Y-m-d H:i:s"),LOCK_EX);
}

global $g, $rebuild_rules;

$snortdir = SNORTDIR;
$snortlogdir = SNORTLOGDIR;
$rcdir = RCFILEPREFIX;

if (!is_array($config['installedpackages']['snortglobal']['rule']))
	$config['installedpackages']['snortglobal']['rule'] = array();
$a_nat = &$config['installedpackages']['snortglobal']['rule'];

// Calculate the index of the next added Snort interface
$id_gen = count($config['installedpackages']['snortglobal']['rule']);

// Get list of configured firewall interfaces
$ifaces = get_configured_interface_list();

if ( ! is_array($config['installedpackages']['snortglobal']) || ! is_array($config['installedpackages']['snortglobal']['rule'])) {
 print "No configured interfaces\n";
 exit ("1");
}

$snortcfg = $config['installedpackages']['snortglobal']['rule'];

if (!is_array($config['schedules']['schedule'])) {
        $config['schedules']['schedule'] = array();
}

$a_schedules = &$config['schedules']['schedule'];
$pass_aliases=array();
foreach ( $snortcfg as $id => $rules) {
        $check_snort_info = $rules['enable'];

        $check_snort_sched = $rules['sched'];

        print "ID:{$id} IFACE:{$rules['interface']} STATUS:{$rules['enable']} SCHED:{$rules['sched']} UUID:{$rules['uuid']}\n";

        foreach ($a_schedules as $schedule){
                if ($schedule['name'] == $check_snort_sched && $check_snort_sched <> 'none') {
                        $schedstatus = filter_get_time_based_rule_status($schedule);
                        $msg="IFACE:{$rules['interface']} STATUS:{$rules['enable']} SCHED:{$rules['sched']}\n";
                        if ($check_snort_info == "on") {
                                if ($schedstatus) {
                                   echo "checking if started {$msg}";
                                   snort_check($id,'start');
                                } else {
                                  echo "checking if stopped {$msg}";
                                   snort_check($id,'stop');
                                }
                        } else {
                                echo "checking if stopped {$msg}";
                                snort_check($id,'stop');
                        }
                }
        }
}
foreach ($pass_aliases as $pass_alias){
        snort_check_mygateways($pass_alias,"update");
}
unlink_if_exists($temp_file);
/* start/stop barnyard2 */
function barn_check($id,$action) {
        global $config;
	$snortcfg = $config['installedpackages']['snortglobal']['rule'][$id];
	$if_real = get_real_interface($snortcfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($snortcfg['interface']);

	if (!snort_is_running($snortcfg['uuid'], $if_real, 'barnyard2')) {
		log_error("Toggle (barnyard starting) for {$if_friendly}({$if_real})...");
		conf_mount_rw();
		sync_snort_package_config();
		conf_mount_ro();
		snort_barnyard_start($snortcfg, $if_real);
	} else {
		log_error("Toggle (barnyard stopping) for {$if_friendly}({$if_real})...");
		snort_barnyard_stop($snortcfg, $if_real);
	}
	sleep(3); // So the GUI reports correctly
}

/* check snort PassLIst */
function snort_check_mygateways ($tablename="none",$save_md5="keep") {
        $return="keep";
        if ($tablename != 'none') {
          $table_md5="/tmp/{$tablename}.md5";
          $current_md5="none";
          if (file_exists("$table_md5")) {
            $current_md5=file_get_contents("$table_md5");
            if ($save_md5=="keep"){
               print "Last md5:\t{$current_md5}\n";
            }
          }
          $cmd_return=array();
          exec("/sbin/pfctl -t " . escapeshellarg($tablename) . " -T show | /usr/bin/sort | /sbin/md5" , $cmd_return,$cmd_exit_code);
          //var_dump ($cmd_return);
          if ($save_md5=="keep"){
             print "Current md5:\t{$cmd_return[0]}\n";
          }
          if ($current_md5 != "none" && $current_md5 != $cmd_return[0]) {
           $return ="restart";
          }
          if ($save_md5=="update") {
             print "Saving md5 hash for {$tablename}...\n";
             file_put_contents($table_md5,$cmd_return[0],LOCK_EX);
          }
        }
        if ($save_md5=="keep"){
           print "Snort whitelist table check Return: $return\n";
        }
        return($return);
}

/* start/stop snort */
function snort_check($id,$action){
        global $config,$pass_aliases;
        print "checking id $id, action $action\n";
	$snortcfg = $config['installedpackages']['snortglobal']['rule'][$id];
        var_dump($snortcfg['uuid']);
        var_dump($snortcfg['whitelistname']);
        $wl_table_status=snort_check_mygateways($snortcfg['whitelistname']);
        $pass_aliases[$snortcfg['whitelistname']]=$snortcfg['whitelistname'];
	$if_real = get_real_interface($snortcfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($snortcfg['interface']);
      
	if (snort_is_running($snortcfg['uuid'], $if_real)) {
                echo "Snort is running, action is $action\n";
                if ($action == "stop") {
        		log_error("Toggle (snort stopping) for {$if_friendly}({$if_real})...");
	        	snort_stop($snortcfg, $if_real);
			exec('/sbin/pfctl -t snort2c -T flush');
                }
                if ($wl_table_status == "restart" ) {
                        snort_check_mygateways($snortcfg['whitelistname'],"update");
                        log_error("Whitelist table changed, restarting snort for {$if_friendly}({$if_real})...");
                        snort_stop($snortcfg, $if_real);
			exec('/sbin/pfctl -t snort2c -T flush');
                        sleep(2);
                        // set flag to rebuild interface rules before starting Snort
                        $rebuild_rules = true;
                        conf_mount_rw();
                        sync_snort_package_config();
                        conf_mount_ro();
                        $rebuild_rules = false;
                        snort_start($snortcfg, $if_real);
                }
	} else {
                echo "Snort is not running, action is $action\n";
                 if ($action == "start") {
		        log_error("Toggle (snort starting) for {$if_friendly}({$if_real})...");

		        // set flag to rebuild interface rules before starting Snort
		        $rebuild_rules = true;
		        conf_mount_rw();
		        sync_snort_package_config();
		        conf_mount_ro();
	        	$rebuild_rules = false;
		        snort_start($snortcfg, $if_real);
                }
	}
      
	sleep(3); // So the GUI reports correctly
}


