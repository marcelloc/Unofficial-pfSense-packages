<?php
/*
 * postfix.php
 *
 * part of Unofficial packages for pfSense(R) softwate
 * Copyright (c) 2011-2017 Marcello Coutinho
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

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("xmlrpc.inc");
require_once("xmlrpc_client.inc");
require_once("/usr/local/pkg/postfix.inc");

define('POSTFIX_DEBUG', '0');
$uname = posix_uname();
if ($uname['machine'] == 'amd64') {
        ini_set('memory_limit', '768M');
}

function get_remote_log() {
	global $config, $g, $postfix_dir;
	$curr_time = time();
	$log_time = date('YmdHis', $curr_time);

	if (is_array($config['installedpackages']['postfixsync'])) {
		$synctimeout = $config['installedpackages']['postfixsync']['config'][0]['synctimeout'] ?: '250';
		foreach ($config['installedpackages']['postfixsync']['config'][0]['row'] as $sh) {
			// Get remote data for enabled fetch hosts
			if ($sh['enabless'] && $sh['sync_type'] == 'fetch') {
				$sync_to_ip = $sh['ipaddress'];
				$port = $sh['syncport'];
				$username = $sh['username'] ?: 'admin';
				$password = $sh['password'];
				$protocol = $sh['syncprotocol'];
				$file = '/var/db/postfix/' . $server . '.sql';

				$error = '';
				$valid = TRUE;

				if ($password == "") {
					$error = "Password parameter is empty. ";
					$valid = FALSE;
				}
				if ($protocol == "") {
					$error = "Protocol parameter is empty. ";
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
					// Take care of IPv6 literal address
					if (is_ipaddrv6($sync_to_ip)) {
						$sync_to_ip = "[{$sync_to_ip}]";
					}
					$url = "{$protocol}://{$sync_to_ip}";
					if (POSTFIX_DEBUG > 4) {
						print "{$sync_to_ip} {$url}, {$port}\n";
					}
					$method = 'pfsense.exec_php';
					$execcmd  = "require_once('/usr/local/www/postfix.php');\n";
					$execcmd .= '$toreturn = get_sql('.$log_time.');';

					/* Assemble XMLRPC payload. */
					$params = array(XML_RPC_encode($password), XML_RPC_encode($execcmd));
					log_error("[postfix] Fetching sql data from {$sync_to_ip}.");
					$msg = new XML_RPC_Message($method, $params);
					$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
					$cli->setCredentials($username, $password);
					//$cli->setDebug(1);
					$resp = $cli->send($msg, $synctimeout);
					$a = $resp->value();
					$errors = 0;
					//var_dump($sql);
					foreach($a as $b) {
						foreach ($b as $c) {
							foreach ($c as $d) {
								foreach ($d as $e) {
									$update = unserialize($e['string']);
									if (POSTFIX_DEBUG > 4) {
										print $update['day'] . "\n";
									}
									if ($update['day'] != "") {
										create_db($update['day'] . ".db");
										if ($debug) {
											print $update['day'] . " writing from remote system to db...";
										}
										$dbhandle = new SQLite3($postfix_dir . '/' . $update['day'] . ".db");
										//file_put_contents("/tmp/" . $key . '-' . $update['day'] . ".sql", gzuncompress(base64_decode($update['sql'])), LOCK_EX);
										$dbhandle->exec(gzuncompress(base64_decode($update['sql'])));
										/*if (!$ok) {
											$errors++;
											die ("Cannot execute query. $error\n".$update['sql']."\n");
										} elseif ($debug) {
											print "ok\n";
										}*/
										$dbhandle->close();
									}
								}
							}
						}
					}
					if ($errors == 0) {
						$method = 'pfsense.exec_php';
						$execcmd  = "require_once('/usr/local/www/postfix.php');\n";
						$execcmd .= 'flush_sql('.$log_time.');';
						/* Assemble XMLRPC payload. */
						$params = array(XML_RPC_encode($password), XML_RPC_encode($execcmd));
						log_error("[postfix] Flushing sql buffer file from {$sync_to_ip}.");
						$msg = new XML_RPC_Message($method, $params);
						$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
						$cli->setCredentials($username, $password);
						//$cli->setDebug(1);
						$resp = $cli->send($msg, $synctimeout);
					}
				} else {
					log_error("[postfix] Fetch sql database from '{$sync_to_ip}' aborted due to the following error(s): {$error}");
				}
			}
		}
		log_error("[postfix] Fetch sql database completed.");
	}
}

function get_sql($log_time) {
	global $config, $xmlrpc_g;
	$server = $_SERVER['REMOTE_ADDR'];

	if (is_array($config['installedpackages']['postfixsync'])) {
		foreach($config['installedpackages']['postfixsync']['config'][0]['row'] as $sh) {
			$sync_to_ip = $sh['ipaddress'];
			$sync_type = $sh['sync_type'];
			$password = $sh['password'];
			$file = '/var/db/postfix/' . $server . '.sql';
			if ($sync_to_ip == "{$server}" && $sync_type == "share" && file_exists($file)) {
				rename($file, $file . ".$log_time");
				return (file($file . ".$log_time"));
			}
		}
		return "";
	}
}

function flush_sql($log_time) {
	if (preg_match("/\d+\.\d+\.\d+\.\d+/", $_SERVER['REMOTE_ADDR'])) {
		unlink_if_exists('/var/db/postfix/' . $_SERVER['REMOTE_ADDR'] . ".sql.{$log_time}");
	}
}

function read_sid_db() {
	global $sa;
	$sql="select sid,db from sid_date;";
	$file="postfix_sid.db";
	$sids=postfix_read_db($sql,$file,'sid_location');
	if (count($sids) == 0 && POSTFIX_DEBUG > 0){
		print "base vazia...\n";
	}
	foreach($sids as $l){
		$sa[$l['sid']]=$l['db'];
	}
}

function check_sid_day($sid,$grep_day){
	global $sa;
	$file="postfix_sid.db";
	$sql="select db from sid_date where sid='{$sid}'";
	if (POSTFIX_DEBUG > 1) {
		print "verificando $sid $grep_day *******\n";
	}
	if (isset ($sa[$sid]) && $sa[$sid] != "") { 
		if (POSTFIX_DEBUG > 1) {
			print "CACHE_HIT $sid $grep_day *******\n";
		}
	} else {
		// verify or assign sid database to be sure logs are updated
	        // on the same database that message belongs to
		$sids=postfix_read_db($sql,$file,'sid_location');
		if (count($sids) == 0) {
			if (POSTFIX_DEBUG > 1) {
				print "gravando sid $sid $grep_day...\n";
			}
			$sql="insert into sid_date ('sid','db') values ('" . $sid . "','" . $grep_day . "');";
			postfix_write_db($sql,$file);
			$sa[$sid]=$grep_day;
		} else {
			//define sid db on array to do not open sid db on every log line
			if (POSTFIX_DEBUG > 1) {
				print "sid $sid no banco";
			}
			$sa[$sid]=$sids['db'];
			if (POSTFIX_DEBUG > 4) {
				var_dump($sa[$sid]);
			}
		}
	}
}

function grep_log(){
	global $postfix_dir,$postfix_arg,$config,$g,$sa,$argv;

	$total_lines=0;
	$days=array();
	$stm_queue=array();
        $stm_noqueue=array();
	$stm_update_sa="";
	$grep="(MailScanner|postfix.cleanup|postfix.smtp|postfix.error|postfix.qmgr|postfix.postscreen)";
	$curr_time = time();
	$log_time=strtotime($postfix_arg['time'],$curr_time);
	$message_status_preconfigured_regex = "/(spam|bounced|deferred|reject|sent|hold|incoming)/";

	// file grep loop
        if ($argv[2]!= ""){
                 $maillog_filename = $argv[2];
	} else {
                $maillog_filename = "/var/log/maillog";
        }
	if (POSTFIX_DEBUG > 0) {
        	echo " checking $maillog_filename ...\n";
	}

	foreach ($postfix_arg['grep'] as $grep_array) {
		$grep=$grep_array['s'];
		$grep_day=$grep_array['d'];
		if (!file_exists($maillog_filename) || !is_readable($maillog_filename)) {
			continue;
		}
		if (POSTFIX_DEBUG > 0) {
			print "/usr/bin/grep -E '^{$grep}' {$maillog_filename}\n";
		}
	  	$lists=array();
	  	exec("/usr/bin/grep -E " . escapeshellarg("^{$grep}") . " {$maillog_filename}", $lists);
	  	foreach ($lists as $line) {
			$status=array();
			$total_lines++;
			#Nov  8 09:31:50 srvch011 postfix/smtpd[43585]: 19C281F59C8: client=pm03-974.auinmem.br[177.70.0.3]
			if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.smtpd\W\d+\W+(\w+): client=(.*)/",$line,$email)) {
				check_sid_day($email[3],$grep_day);
				$values="'".$email[3]."','".$email[1]."','".$email[2]."','".$email[4]."'";
				if(${$email[3]}!=$email[3]) {
					$stm_queue[$sa[$email[3]]] .= 'insert or ignore into mail_from(sid,date,server,client) values ('.$values.');'."\n";
					}
				${$email[3]}=$email[3];
			}
			#Dec  2 22:21:18 pfsense MailScanner[60670]: Requeue: 8DC3BBDEAF.A29D3 to 5AD9ABDEB5
			#Apr 01 05:49:05 smgsc2 MailScanner[48506]: Requeue: 4C256286A6E.A2F06 to E89B5286BD6
			else if (preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) MailScanner.*Requeue: (\w+)\W\w+ to (\w+)/",$line,$email)) {
				check_sid_day($email[3],$grep_day);
				check_sid_day($email[4],$sa[$email[3]]);
				if (POSTFIX_DEBUG > 2) {
					print "REQUEUE {$email[3]} {$email[4]} grep_day{$grep_day} e3_day {$sa[$email[3]]} e4_day {$sa[$email[3]]}\n";
				}
				$stm_update_sa .= "update or ignore sid_date set sid='".$email[4]."' where sid='".$email[3]."';\n";
				$stm_queue[$sa[$email[3]]].= "update or ignore mail_from set sid='".$email[4]."' where sid='".$email[3]."';\n";
				//make sure db will not have duplicate entries with old and new sid
				$stm_queue[$sa[$email[3]]].= "delete from mail_to where from_id in (select id from mail_from where sid='".$email[3]."');\n";
				$stm_queue[$sa[$email[3]]].= "delete from mail_from  where sid='".$email[3]."';\n";
			}
			#Apr 05 03:32:20 zonk MailScanner[4802]: Message 8193A1496BBE.AE19A from 195.62.13.253 (info@testdomain.com) to wwtest.com is spam, SpamAssassin (nicht zwischen gespeichert, Wertung=13.592, benoetigt 3, BAYES_50 0.80, BLACKLIST_SOURCE_COUNTRY 0.50, DIGEST_MULTIPLE 0.29, DKIM_SIGNED 0.10, DKIM_VALID -0.10, DKIM_VALID_AU -0.10, FROM_IS_REPLY_TO -0.50, HS_HEADER_821 5.00, PYZOR_CHECK 1.39, RAZOR2_CF_RANGE_51_100 0.50, RAZOR2_CF_RANGE_E8_51_100 1.89, RAZOR2_CHECK 0.92, RP_MATCHES_RCVD -0.10, SPF_PASS -0.00, ZONK_21394 3.00)
			#Dec  5 14:06:10 srvchunk01 MailScanner[19589]: Message 775201F44B1.AED2C from 209.185.111.50 (marcellocoutinho@mailtest.com) to sede.mail.test.com is spam, SpamAssassin (not cached, escore=99.202, requerido 6, autolearn=spam, DKIM_SIGNED 0.10, DKIM_VALID -0.10, DKIM_VALID_AU -0.10, FREEMAIL_FROM 0.00, HTML_MESSAGE 0.00, RCVD_IN_DNSWL_LOW -0.70, WORM_TEST2 100.00)
			else if (preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) MailScanner\W\d+\W+\w+\s+(\w+).* is spam, (.*)/",$line,$email)) {
				check_sid_day($email[3],$grep_day);
				if (POSTFIX_DEBUG > 3) {
					print "\n#######################################\nSPAM:".$email[4].$email[3].$email[2]."\n#######################################\n";
				}
				$stm_queue[$sa[$email[3]]] .= "update or ignore mail_to set status=(select id from mail_status where info='spam'), status_info='".preg_replace("/(\<|\>|\s+|\'|\")/"," ",$email[4])."' where from_id in (select id from mail_from where sid='".$email[3]."' and server='".$email[2]."');\n";
			}
			#Nov 14 09:29:32 srvch011 postfix/error[58443]: 2B8EB1F5A5A: to=<hildae.sva@pi.email.com>, relay=none, delay=0.66, delays=0.63/0/0/0.02, dsn=4.4.3, status=deferred (delivery temporarily suspended: Host or domain name not found. Name service error for name=mail.pi.test.com type=A: Host not found, try again)
			#Nov  3 21:45:32 srvch011 postfix/smtp[18041]: 4CE321F4887: to=<viinil@vitive.com.br>, relay=smtpe1.eom[81.00.20.9]:25, delay=1.9, delays=0.06/0.01/0.68/1.2, dsn=2.0.0, status=sent (250 2.0.0 Ok: queued as 2C33E2382C8)
			#Nov 16 00:00:14 srvch011 postfix/smtp[7363]: 7AEB91F797D: to=<alessandra.bueno@mg.test.com>, relay=mail.mg.test.com[172.25.3.5]:25, delay=39, delays=35/1.1/0.04/2.7, dsn=5.7.1, status=bounced (host mail.mg.test.com[172.25.3.5] said: 550 5.7.1 Unable to relay for alessandra.bueno@mg.test.com (in reply to RCPT TO command))
			else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.\w+\W\d+\W+(\w+): to=\<(.*)\>, relay=(.*), delay=([0-9,.]+), .* dsn=([0-9,.]+), status=(\w+) (.*)/",$line,$email)) {
				check_sid_day($email[3],$grep_day);
				if ( ! preg_match($message_status_preconfigured_regex , $email[8])) {
					$stm_queue[$sa[$email[3]]].= "insert or ignore into mail_status (info) values ('".$email[8]."');\n";
				}
				$stm_queue[$sa[$email[3]]].= "insert or ignore into mail_to (from_id,too,status,status_info,relay,delay,dsn) values ((select id from mail_from where sid='".$email[3]."' and server='".$email[2]."'),'".strtolower($email[4])."',(select id from mail_status where info='".$email[8]."'),'".preg_replace("/(\<|\>|\s+|\'|\")/"," ",$email[9])."','".$email[5]."','".$email[6]."','".$email[7]."');\n";
				//update status to sent only if it's not a spam message
				$stm_queue[$sa[$email[3]]] .= "update or ignore mail_to set dsn='{$email[7]}', delay='{$email[6]}', relay='{$email[5]}', too='" . strtolower($email[4]);
				$stm_queue[$sa[$email[3]]] .= "' where from_id in (select id from mail_from where sid='{$email[3]}' and server='{$email[2]}');\n";
				$stm_queue[$sa[$email[3]]] .= "update or ignore mail_to set status=(select id from mail_status where info='{$email[8]}'), status_info='" . preg_replace("/(\<|\>|\s+|\'|\")/"," ",$email[9]);
				$stm_queue[$sa[$email[3]]] .= "' where from_id in (select id from mail_from where sid='{$email[3]}' and server='{$email[2]}') and status !=(select id from mail_status where info='spam');\n";
			}
			#Nov 13 01:48:44 srvch011 postfix/cleanup[16914]: D995B1F570B: message-id=<61.40.11745.10E3FBE4@ofertas6>
			else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.cleanup\W\d+\W+(\w+): message-id=\<(.*)\>/",$line,$email)) {
				check_sid_day($email[3],$grep_day);
				$stm_queue[$sa[$email[3]]].="update mail_from set msgid='".$email[4]."' where sid='".$email[3]."';\n";
			}
			#Nov 14 02:40:05 srvch011 postfix/qmgr[46834]: BC5931F4F13: from=<ceag@mx.crmcom.br>, size=32727, nrcpt=1 (queue active)
			else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.qmgr\W\d+\W+(\w+): from=\<(.*)\>\W+size=(\d+)/",$line,$email)){
				check_sid_day($email[3],$grep_day);
				$stm_queue[$sa[$email[3]]].= "update mail_from set fromm='".strtolower($email[4])."', size='".$email[5]."' where sid='".$email[3]."';\n";
			}
			#Nov 13 00:09:07 srvch011 postfix/bounce[56376]: 9145C1F67F7: sender non-delivery notification: D5BD31F6865
			#else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.bounce\W\d+\W+(\w+): sender non-delivery notification: (\w+)/",$line,$email)){
			#	$stm_queue[$day].= "update mail_queue set bounce='".$email[4]."' where sid='".$email[3]."';\n";
			#}
			#Nov 14 01:41:44 srvch011 postfix/smtpd[15259]: warning: 1EF3F1F573A: queue file size limit exceeded
	  		else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.smtpd\W\d+\W+warning: (\w+): queue file size limit exceeded/",$line,$email)){
				check_sid_day($email[3],$grep_day);
				$stm_queue[$sa[$email[3]]].= "update mail_to set status=(select id from mail_status where info='reject'), status_info='queue file size limit exceeded' where from_id in (select id from mail_from where sid='".$email[3]."' and server='".$email[2]."');\n";
			}
			#Apr 25 10:00:11 smgsc2 postfix/cleanup[19867]: EF0302869D0: info: header Subject: New message for you from asmg.test.com[200.98.16.4]; from=<notificacao@test.com> to=<user.sirname@corp.com> proto=SMTP helo=<alfa.test.com>
			#Nov  9 02:14:57 srvch011 postfix/cleanup[6856]: 617A51F5AC5: warning: header Subject: Mapeamento de Processos from lxalpha.12b.com.br[66.109.29.225]; from=<apache@lxalpha.12b.com.br> to=<ritiele.faria@mail.test.com> proto=ESMTP helo=<lxalpha.12b.com.br>
			#Nov  8 09:31:50 srvch011 postfix/cleanup[11471]: 19C281F59C8: reject: header From: "Giuliana Flores - Parceiro do Grupo Virtual" <publicidade@parceiro-grupovirtual.com.br> from pm03-974.auinmeio.com.br[177.70.232.225]; from=<publicidade@parceiro-grupovirtual.com.br> to=<jorge.lustosa@mail.test.com> proto=ESMTP helo=<pm03-974.auinmeio.com.br>: 5.7.1 [SN007]
			#Nov 13 00:03:24 srvch011 postfix/cleanup[4192]: 8A5B31F52D2: reject: body http://platform.roastcrack.info/mj0ie6p-48qtiyq from move2.igloojack.info[173.239.63.16]; from=<ljmd6u8lrxke4@move2.igloojack.info> to=<edileva@aasdf..br> proto=SMTP helo=<move2.igloojack.info>: 5.7.1 [BD040]
			#Nov 14 01:41:35 srvch011 postfix/cleanup[58446]: 1EF3F1F573A: warning: header Subject: =?windows-1252?Q?IMOVEL_Voc=EA_=E9_um_Cliente_especial_da_=93CENTURY21=22?=??=?windows-1252?Q?Veja_o_que_tenho_para_voc=EA?= from mail-yw0-f51.google.com[209.85.213.51]; from=<sergioalexandre6308@gmail.com> to=<sinza@tr.br> proto=ESMTP helo=<mail-yw0-f51.google.com>
			else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.cleanup\W\d+\W+(\w+): (\w+): (.*) from ([a-z,A-Z,0-9,.,-]+)\W([0-9,.]+)\W+from=\<(.*)\> to=\<(.*)\>.*helo=\W([a-z,A-Z,0-9,.,-]+)(.*)/",$line,$email)){
				check_sid_day($email[3],$grep_day);
				$status['date'] = $email[1];
				$status['server'] = $email[2];
				$status['sid'] = $email[3];
				$status['remote_hostname'] = $email[6];
				$status['remote_ip'] = $email[7];
				$status['from'] = $email[8];
				$status['to'] = $email[9];
				$status['helo'] = $email[10];
				$status['status'] = $email[4];
				if ( ! preg_match($message_status_preconfigured_regex , $email[4])) {
					$stm_queue[$sa[$email[3]]] .= "insert or ignore into mail_status (info) values ('".$email[4]."');\n";
				}
				if ($email[4] == "info" || $email[4] == "warning" ) {
					if (${$status['sid']}=='hold') {
						$status['status']='hold';
					} else {
						$status['status']='incoming';
					}
					#print "$line\n";
					$status['status_info']=preg_replace("/(\<|\>|\s+|\'|\")/"," ",$email[11]);
					$status['subject']=preg_replace("/header Subject: /","",$email[5]);
					$status['subject']=preg_replace("/(\<|\>|\s+|\'|\")/"," ",$status['subject']);
					$stm_queue[$sa[$email[3]]].="update mail_from set subject='".$status['subject']."', fromm='".strtolower($status['from'])."',helo='".$status['helo']."' where sid='".$status['sid']."';\n";
					$stm_queue[$sa[$email[3]]].="insert or ignore into mail_to (from_id,too,status,status_info) VALUES ((select id from mail_from where sid='".$email[3]."' and server='".$email[2]."'),'".strtolower($status['to'])."',(select id from mail_status where info='".$status['status']."'),'".$status['status_info']."');\n";
					$stm_queue[$sa[$email[3]]].="update or ignore mail_to set status=(select id from mail_status where info='".$status['status']."'), status_info='".$status['status_info']."', too='".strtolower($status['to'])."' where from_id in (select id from mail_from where sid='".$status['sid']."' and server='".$email[2]."');\n";
				} else {
					${$status['sid']}=$status['status'];
					$stm_queue[$sa[$email[3]]].="update mail_from set fromm='".strtolower($status['from'])."',helo='".$status['helo']."' where sid='".$status['sid']."';\n";
					$status['status_info']=preg_replace("/(\<|\>|\s+|\'|\")/"," ",$email[5].$email[11]);
					$stm_queue[$sa[$email[3]]].="insert or ignore into mail_to (from_id,too,status,status_info) VALUES ((select id from mail_from where sid='".$email[3]."' and server='".$email[2]."'),'".strtolower($status['to'])."',(select id from mail_status where info='".$email[4]."'),'".$status['status_info']."');\n";
					$stm_queue[$sa[$email[3]]].="update or ignore mail_to set status=(select id from mail_status where info='".$email[4]."'), status_info='".$status['status_info']."', too='".strtolower($status['to'])."' where from_id in (select id from mail_from where sid='".$status['sid']."' and server='".$email[2]."');\n";
				}
			}
			#Apr  5 00:58:45 zonk postfix/postscreen[87711]: NOQUEUE: reject: RCPT from [103.230.152.124]:21574: 450 4.7.1 Service unavailable; client [103.230.152.124] blocked using zen.spamhaus.org; from=<walterpaulflechtner@excite.fr>, to=<litigates@janus-tv.de>, proto=ESMTP, helo=<[103.230.152.124]>
			#Apr  6 00:00:00 zonk postfix/smtpd[96233]: NOQUEUE: reject: RCPT from mx6.test.com[217.182.51.86]: 550 5.1.1 <nvcvgegax@test.de>: Recipient address rejected: User unknown in relay recipient table; from=<tekavqrqx_mz3@test.com> to=<nvcvgegax@test.de> proto=ESMTP helo=<mx6.test.com>
			#Apr  5 00:05:40 zonk postfix/postscreen[87711]: NOQUEUE: reject: RCPT from [191.101.155.227]:60197: 450 4.7.1 Service unavailable; client [191.101.155.227] blocked using zen.spamhaus.org; from=<service@test.de>, to=<user.sirname@test.de>, proto=ESMTP, helo=<itcorhost.ru>
			#Nov  9 02:14:34 srvch011 postfix/smtpd[38129]: NOQUEUE: reject: RCPT from unknown[201.36.0.7]: 450 4.7.1 Client host rejected: cannot find your hostname, [201.36.98.7]; from=<maladireta@esadcos.com.br> to=<sexec.09vara@go.domain.test.com> proto=ESMTP helo=<capri0.wb.com.br>
			else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) (\S+) postfix.(postscreen|smtpd)\W\d+\W+NOQUEUE:\s+(\w+): (.*); from=\<(.*)\>\W+to=\<(.*)\>.*helo=\<(.*)\>/",$line,$email)){
				$status['date']=$email[1];
				$status['server']=$email[2];
				if (preg_match("/Service currently unavailable/",$email[5])) {
        	                        $status['status'] = "soft bounce";
	                        } else {
                	                $status['status'] = $email[4];
                        	}
				$status['status_info']=preg_replace("/;/","",$email[5]);
				$status['from']=$email[6];
				$status['to']=$email[7];
				$status['helo']=$email[8];
				$values="'".$status['date']."','".$status['status']."','".$status['status_info']."','".strtolower($status['from'])."','".strtolower($status['to'])."','".$status['helo']."','".$status['server']."'";
				//log without queue, must use curr_time info instead of sid database
				$day=date("Y-m-d",strtotime($postfix_arg['time'],$curr_time));
				if (POSTFIX_DEBUG > 4) {
					print "\n $day {$postfix_arg['time']} $curr_time\n*****************\n";
				}
				$stm_noqueue[$day] .= 'insert or ignore into mail_noqueue(date,status,status_info,fromm,too,helo,server) values (' . $values . ');' . "\n";
			}
			if ($total_lines%1500 == 0){
				if (POSTFIX_DEBUG > 0) {
					print "Save partial logs in database($line)...\n";
				}
				//foreach($stm_noqueue as $noqueue) {
				//	$tstmq=split(";",$noqueue);
				//	var_dump($tstmq);
				//}
				if (POSTFIX_DEBUG > 4) {
					var_dump($stm_noqueue);
					var_dump($stm_queue);
				}
				write_db($stm_noqueue,"noqueue");
				write_db($stm_queue,"from");
				$stm_noqueue=array();
			        $stm_queue=array();

			}
		}
	#save log in database
	if (POSTFIX_DEBUG > 4) {
		var_dump($stm_noqueue);
        	var_dump($stm_queue);
	}
	write_db($stm_noqueue,"noqueue");
	write_db($stm_queue,"from");
	$stm_noqueue=array();
	$stm_queue=array();
	}

	$config=parse_xml_config("{$g['conf_path']}/config.xml", $g['xml_rootobj']);
        //print count($config['installedpackages']);
        //start db replication if configured
        if ($config['installedpackages']['postfixsync']['config'][0]['rsync']) {
                foreach ($config['installedpackages']['postfixsync']['config'] as $rs ) {
                        foreach($rs['row'] as $sh) {
                                $sync_to_ip = $sh['ipaddress'];
                                $sync_type = $sh['sync_type'];
                                $password = $sh['password'];
				if (POSTFIX_DEBUG > 2) {
                                	print "checking replication to $sync_to_ip...";
				}
                                if ($password && $sync_to_ip && preg_match("/(both|database)/",$sync_type)) {
                                        postfix_do_xmlrpc_sync($sync_to_ip, $password,$sync_type);
				}
				if (POSTFIX_DEBUG > 2) {
                                	print "ok\n";
				}
			}
		}
	}

}

function write_db($stm, $table) {
	global $postfix_dir, $config, $g;
	$do_sync = array();
	if (POSTFIX_DEBUG > 0) {
		print "writing to database...";
	}
	foreach ($stm as $day => $sql) {
		if ((strlen($day) > 8)) {
			if ($config['installedpackages']['postfixsync']['config'][0]) {
				foreach ($config['installedpackages']['postfixsync']['config'] as $rs) {
					foreach($rs['row'] as $sh) {
						$sync_to_ip = $sh['ipaddress'];
						$sync_type = $sh['sync_type'];
						$password = $sh['password'];
						$sql_file = '/var/db/postfix/' . $sync_to_ip . '.sql';
						${$sync_to_ip} = "";
						if (file_exists($sql_file)) {
							${$sync_to_ip} = file_get_contents($sql_file);
						}
						if ($sync_to_ip && $sync_type == "share") {
							${$sync_to_ip} .= serialize(array('day' => $day, 'sql' => base64_encode(gzcompress($stm[$day] . "COMMIT;", 9)))) . "\n";
							if (!in_array($sync_to_ip, $do_sync)) {
								$do_sync[] = $sync_to_ip;
							}
						}
					}
				}
			}
			/* Write local db file */
			if ($debug) {
				print "writing to local db $day...";
			}
			postfix_write_db($stm,$day.".db");
			//file_put_contents("/tmp/" . $key . '-' . $update['day'] . ".sql", gzuncompress(base64_decode($update['sql'])), LOCK_EX);
		}
	}
	/* Write updated sql files */
	if (count($do_sync) > 0 ) {
		foreach ($do_sync as $ip) {
			file_put_contents('/var/db/postfix/' . $ip . '.sql', ${$ip}, LOCK_EX);
		}
	}
	/* Write local file */
}

#############################################################################
/* read postfix DB into array */
function postfix_read_db($query,$file,$db_type='daily_db') {
        $postfixdb = array();
        $DB = postfix_opendb($file,$db_type);
        if ($DB) {
                $response = $DB->query($query);
                if ($response != FALSE) {
                        while ($row = $response->fetchArray()) {
                                $postfixdb[] = $row;
			}
			$DB->close();
                } else {
                        print "Trying to read DB returned error: {$DB->lastErrorMsg()}";
		}
        }
        return $postfixdb;
}

function postfix_opendb($file,$db_type='daily_db') {
        global $g,$postfix_dir,$postfix_arg;

	if (POSTFIX_DEBUG > 2) {
		print "{$postfix_dir}{$file}\n";
	}
        if (! is_dir($postfix_dir)) {
                mkdir($postfix_dir,0775);
        }
	include("/usr/local/www/postfix.sql.php");
        $DB = new SQLite3($postfix_dir.$file);
        if ($DB->exec($db_stm[$db_type] . ";")) {
                return $DB;
	}

}


function postfix_write_db($queries,$file) {
        global $g;
        if (is_array($queries)){
                $query = implode(";", $queries).";";
                $query=preg_replace("/;;/",";",$query);
                }
        else
                $query = $queries;

        if(POSTFIX_DEBUG > 0) {
                file_put_contents("/tmp/cp.txt","postfix write db call($file)\n$query\n",FILE_APPEND);
	}
        $DB = postfix_opendb($file);
        if ($DB) {
                $DB->exec("BEGIN TRANSACTION");
                $result = $DB->exec($query);
                if (!$result)
                        print "Trying to modify DB returned error: {$DB->lastErrorMsg()}\n";
                else
                        $DB->exec("END TRANSACTION");
                $DB->close();
                return $result;
        } else{
                if(POSTFIX_DEBUG > 0) {
                        file_put_contents("/tmp/cp.txt","Failed to open postfix db file({$file})\n",FILE_APPEND);
		}
                return true;
        }
}

#############################################################################

$postfix_dir="/var/db/postfix/";
$curr_time = time();
#console script call
if ($argv[1]!=""){
if (POSTFIX_DEBUG > 0) {
	print "Inciando leitura do log...\n";
}
$sa=array();
read_sid_db();
$m[0]="/^(\w+)\s0/";
$r[0]="$1 (0| )";
$postfix_arg=array();
switch ($argv[1]){
        case "01min":
                $postfix_arg=array(     'grep' => array( array( 's' => preg_replace($m,$r,date("M d H:i",strtotime('-1min',$curr_time))),
								'd' => date("Y-m-d",strtotime('-1min',$curr_time)))),
                                        'time' => '-1 min');
                break;
        case "10min":
                $postfix_arg=array(     'grep' => array( array( 's' => substr(date("M d H:i",strtotime('-10 min',$curr_time),0,-1)),
								'd' =>  date("Y-m-d",strtotime('-10min',$curr_time)))),
                                        'time' => '-10 min');
                break;
        case "01hour":
                $postfix_arg[]=array(     'grep' => array(array('s' => preg_replace($m,$r,date("M d H:",strtotime('-01 hour',$curr_time))),
							  	'd' => date("Y-m-d",strtotime('-10min',$curr_time))),
							  array('s'=> preg_replace($m,$r,date("M d H:",strtotime('-1min',$curr_time))),
								'd' => date("Y-m-d",strtotime('-10min',$curr_time)))),
                                           'time' => '-01 hour');

                break;
        case "04hours":
                $postfix_arg=array(     'grep' => array(array('s'=>preg_replace($m,$r,date("M d H:",strtotime('-04 hour',$curr_time))),
								'd' => date("Y-m-d",strtotime('-04 hour',$curr_time))),
						  	array('s'=>preg_replace($m,$r,date("M d H:",strtotime('-03 hour',$curr_time))),
								'd' => date("Y-m-d",strtotime('-03 hour',$curr_time))),
							array('s'=>preg_replace($m,$r,date("M d H:",strtotime('-02 hour',$curr_time))),
								'd' => date("Y-m-d",strtotime('-01 hour',$curr_time))),
							array('s'=>preg_replace($m,$r,date("M d H:",strtotime('-01 hour',$curr_time))),
								'd' => date("Y-m-d",strtotime('-01 hour',$curr_time))),
							array('s'=>preg_replace($m,$r,date("M d H:",strtotime('-1min',$curr_time))),
								'd' => date("Y-m-d",strtotime('-01 min',$curr_time)))),
                                        'time' => '-04 hour');
                break;
	case "today":
		$postfix_arg=array( 	'grep' => create_grep(0,$m,$r,$curr_time),
					'time' => '-01 min');
		break;
	case "yesterday":
		$postfix_arg=array(     'grep' => create_grep(1,$m,$r,$curr_time),
                                        'time' => '-01 day');
                break;
	default:
		if (preg_match("/(\d)day(s|)/",$argv[1],$match)) {
			$postfix_arg=array(     'grep' => create_grep($match[1],$m,$r,$curr_time),
                                        	'time' => "-0{$match[1]} days");

		} else {
			die ("invalid parameters\n\nValid arguments are: 01min 10min 01hour 04hours today 1day 2days 3days,...\n");
		}
		break;
}
//var_dump($postfix_arg);
//exit;
$postfix_arg['argv']=$argv[1];
# get remote log from remote server
get_remote_log();
# get local log from logfile
grep_log();
mwexec_bg('/usr/local/bin/php -q /usr/local/www/postfix_cloud_domains.php');
}

function create_grep($days,$m,$r,$curr_time){
	$hours=array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
	$return=array();
	$time="-0{$days} day";
	while ($days > -1){
		$time="-0{$days} day";
		if (POSTFIX_DEBUG > 2) {
			print "time $time\n";
		}
		foreach ($hours as $hr) {
			//print $days. " -- ". date("G",$curr_time) . "\n";
			if ($days == 0 && $hr > date("G",$curr_time)) {
				break;
			}
			$return[]=array('s'=>preg_replace($m,$r,date("M d {$hr}:",strtotime($time,$curr_time))),
					'd'=>date("Y-m-d",strtotime($time,$curr_time)));
		}
		$days--;
	}
	return($return);
}

// http client call
if ($_REQUEST['files']!= ""){
	#do search
	$fields1  = "date,'' as sid,fromm,too,'' as size,'' as subject,helo,status,status_info as info,'' as relay,'' as dsn,'' as server,";
	$fields1 .= "'' as delay, '' as msgid, '' as bounce, 'NOQUEUE' as log";
  	$stm1 = "select {$fields1} from mail_noqueue where sid = ''  ";

   	$fields2  = "date,sid,fromm,too,size,subject,helo,mail_status.info as status,status_info as info,relay,dsn,mail_from.server as server,";
	$fields2 .= "delay,msgid,bounce,'QUEUE' as log";
  	$stm2 = "select {$fields2} from mail_from, mail_to ,mail_status where mail_from.id=mail_to.from_id and mail_to.status=mail_status.id ";

	$next = " and ";
	$limit_prefix=(preg_match("/\d+/",$_REQUEST['limit']) ? "limit " : "" );
	$limit=(preg_match("/\d+/",$_REQUEST['limit']) ? $_REQUEST['limit'] : "" );
	$files= explode(",", $_REQUEST['files']);
	$stm_fetch=array();
	$total_result=0;
	if ($_REQUEST['from']!= ""){
		if (preg_match('/\*/',$_REQUEST['from'])) {
			$stm .= $next . "fromm like '".preg_replace('/\*/','%',$_REQUEST['from']) . "'";
		} else {
			$stm .= $next . "fromm in('" . preg_replace("/\s+/","','",$_REQUEST['from']) . "')";
		}
	}
	if ($_REQUEST['to']!= ""){
		if (preg_match('/\*/',$_REQUEST['to'])) {
			$stm .= $next . "too like '" . preg_replace('/\*/','%',$_REQUEST['to']) . "'";
		} else {
			$stm .= $next . "too in('" . preg_replace("/\s+/","','",$_REQUEST['to']) . "')";
		}
	}
	if ($_REQUEST['sid']!= ""){
		$stm .= $next . "sid in('" . preg_replace("/\s+/","','",$_REQUEST['sid']) . "')";
	}
	if ($_REQUEST['relay']!= ""){
		if (preg_match('/\*/',$_REQUEST['subject'])) {
			$stm .= $next . "relay like '".preg_replace('/\*/','%',$_REQUEST['relay']) . "'";
		} else {
			$stm .= $next . "relay = '".$_REQUEST['relay'] . "'";
		}
	}
	if ($_REQUEST['subject']!= ""){
		if (preg_match('/\*/',$_REQUEST['subject'])) {
			$stm .= $next . "subject like '" . preg_replace('/\*/','%',$_REQUEST['subject']) . "'";
		} else {
			$stm .= $next . "subject = '" . $_REQUEST['subject'] . "'";
		}
	}
	if ($_REQUEST['msgid']!= "") {
		if (preg_match('/\*/',$_REQUEST['msgid'])) {
			$stm .= $next."msgid like '" . preg_replace('/\*/','%',$_REQUEST['msgid']) . "'";
		} else {
			$stm .= $next."msgid = '" . $_REQUEST['msgid'] . "'";
		}
	}
	if ($_REQUEST['server']!= "" ){
		$stm .= $next . "server = '".$_REQUEST['server'] . "'";
	}

	if ($_REQUEST['status']!= "") {
		$stm .= $next . "status = '" . $_REQUEST['status'] . "'";
	}
	//$stm_fetch=array();

	foreach ($files as $postfix_db) {
              if (file_exists($postfix_dir.'/'.$postfix_db)) {
			$dbhandle = new SQLite3($postfix_dir.'/'.$postfix_db);
			//noqueue
			$result1= $dbhandle->query($stm1. $stm . " order by date desc $limit_prefix $limit;");
			if ($result1){
				//var_dump($result1->fetchArray(SQLITE3_ASSOC));
				while($row=$result1->fetchArray(SQLITE3_ASSOC)) {
					if (is_array($row)) {
						$stm_fetch[] = $row;
                               	 	}
				}
			}
			//queue
			$stm = preg_replace("/ and status =/"," and mail_status.info =",$stm);
			$result2= $dbhandle->query($stm2 . $stm . " order by date desc $limit_prefix $limit;");
                        if ($result2){
                                //var_dump($result2->fetchArray(SQLITE3_ASSOC));
                                while($row=$result2->fetchArray(SQLITE3_ASSOC)) {
                                        if (is_array($row)) {
                                                $stm_fetch[] = $row;
                                        }
                                }
                        }
	   }
	}
	$fields= explode(",", $_REQUEST['fields']);
		//to see examples, and select datatable modules to download see
		// https://datatables.net/download/
		?>
                <link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
		<link rel="stylesheet" href="/vendor/datatable/Buttons-1.2.4/css/buttons.dataTables.min.css">
                <script src="/vendor/jquery/jquery-1.12.0.min.js" type="text/javascript"></script>
                <script src="/vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
                <script src="/vendor/datatable/js/jquery.dataTables.min.js" type="text/javascript"></script>
		<script src="/vendor/datatable/Buttons-1.2.4/js/dataTables.buttons.min.js" type="text/javascript"></script>
		<script src="/vendor/datatable/JSZip-2.5.0/jszip.min.js" type="text/javascript"></script>
		<script src="/vendor/datatable/pdfmake-0.1.18/build/pdfmake.min.js" type="text/javascript"></script>
		<script src="/vendor/datatable/pdfmake-0.1.18/build/vfs_fonts.js" type="text/javascript"></script>
		<script src="/vendor/datatable/Buttons-1.2.4/js/buttons.html5.min.js" type="text/javascript"></script>
		<script src="/vendor/datatable/ColReorder-1.3.2/js/dataTables.colReorder.min.js" type="text/javascript"></script>

		<br/>
                <?php

	print '<table id="dtresult" class="display" width="90%" border="0" cellpadding="8" cellspacing="0">';
	$tss=array('thead','tfoot');
	$dbc=array('date','server','from','to','subject','delay','helo','status','status_info','size','sid','msgid','bounce','relay','log');
        foreach ($tss as $t){
		$$t = "<" . $t . "><tr>\n";
		foreach ($dbc as $c){
                	if(in_array($c,$fields))
                        	$$t .= "<th>".ucfirst($c)."</th>";
                        }
		$$t .= "</tr></" . $t . ">";
	}
	print "{$thead}\n<tbody>\n";
	foreach ($stm_fetch as $mail) {
        	print "\n<tr>";
                foreach ($dbc as $c) {
                	if(in_array($c,$fields)) {
			   switch($c){
                                case 'from':
                                        print  "<th>{$mail['fromm']}</th>\n";
                                        break;
				case 'status_info':
                                        print  "<th>{$mail['info']}</th>\n";
                                        break;
                                case 'to':
                                        print  "<th>{$mail['too']}</th>\n";
                                        break;
				case 'subject':
					print '<th>'. mb_decode_mimeheader($mail['subject']).'</th>';
					break;
				default:
                                	print  "<th>{$mail[$c]}</th>\n";
					break;
				}
                        }
		}
                print "</tr>\n";
        }
	print "</tbody>\n{$tfoot}\n</table>";
	print <<<EOF
<script>
$(document).ready(function() {
    $('#dtresult').DataTable({
	scrollY:        '60vh',
	scrollCollapse: true,
	dom: 'Bfrtip',
	colReorder: {
            realtime: false
        },
        buttons: [
            'copyHtml5',
	    'excelHtml5',
            'csvHtml5',
            { extend: 'pdfHtml5',
		orientation: 'landscape',
		}
        ]
	});
} );
</script>
EOF;
}
?>
