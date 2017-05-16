<?php
/*
 * postfix_cloud_domains.php
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
require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("/usr/local/pkg/postfix.inc");

$uname=posix_uname();
if ($uname['machine']=='amd64')
        ini_set('memory_limit', '250M');

function open_table(){
	echo "<table style=\"padding-top:0px; padding-bottom:0px; padding-left:0px; padding-right:0px\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	echo"  <tr>";
}
function close_table(){
	echo"  </tr>";
	echo"</table>";

}

function postfix_read_db($query,$file) {
	$postfixdb = array();
	$DB = postfix_opendb($file);
	if ($DB) {
		$response = $DB->query("{$query}");
		if ($response != FALSE) {
			while ($row = $response->fetchArray())
				$postfixdb[] = $row;
		}
		else
			print "Trying to read DB returned error: {$DB->lastErrorMsg()}";
		$DB->close();

	}
	return $postfixdb;
}

function postfix_opendb($file) {
	global $g,$postfix_dir,$postfix_arg;

	$stm = "select id from mail_status;";
	if (file_exists($postfix_dir.$file)){
		$DB = new SQLite3($postfix_dir.$file);
		if ($DB->exec("{$stm};"))
			return $DB;
	}
}

function postfix_hash_append($afile,$domain,$ips=array()) {
        $hfile = "/usr/local/etc/postfix/auto_whitelisted_cidr";
        $cidrs = "";
        if (file_exists($hfile)) {
                $cidrs=file_get_contents($hfile);
        }
        if (file_exists($afile)) {
                $domains=file_get_contents($afile);
        }
        $domains .= "{$domain}\n";
        file_put_contents($afile,$domains,LOCK_EX);
        foreach($ips as $ip) {
                if (preg_match("/ip\d:(\S+)/",$ip,$m)) {
                        $cidrs .= "{$m[1]}\tpermit #{$domain}\n";
                }
        }
        file_put_contents("{$hfile}.tmp",$cidrs,LOCK_EX);
        system("/usr/bin/sort -u {$hfile}.tmp > {$hfile}");
        system("/usr/local/sbin/postmap {$hfile}");
}
global $config;

if (is_array ($argv) && $argv[1] == 'clean') {
	$prefix = "/usr/local/etc/postfix";
	file_put_contents("{$prefix}/auto_whitelisted_domains","",LOCK_EX);
	file_put_contents("{$prefix}/auto_whitelisted_cidr","",LOCK_EX);
	system("/usr/local/sbin/postmap {$prefix}/auto_whitelisted_cidr");
	system("/usr/local/sbin/postfix reload");
	exit;	
}

if (is_array($config['installedpackages']['postfixantispam'])) {
        $antispam=$config['installedpackages']['postfixantispam']['config'][0];
        $count=$antispam['auto_whitelist'];
} else {
        $count=0;
}
if ($count > 0){
  $afile = "/usr/local/etc/postfix/auto_whitelisted_domains";
  $domains = array();
  if (file_exists($afile)) {
        $domains = file($afile);
        foreach($domains as $id => $domain) {
                $domains[$id]=chop($domain);
        }
  }
  $postfix_dir="/var/db/postfix/";
  $curr_time = time();
  $postfix_db=date("Y-m-d");
  $reload_postfix=0;
  if (file_exists($postfix_dir.'/'.$postfix_db.".db")){
	// noqueue
        // print "{$postfix_dir}/{$postfix_db}.db...\n";
        $stm="select fromm,count(*) as total from mail_noqueue where status_info like '%450 4.3.2 Service currently unavailable' group by fromm order by count(*) DESC";
        $row_noqueue = postfix_read_db($stm,$postfix_db.".db");
        foreach($row_noqueue as $i => $row){
                $domain=preg_replace("/\S+@/","",$row['fromm']);
                print "{$domain} {$row['total']}\n";
                if ($row['total'] >= $count) {
                        if (in_array($domain,$domains)) {
                                print "$domain already whitelisted\n";
                        } else {
				if (preg_match("/\w+/",$domain)) {
				   $spf = "spf_" . $domain;
                                   exec("/usr/local/bin/spf-tools/despf.sh $domain",$$spf);
                                   if (count($$spf > 1)) {
                                        $reload_postfix++;
                                        $domains[]=$domain;
                                        postfix_hash_append($afile,$domain,$$spf);
                                   }
				} else {
				   print "domain($domain) is invalid\n";
				}
                        }
                }
        }
  }
  if ($reload_postfix > 0) {
        system("/usr/local/sbin/postfix reload");
  }
}
?>
