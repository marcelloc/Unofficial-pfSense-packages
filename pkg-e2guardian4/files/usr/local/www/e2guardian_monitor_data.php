<?php
/*
 * e2guardian_monitor_data.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2015 Rubicon Communications, LLC (Netgate)
 * Copyright (C) 2012-2017 Marcello Coutinho
 * Copyright (C) 2012-2014 Carlos Cesario <carloscesario@gmail.com>
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

require_once("guiconfig.inc");

global $config;
if (is_array($config['installedpackages']['e2guardianlog'])) {
	$e2glog = $config['installedpackages']['e2guardianlog']['config'][0];
} else {
	$e2glog = array();
}

function e2gm($field){
	global $filter;
	// Apply filter and color
	// Need validate special chars
	if ($filter != "") {
        	$field = preg_replace("@($filter)@i","<span><font color='red'>$1</font></span>", $field);
	}
	return $field;
}

/* Requests */
if ($_POST) {
	global $filter, $program, $e2glog;
	//var_dump($config['installedpackages']['e2guardianlog']);
	// Actions
	$filter = preg_replace('/(@|!|>|<)/', "", htmlspecialchars($_POST['strfilter']));
	$program = strtolower($_POST['program']);
	switch ($program) {
		case 'accesshead':
			// Show table headers
			switch($e2glog['logfileformat']) {
				case 1:
					show_tds(array("Date", "IP", "Method", "Url", "User", "Group", "Reason"));
					break;
				case 3:
                               		show_tds(array("Date", "IP", "Status", "Address", "User", "Destination"));
					break;
			}
		case 'access':
			// Define log file
			$log = '/var/log/e2guardian/access.log';
			// Fetch lines
			$logarr = fetch_log($log);
			switch($e2glog['logfileformat']) {
				case 1:
					//2017.6.24 3:30:40 172.17.26.4 127.0.0.1 https://logger.rm.uol.com.br/v1/?prd=98&disp=true&gps=true&grp=Origem:TM-code-injector;tm_repo_id:wrnitl&msr=Execucoes%20Iniciadas:1&CACHE_BUSTER=1498285785240 - GET 0 0 - 1 503 - - Default - - - - -
					//2017.6.21 23:52:00 lab 172.17.26.9 https://rdm.reamp.com.br:443 - CONNECT 1061 0 - 1 200 - - Default - - - - -
					//2017.6.24 1:33:32 lab 172.17.26.4 https://pixel-a.sitescout.com:443 *DENIED* MISSING TRANSLATION KEY CONNECT 0 0 SSL SITE 1 200 - - Default - - - - -
					//2017.6.24 4:13:56 172.17.26.4 127.0.0.1 https://hp.imguol.com.br/c/home/e5/2017/06/23/a-modelo-baiana-carol-caputo-e-apontada-como-novo-affair-de-neymar-1498249568434_80x80.jpg *DENIED* Express&atilde;o Regular proibida em URL: (girls|babes|bikini|model)+.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov) GET 0 0 Banned Regular Expression URLs 1 403 - - Default - - - - -
					//2017.6.24 4:14:34 172.17.26.4 127.0.0.1 https://www.youtube.com/results?search_query=xxx *DENIED* Encontrada combina&ccedil;&atilde;o de frases proibida: 250 : 2582 ((-health, penis)+(-news, report)+( porn, xxx)+( xxx,  porn)+( xxx,  sex )+(erotic,  porn)+(erotic,  xxx)+(naughty,  xxx )+(film, sex)+( film,  porno )+ girl +- health + penis + porn+ porno+ putaria+ sex + sexo + vagina+ xxx +-abandon+adult content+anal play+-cat +-documentary+-environment+erotic+erotica+-faq+-health+hot xxx+-main+naughty+new xxx+-pesquisa+porno+sex scene+sex video+sexy+uncensored+vagina+xxx girl+xxx movie+xxx porn+xxx sex+xxx video) GET 176290 2582 Pornography, Pornography (Portuguese), Pornografia, Pornography (Norwegian), Pornography (Spanish) 1 403 text/html - Default - - - - -

					foreach ($logarr as $logent) {
						//split log
						if (preg_match("/(\S+\s+\S+) (\S+) (\S+) (\S+) (.*) (GET|OPTIONS|POST|CONNECT) \d+ \d+ (.*) \d \d\d\d \S+ \S+ (\S+)/", $logent, $logline)) {

	                                                // Word wrap the URL
        	                                        $url = htmlentities($logline[4]);
        	                                        $logline[4] = preg_replace("@\<\>@","",$logline[4]);
                	                                $url = html_autowrap($url);
							
							$logline[5] = htmlentities($logline[5]);
                                                        $logline[5] = html_autowrap($logline[5]);

							echo "<tr valign='top'>\n";
                                       		        echo "<td class='listlr' nowrap='nowrap'>" . e2gm($logline[1]) . "</td>\n";
                                       	       		echo "<td class='listr'>" . e2gm($logline[3]) . "</td>\n";
                                                	echo "<td class='listr'>" . (preg_match("/DENIED/",$logline[5]) ? e2gm("DENIED") : e2gm($logline[6])) . "</td>\n";
                                               		echo "<td class='listr' title='{$logline[4]}' width='*'>" . e2gm(preg_replace("/(\?|;).*/","",$url)) . "</td>\n";
                                                	echo "<td class='listr'>" . e2gm($logline[2]) . "</td>\n";
                                                	echo "<td class='listr'>" . e2gm($logline[8]) . "</td>\n";
                                                	echo "<td class='listr'>" . e2gm($logline[7]) . "</td>\n";
                                                	echo "</tr>\n";
						}
					}

					break;
				case 3:
					// Print lines
					foreach ($logarr as $logent) {
						// Split line by space delimiter
						$logline = preg_split("/\s+/", $logent);

						// Word wrap the URL
						$logline[7] = htmlentities($logline[7]);
						$logline[7] = html_autowrap($logline[7]);

						// Remove /(slash) in destination row
						$logline_dest = preg_split("/\//", $logline[9]);

						// Apply filter and color
						// Need validate special chars
						if ($filter != "") {
							$logline = preg_replace("@($filter)@i","<span><font color='red'>$1</font></span>", $logline);
						}

						echo "<tr valign='top'>\n";
						echo "<td class='listlr' nowrap='nowrap'>" . e2gm("{$logline[0]} {$logline[1]}") . "</td>\n";
						echo "<td class='listr'>" . e2gm($logline[3]) . "</td>\n";
						echo "<td class='listr'>" . e2gm($logline[4]) . "</td>\n";
						echo "<td class='listr' width='*'>" . e2gm($logline[7]) . "</td>\n";
						echo "<td class='listr'>" . e2gm($logline[8]) . "</td>\n";
						echo "<td class='listr'>" . e2gm($logline_dest[1]) . "</td>\n";
						echo "</tr>\n";
					}
					break;
				default:
					print "e2guardian log format selected is not implemented yet";
					break;
			}
			break;
		case 'starthead';
			// Show table headers
			show_tds(array("Date-Time", "Message"));
			break;
		case 'start';
			// Define log file
			$log = '/var/log/e2guardian/start.log';
			// Fetch lines
			$logarr = fetch_log($log);
			foreach ($logarr as $logent) {
				// Split line by delimiter
				//Thu Jun 22 00:49:13 BRT 2017 start
				if (preg_match("@(.*) (start)@", $logent, $logline)) {

					// Word wrap the message
					$logline[1] = htmlentities($logline[1]);
					$logline[1] = html_autowrap($logline[1]);

					echo "<tr>\n";
					echo "<td class=\"listlr\" nowrap=\"nowrap\">{$logline[1]}</td>\n";
					echo "<td class=\"listr\" nowrap=\"nowrap\">{$logline[2]}</td>\n";
					echo "</tr>\n";
				}
			}
			break;
		}
}

/* Functions */
function html_autowrap($cont) {
	// split strings
	$p = 0;
	$pstep = 25;
	$str = $cont;
	$cont = '';
	for ($p = 0; $p < strlen($str); $p += $pstep) {
		$s = substr($str, $p, $pstep);
		if (!$s) {
			break;
		}
		$cont .= $s . "<wbr />";
	}
	return $cont;
}

// Show Squid Logs
function fetch_log($log) {
	global $filter, $program, $e2glog;
	$log = escapeshellarg($log);
	// Get data from form post
	$lines = escapeshellarg(is_numeric($_POST['maxlines']) ? $_POST['maxlines'] : 50);
	if (preg_match("/!/", htmlspecialchars($_POST['strfilter']))) {
		$grep_arg = "-iv";
	} else {
		$grep_arg = "-i";
	}

	// Check program to execute or no the parser
	if ($program == "access" && $e2glog['logfileformat'] == 3) {
		$parser = "| /usr/local/bin/php-cgi -q e2guardian_log_parser.php";
	} else {
		$parser = "";
	}

	// Get logs based in filter expression
	if ($filter != "") {
		exec("/usr/bin/tail -n 2000 {$log} | /usr/bin/grep {$grep_arg} " . escapeshellarg($filter). " | /usr/bin/tail -r -n {$lines} {$parser} ", $logarr);
	} else {
		exec("/usr/bin/tail -r -n {$lines} {$log} {$parser}", $logarr);
	}
	// Return logs
	return $logarr;
};

function show_tds($tds) {
	echo "<tr valign='top'>\n";
	foreach ($tds as $td){
		echo "<th class='listhdrr'>" . gettext($td) . "</th>\n";
	}
	echo "</tr>\n";
}

?>
