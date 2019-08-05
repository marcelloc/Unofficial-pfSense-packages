<?php
/*
 * diag_speedtest.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2005 Bob Zoller (bob@kludgebox.com)
 * All rights reserved.
 *
 * originally based on m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

##|+PRIV
##|*IDENT=page-diagnostics-speedtest
##|*NAME=Diagnostics: Speedtest
##|*DESCR=Allow access to the 'Diagnostics: Speedtest' page.
##|*MATCH=diag_speedtest.php*
##|-PRIV

$allowautocomplete = true;
$pgtitle = array(gettext("Diagnostics"), gettext("Speedtest"));
require_once("guiconfig.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 3);
$do_speedtest = false;
$host = '';
$count = DEFAULT_COUNT;

if ($_POST) {
	unset($input_errors);
	unset($do_speedtest);

	$ipproto = $_REQUEST['ipproto'];
	if (($ipproto == "ipv4") && is_ipaddrv6($host)) {
		$input_errors[] = gettext("When using IPv4, the target host must be an IPv4 address or hostname.");
	}
	if (($ipproto == "ipv6") && is_ipaddrv4($host)) {
		$input_errors[] = gettext("When using IPv6, the target host must be an IPv6 address or hostname.");
	}

	if (!$input_errors) {
		if ($_POST) {
			$do_speedtest = true;
		}
		if (isset($_REQUEST['sourceip'])) {
			$sourceip = $_REQUEST['sourceip'];
		}
		$count = $_REQUEST['count'];
		if (preg_match('/[^0-9]/', $count)) {
			$count = DEFAULT_COUNT;
		}
	}
}

if ($do_speedtest) {
?>
	<script type="text/javascript">
	//<![CDATA[
	window.onload=function() {
		document.getElementById("speedtestCaptured").wrap='off';
	}
	//]]>
	</script>
<?php
	$ifscope = '';
	$command = "/usr/local/bin/speedtest-cli";
	if ($ipproto == "ipv6") {
		//$command .= "6";
		$ifaddr = is_ipaddr($sourceip) ? $sourceip : get_interface_ipv6($sourceip);
		if (is_linklocal($ifaddr)) {
			$ifscope = get_ll_scope($ifaddr);
		}
	} else {
		$ifaddr = is_ipaddr($sourceip) ? $sourceip : get_interface_ip($sourceip);
	}

	if ($ifaddr != "") {
		$srcip = "--source " . escapeshellarg($ifaddr);
		if (is_linklocal($host) && !strstr($host, "%") && !empty($ifscope)) {
			$host .= "%{$ifscope}";
		}
	}

	//$cmd = "{$command} {$srcip} -c" . escapeshellarg($count) . " " . escapeshellarg($host);
	$cmd = "{$command} {$srcip} --json --share";
	//echo "speedtest command: {$cmd}\n";
	$result = shell_exec($cmd);

	if (empty($result)) {
		$input_errors[] = sprintf(gettext('Host "%s" did not respond or could not be resolved.'), $cmd);
	}

}

include('head.inc');

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form(false);

$section = new Form_Section('Speedtest');
/*
$section->addInput(new Form_Input(
	'host',
	'*Hostname',
	'text',
	$host,
	['placeholder' => 'Hostname to speedtest']
));
*/
$section->addInput(new Form_Select(
	'ipproto',
	'*IP Protocol',
	$ipproto,
	['ipv4' => 'IPv4', 'ipv6' => 'IPv6']
));

$section->addInput(new Form_Select(
	'sourceip',
	'*Source address',
	$sourceip,
	array('' => gettext('Automatically selected (default)')) + get_possible_traffic_source_addresses(true)
))->setHelp('Select source address for the speedtest.');

/*
$section->addInput(new Form_Select(
	'count',
	'Maximum number of speedtests',
	$count,
	array_combine(range(1, MAX_COUNT), range(1, MAX_COUNT))
))->setHelp('Select the maximum number of speedtests.');
*/
$form->add($section);

$form->addGlobal(new Form_Button(
	'Submit',
	'Speedtest',
	null,
	'fa-rss'
))->addClass('btn-primary');

print $form;

if ($do_speedtest && !empty($result) && !$input_errors) {
?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext('Results')?></h2>
		</div>

		<div class="panel-body" align="center">
		<br/>
		<?php $json = json_decode($result,true)?>
			<a><img src="<?=$json['share']?>" alt="Speedtest Result"></a>
		<br/>
		</div>
	</div>
<?php
}

include('foot.inc');
