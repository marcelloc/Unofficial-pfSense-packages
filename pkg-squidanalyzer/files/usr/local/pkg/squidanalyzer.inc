<?php
# ------------------------------------------------------------------------------
/*
 * squidanalyzer.inc
 *
 * part of Unofficial packages for pfSense(R) softwate
 * Copyright (C) 2016 Luiz Gustavo <me@luizgustavo.pro.br>
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
# ------------------------------------------------------------------------------

require_once('globals.inc');
require_once('config.inc');
require_once('util.inc');
require_once('pfsense-utils.inc');
require_once('pkg-utils.inc');
require_once('filter.inc');
require_once('service-utils.inc');


define('SACONFIG', '/usr/local/etc/squidreport/squidanalyzer.conf');

function sa_check_cron($action, $min = "*", $hour = "*", $mday = "*", $month = "*", $wday = "*"){
	$cron_cmd = "/usr/local/bin/squid-analyzer";

	//Remove current squidanalyzer cron
	install_cron_job ($cron_cmd, false);

	if ($action == "create") {
		//Install a new squidanalyzer cron
		install_cron_job ($cron_cmd . ' > /dev/null', true, $min, $hour, $mday, $month, $wday , "root");
	}
}

function validate_form_sa($post, &$input_errors) {
	global $config;
	if (isset($post['saenable'])) {
		if ( $post['squidlog'] == "" ) {
			$input_errors[] = gettext('The field') . " Squid Log " . gettext('cannot be empty');
		} else {
			$files = explode(",",$post['squidlog']);
			foreach ($files as $file) {
				if (! file_exists($file)) {
					$input_errors[] = "$file " . gettext ('does not exists');
				}
			}
		}
    		if ( $post['topnumber'] != "" && ! is_numeric($post['topnumber']) ) {
        		$input_errors[] = gettext('The field') . " Top Number " . gettext('must be a numeric value');
    		}
	
		if ( $post['topurluser'] != "" && ! is_numeric($post['topurluser']) ) {
        		$input_errors[] = gettext('The field'). " Top URL User " . gettext('must be a numeric value');
    		}

		if ( ! preg_match("/^(\d+)(\w)$/", $post['frequency']) ) {
        		$input_errors[] = gettext('The field') . " Frequency " . gettext('must have a valid schedule definition');
    		}
	}
}

function sync_squidanalyzer() {
	global $g, $config;

	//get settings
	if (is_array($config['installedpackages']['squidanalyzer'])) {
		$settings = $config['installedpackages']['squidanalyzer']['config'][0];
	} else {
		sa_check_cron("remove");
		return;
	}
	if ($settings['saenable'] != "on") {
		sa_check_cron("remove");
		return;

	}
	if ($_POST['Submit'] == 'Force update now') {
		if ( file_exists(SACONFIG) ) {
			mwexec_bg("/usr/local/bin/squid-analyzer");
		}
	}

	if (($_POST['Submit'] == 'Save') || !isset($_POST['Submit'])) {
		$sa_topurluser = ($settings['topurluser'] ? $settings['topurluser'] : "10");
		$sa_topnumber = ($settings['topnumber'] ? $settings['topnumber'] : "100");
		$sa_useclientdns = ($settings['useclientdnsname'] == 'on') ? '1':'0';
		$sa_urlreport = ($settings['urlreport'] == 'on') ? '1':'0';
		$sa_userreport = ($settings['userreport'] == 'on') ? '1':'0';
		include("/usr/local/pkg/squidanalyzer.template");

		if (!file_exists('/usr/local/squidreport')) {
      			mwexec('mkdir /usr/local/squidreport');
		}

		// write config file
		file_put_contents(SACONFIG, $sa_config, LOCK_EX);

		// check schedule
		if (preg_match("/^(\d+)(\w)/", strtolower($settings['frequency']), $m)) {
			$cf = ($m[1] == 1 ? "*" : "*/{$m[1]}");
			switch ($m[2]) {
				case "m":
					sa_check_cron("create", $cf);
					break;
				case "d":
					sa_check_cron("create", "0", "0", $cf);
					break;
				case "h":
				default:
					sa_check_cron("create", "0", $cf);
					break;
			}
		} else {
			sa_check_cron("remove");
		}
		write_config("SquidAnalyzer save.");
	}
}
