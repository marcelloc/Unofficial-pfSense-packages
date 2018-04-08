<?php

/*
 * tinyproxy.inc
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

$shortcut_section = "tinyproxy";

$pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);
if ($pfs_version == "2.3" ) {
	require_once("xmlrpc.inc");
}
require_once("xmlrpc_client.inc");

function tinyproxy_check_config() {
        global $savemsg, $config;

        if (!isset($config['installedpackages']['tinyproxy']['config'][0]['enable'])) {
                return 1;
        }

        if (isset($_REQUEST['apply'])) {
                tinyproxy_start("no", false, true);
                return;
        }
        if (is_subsystem_dirty('tinyproxy')) {
                $savemsg  = gettext("Tinyproxy configuration has been changed.") . "<br>" . gettext("The changes must be applied for them to take effect.");
                $savemsg .= "</div><div class='pull-right'><button type='button' onclick=\"location.href='{$_SERVER['REQUEST_URI']}&apply=apply changes'\" class='btn btn-success'>";
                $savemsg .= gettext("Apply Changes") . "</button><a>&nbsp;&nbsp;</a>";
        }
        return 0;
}


function sync_package_tinyproxy(){
	global $config;

	$tinyproxy_server = $config['system']['hostname']. "." .$config['system']['domain'];
	if (is_array($config['installedpackages']['tinyproxy']['config'])) {
		$e2g_config = $config['installedpackages']['tinyproxy']['config'][0];
		$tpc['ip'] = tinyproxy_get_real_interface_address($e2g_config['interface']);
		$tpc['port'] = $e2g_config['listenport'];
		$tpc['maxclients'] = (is_numeric($e2g_config['maxclients']) ? $e2g_config['maxclients'] : 1000);
		$tpc['min'] = (is_numeric($e2g_config['min']) ? $e2g_config['min'] : 5);
		$tpc['max'] = (is_numeric($e2g_config['max']) ? $e2g_config['max'] : 20);
		$tpc['start'] = (is_numeric($e2g_config['start']) ? $e2g_config['start'] : 20);
		$tpc['maxperchild'] = (is_numeric($e2g_config['maxperchild']) ? $e2g_config['maxperchild'] : 0);
		//fix acl
		$tpc['allow'] = "Allow 127.0.0.1\n";
		if ($e2g_config['acl']) {
                    $lines = preg_split('/\r\n|\r|\n/', base64_decode($e2g_config['acl']));
                    foreach ($lines as $allow) {
                            $tpc['allow'] .= "Allow ".$allow."\n";
                    }
		}

		//load template
		include("/usr/local/pkg/tinyproxy.template");
		file_put_contents("/usr/local/etc/tinyproxy.conf", $tpf_file, LOCK_EX);

		 //nginx start stop script
		$rc_prefix = "/usr/local/etc/rc.d/tinyproxy";
		if ( file_exists($rc_prefix) && !file_exists("{$rc_prefix}.sh")) {
			@symlink($rc_prefix, "{$rc_prefix}.sh");		
		}

		//mark config changes
		mark_subsystem_dirty('tinyproxy');
	}
	

}

function start_tinyproxy(){
	exec('/usr/local/etc/rc.d/tinyproxy.sh onerestart || /usr/local/etc/rc.d/tinyproxy.sh onestart');
}


function stop_tinyproxy(){
	exec('/usr/local/etc/rc.d/tinyproxy.sh onestop');
}

function tinyproxy_get_real_interface_address($iface) {
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

function tinyproxy_start($via_rpc = "no", $install_process = false, $force_start = false) {
        global $g, $config, $savemsg;

        clear_subsystem_dirty('tinyproxy');

        if (!$force_start) {
                if (tinyproxy_check_config() <> 0) {
                        return;
                }
        }
	
        $script = '/usr/local/etc/rc.d/tinyproxy.sh';
        if (is_array($config['installedpackages']['tinyproxy']['config'])) {
                $tiny_cfg = $config['installedpackages']['tinyproxy']['config'][0];
        } else {
                log_error('Cannot find tinyproxy daemon configuration');
        }
        if ($tiny_cfg['enable'] == "on") {
		start_tinyproxy();
	} else {
		stop_tinyproxy();
	}
}

function tinyproxy_validate_input($post, &$input_errors) {
	global $config;
	if ($post['listenport'] != "" ) {
		if ( ! is_numeric($post['listenport'])) {
			$input_errors[] = "{$post['listenport']} is not valid port number.";
		}
		if ($post['listenport'] == 80 && ! isset($config['system']['webgui']['disablehttpredirect'])) {
			$input_errors[] = "Port 80 cannot be used until Webgui redirect is disabled under system -> advanced";
		}
	}

}

/* Uses XMLRPC to synchronize the changes to a remote node. */
function tinyproxy_sync_on_changes() {
	global $config;

	if (is_array($config['installedpackages']['tinyproxysync']['config'])) {
		$tinyproxy_sync = $config['installedpackages']['tinyproxysync']['config'][0];
		$synconchanges = $tinyproxy_sync['synconchanges'];
		$synctimeout = $tinyproxy_sync['synctimeout'] ?: '250';
		switch ($synconchanges) {
			case "manual":
				if (is_array($tinyproxy_sync['row'])) {
					$rs = $tinyproxy_sync['row'];
				} else {
					log_error("[tinyproxy] XMLRPC sync is enabled but there are no hosts configured as replication targets.");
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
						log_error("[tinyproxy] XMLRPC CARP/HA sync is enabled but there are no system backup hosts configured as replication targets.");
						return;
					} else {
						$rs[0]['syncdestinenable'] = TRUE;
					}
				} else {
					log_error("[tinyproxy] XMLRPC CARP/HA sync is enabled but there are no system backup hosts configured as replication targets.");
					return;
				}
				break;
			default:
				return;
				break;
		}
		if (is_array($rs)) {
			log_error("[tinyproxy] XMLRPC sync is starting.");
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
						tinyproxy_do_xmlrpc_sync($sync_to_ip, $port, $protocol, $username, $password, $synctimeout);
					} else {
						log_error("[tinyproxy] XMLRPC sync with '{$sync_to_ip}' aborted due to the following error(s): {$error}");
					}
				}
			}
			log_error("[tinyproxy] XMLRPC sync completed.");
		}
 	}
}

/* Do the actual XMLRPC sync. */
function tinyproxy_do_xmlrpc_sync($sync_to_ip, $port, $protocol, $username, $password, $synctimeout) {
	global $config, $g;

	if ($username == "" || $password == "" || $sync_to_ip == "" || $port == "" || $protocol == "") {
		log_error("[tinyproxy] A required XMLRPC sync parameter (username, password, replication target, port or protocol) is empty ... aborting pkg sync");
		return;
	}

	// Take care of IPv6 literal address
	if (is_ipaddrv6($sync_to_ip)) {
		$sync_to_ip = "[{$sync_to_ip}]";
	}

	$url = "{$protocol}://{$sync_to_ip}";

	/* XML will hold the sections to sync. */
	$xml = array();
	$xml['tinyproxy'] = $config['installedpackages']['tinyproxy'];
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
		log_error("[tinyproxy] {$error}");
		file_notice("sync_settings", $error, "tinyproxy Settings Sync", "");
	} elseif ($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = "An error code was received while attempting XMLRPC sync with {$url}:{$port} - Code " . $resp->faultCode() . ": " . $resp->faultString();
		log_error("[tinyproxy] {$error}");
		file_notice("sync_settings", $error, "tinyproxy Settings Sync", "");
	} else {
		log_error("[tinyproxy] XMLRPC sync successfully completed with {$url}:{$port}.");
	}

	/* Tell tinyproxy to reload our settings on the destination sync host. */
	$method = 'pfsense.exec_php';
	$execcmd = "require_once('/usr/local/pkg/tinyproxy.inc');\n";
	$execcmd .= "sync_package_tinyproxy();";
	/* Assemble XMLRPC payload. */
	$params = array(XML_RPC_encode($password), XML_RPC_encode($execcmd));

	log_error("[tinyproxy] XMLRPC reload data {$url}:{$port}.");
	$msg = new XML_RPC_Message($method, $params);
	$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
	$cli->setCredentials($username, $password);
	$resp = $cli->send($msg, $synctimeout);
	if (!$resp) {
		$error = "A communications error occurred while attempting XMLRPC sync with {$url}:{$port} (pfsense.exec_php).";
		log_error("[tinyproxy] {$error}");
		file_notice("sync_settings", $error, "tinyproxy Settings Sync", "");
	} elseif ($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = "An error code was received while attempting XMLRPC sync with {$url}:{$port} - Code " . $resp->faultCode() . ": " . $resp->faultString();
		log_error("[tinyproxy] {$error}");
		file_notice("sync_settings", $error, "tinyproxy Settings Sync", "");
	} else {
		log_error("[tinyproxy] XMLRPC reload data success with {$url}:{$port} (pfsense.exec_php).");
	}
}

?>
