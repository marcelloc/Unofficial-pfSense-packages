<?php

/*
 * wpad.inc
 *
 * part of Unofficial packages for pfSense(R) softwate
 * Copyright (c) 2017 Marcello Coutinho
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
require_once('config.inc');
require_once('globals.inc');
require_once('services.inc');
require_once('service-utils.inc');
require_once('util.inc');
require_once("notices.inc");
require_once("interfaces.inc");
require_once("xmlrpc.inc");
require_once("xmlrpc_client.inc");

function wpad_text_area_decode($text) {
	return preg_replace('/\r\n/', "\n", base64_decode($text));
}

function wpad_clean($wpad_index) {
	$rc_file = "/usr/local/etc/rc.d/nginx_wpad{$wpad_index}.sh";
	//print "$wpad_index $rc_file";
	exec("{$rc_file} stop",$x);
	//var_dump($x);
	//exit;
	unlink_if_exists($rc_file);
	$files = array("wpad.dat","wpad.da","proxy.pac");
	foreach ($files as $file) {
		unlink_if_exists("/usr/local/www/wpad{$wpad_index}/{$file}");
	}	
	if (file_exists ("/usr/local/www/wpad{$wpad_index}")) {
		rmdir("/usr/local/www/wpad{$wpad_index}");
	}
}

function wpad_get_real_interface_address($iface) {
        global $config;
	if (preg_match("/[a-z]+/",$iface)) {
        	$iface = convert_friendly_interface_to_real_interface_name($iface);
        	$line = trim(shell_exec("ifconfig $iface | grep inet | grep -v inet6"));
        	list($dummy, $ip, $dummy2, $netmask) = explode(" ", $line);
        	return $ip;
	} else {
		return $iface;
	}
}


function wpad_check($wpad_index,$nginx){
	
	if (!is_dir("/usr/local/www/wpad{$wpad_index}")) {
                        mkdir ("/usr/local/www/wpad{$wpad_index}", 0755, true);
                }
	//nginx conf file
	$wpad_workers = ( $nginx['workers'] != "" ? $nginx['workers'] : "10" );
	$wpad_listenport = ( $nginx['listenport'] != "" ? $nginx['listenport'] : "80" );
	$wpad_cf = "/usr/local/etc/nginx/nginx_wpad{$wpad_index}.conf";
	$wpad_listen_ip = wpad_get_real_interface_address($nginx['interface']);
	include("/usr/local/pkg/wpad_nginx.template");
	file_put_contents($wpad_cf,$conf_template,LOCK_EX);
	
	//nginx start stop script
	$start  = "/usr/local/sbin/nginx -c {$wpad_cf}\n";
	$start .= "chmod go+rw /var/run/php-fpm.socket";
	//$stop = "/bin/ps -ax | /usr/bin/grep \"nginx -c {$wpad_cf}\" | /usr/bin/grep -v grep | /usr/bin/awk '{print $1}' | /usr/bin/xargs kill"; 
	$stop  = "if [ -f /var/run/nginx_wpad{$wpad_index}.pid ]\n\tthen\n\t";
	$stop .= "kill `cat /var/run/nginx_wpad{$wpad_index}.pid`\nfi";
	if (file_exists("/usr/local/etc/rc.d/nginx_wpad{$wpad_index}.sh")) {
		system("/usr/local/etc/rc.d/nginx_wpad{$index}.sh stop");
	}
	write_rcfile(array("file" => "nginx_wpad{$wpad_index}.sh", "start" => $start, "stop" => $stop));
}

function wpad_symlink($file1,$file2) {
	if (! file_exists($file2)) {
		if (file_exists($file1)) {
			@symlink($file1, $file2);
		}
	}
}
function sync_package_wpad() {
	global $config, $g;

	$index = 0;
	/* Loop on configured files. */
	if ($config['installedpackages']['wpad']['config'] != "") {
		foreach ($config['installedpackages']['wpad']['config'] as $index=> $file) {
			if ($file['enable'] == "on") {
				wpad_check($index,$file);
			} else {
				wpad_clean($index);
			}
			/* Write file. */
			$pac_file = "/usr/local/www/wpad{$index}/proxy.pac";
			file_put_contents($pac_file, wpad_text_area_decode($file['filedata']), LOCK_EX);
			wpad_symlink($pac_file, "/usr/local/www/wpad{$index}/wpad.da");
			wpad_symlink($pac_file, "/usr/local/www/wpad{$index}/wpad.dat");
			system("/usr/local/etc/rc.d/nginx_wpad{$index}.sh start");
		}
	}

	// check if there is scripts to remove
	$index = $index + 1;
	while (file_exists("/usr/local/etc/rc.d/nginx_wpad{$index}.sh")) {
		wpad_clean($index);
		$index = $index + 1;		
	}

	wpad_sync_on_changes();
}

function wpad_validate_input($post, &$input_errors) {
	global $config;
	if ($post['listenport'] != "" ) {
		if ( ! is_numeric($post['listenport'])) {
			$input_errors[] = "{$post['listenport']} is not valid port number.";
		}
		if ($post['listenport'] == 80 && ! isset($config['system']['webgui']['disablehttpredirect'])) {
			$input_errors[] = "Port 80 cannot be used until Webgui redirect is disabled under system -> advanced";
		}
	}
	if ($post['description'] && !preg_match("@^[a-zA-Z0-9 _/.-]+$@", $post['description'])) {
		$input_errors[] = "Do not use special characters in description.";
	}

	if (array_key_exists('filedata',$post)){
		if ($post['filedata'] == "") {
			$_POST['filedata'] = file_get_contents("/usr/local/pkg/wpad_script.template");
		}
	}
}

/* Uses XMLRPC to synchronize the changes to a remote node. */
function wpad_sync_on_changes() {
	global $config;

	if (is_array($config['installedpackages']['wpadsync']['config'])) {
		$wpad_sync = $config['installedpackages']['wpadsync']['config'][0];
		$synconchanges = $wpad_sync['synconchanges'];
		$synctimeout = $wpad_sync['synctimeout'] ?: '250';
		switch ($synconchanges) {
			case "manual":
				if (is_array($wpad_sync['row'])) {
					$rs = $wpad_sync['row'];
				} else {
					log_error("[wpad] XMLRPC sync is enabled but there are no hosts configured as replication targets.");
					return;
				}
				break;
			case "auto":
				if (is_array($config['hasync'])) {
					$system_carp = $config['hasync'];
					$rs[0]['ipaddress'] = $system_carp['synchronizetoip'];
					$rs[0]['username'] = $system_carp['username'];
					$rs[0]['password'] = $system_carp['password'];
					$rs[0]['syncdestinenable'] = FALSE;

					// XMLRPC sync is currently only supported over connections using the same protocol and port as this system
					if ($config['system']['webgui']['protocol'] == "http") {
						$rs[0]['syncprotocol'] = "http";
						$rs[0]['syncport'] = $config['system']['webgui']['port'] ?: '80';
					} else {
						$rs[0]['syncprotocol'] = "https";
						$rs[0]['syncport'] = $config['system']['webgui']['port'] ?: '443';
					}
					if ($system_carp['synchronizetoip'] == "") {
						log_error("[wpad] XMLRPC CARP/HA sync is enabled but there are no system backup hosts configured as replication targets.");
						return;
					} else {
						$rs[0]['syncdestinenable'] = TRUE;
					}
				} else {
					log_error("[wpad] XMLRPC CARP/HA sync is enabled but there are no system backup hosts configured as replication targets.");
					return;
				}
				break;
			default:
				return;
				break;
		}
		if (is_array($rs)) {
			log_error("[wpad] XMLRPC sync is starting.");
			foreach ($rs as $sh) {
				// Only sync enabled replication targets
				if ($sh['syncdestinenable']) {
					$sync_to_ip = $sh['ipaddress'];
					$port = $sh['syncport'];
					$username = $sh['username'] ?: 'admin';
					$password = $sh['password'];
					$protocol = $sh['syncprotocol'];

					$error = '';
					$valid = TRUE;

					if ($password == "") {
						$error = "Password parameter is empty. ";
						$valid = FALSE;
					}
					if (!is_ipaddr($sync_to_ip) && !is_hostname($sync_to_ip) && !is_domain($sync_to_ip)) {
						$error .= "Misconfigured Replication Target IP Address or Hostname. ";
						$valid = FALSE;
					}
					if (!is_port($port)) {
						$error .= "Misconfigured Replication Target Port. ";
						$valid = FALSE;
					}
					if ($valid) {
						wpad_do_xmlrpc_sync($sync_to_ip, $port, $protocol, $username, $password, $synctimeout);
					} else {
						log_error("[wpad] XMLRPC sync with '{$sync_to_ip}' aborted due to the following error(s): {$error}");
					}
				}
			}
			log_error("[wpad] XMLRPC sync completed.");
		}
 	}
}

/* Do the actual XMLRPC sync. */
function wpad_do_xmlrpc_sync($sync_to_ip, $port, $protocol, $username, $password, $synctimeout) {
	global $config, $g;

	if ($username == "" || $password == "" || $sync_to_ip == "" || $port == "" || $protocol == "") {
		log_error("[wpad] A required XMLRPC sync parameter (username, password, replication target, port or protocol) is empty ... aborting pkg sync");
		return;
	}

	// Take care of IPv6 literal address
	if (is_ipaddrv6($sync_to_ip)) {
		$sync_to_ip = "[{$sync_to_ip}]";
	}

	$url = "{$protocol}://{$sync_to_ip}";

	/* XML will hold the sections to sync. */
	$xml = array();
	$xml['wpad'] = $config['installedpackages']['wpad'];
	/* Assemble XMLRPC payload. */
	$params = array(XML_RPC_encode($password), XML_RPC_encode($xml));

	/* Set a few variables needed for sync code */
	$method = 'pfsense.merge_installedpackages_section_xmlrpc';
	$msg = new XML_RPC_Message($method, $params);
	$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
	$cli->setCredentials($username, $password);
	if ($g['debug']) {
		$cli->setDebug(1);
	}
	/* Send our XMLRPC message and timeout after 250 seconds. */
	$resp = $cli->send($msg, $synctimeout);
	if (!$resp) {
		$error = "A communications error occurred while attempting XMLRPC sync with {$url}:{$port}.";
		log_error("[wpad] {$error}");
		file_notice("sync_settings", $error, "wpad Settings Sync", "");
	} elseif ($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = "An error code was received while attempting XMLRPC sync with {$url}:{$port} - Code " . $resp->faultCode() . ": " . $resp->faultString();
		log_error("[wpad] {$error}");
		file_notice("sync_settings", $error, "wpad Settings Sync", "");
	} else {
		log_error("[wpad] XMLRPC sync successfully completed with {$url}:{$port}.");
	}

	/* Tell wpad to reload our settings on the destination sync host. */
	$method = 'pfsense.exec_php';
	$execcmd = "require_once('/usr/local/pkg/wpad.inc');\n";
	$execcmd .= "sync_package_wpad();";
	/* Assemble XMLRPC payload. */
	$params = array(XML_RPC_encode($password), XML_RPC_encode($execcmd));

	log_error("[wpad] XMLRPC reload data {$url}:{$port}.");
	$msg = new XML_RPC_Message($method, $params);
	$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
	$cli->setCredentials($username, $password);
	$resp = $cli->send($msg, $synctimeout);
	if (!$resp) {
		$error = "A communications error occurred while attempting XMLRPC sync with {$url}:{$port} (pfsense.exec_php).";
		log_error("[wpad] {$error}");
		file_notice("sync_settings", $error, "wpad Settings Sync", "");
	} elseif ($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = "An error code was received while attempting XMLRPC sync with {$url}:{$port} - Code " . $resp->faultCode() . ": " . $resp->faultString();
		log_error("[wpad] {$error}");
		file_notice("sync_settings", $error, "wpad Settings Sync", "");
	} else {
		log_error("[wpad] XMLRPC reload data success with {$url}:{$port} (pfsense.exec_php).");
	}
}

?>
