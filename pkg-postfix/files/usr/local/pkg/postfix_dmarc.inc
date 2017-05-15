<?php
/*
 * postfix_dmarc.inc
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

$dmarc_user="mailnull";

$pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);

function check_dmarc_service($cfg){
	global $savemsg_array;
	if($cfg['dmarc_enabled'] != "on") {
                $status='NO';
        } else {
                $status='YES';
        }
        $service = "opendmarc";
        $service_dir = "/usr/local/etc/rc.d";
        $service_file_content = file_get_contents("{$service_dir}/{$service}");
        $service_file_content = preg_replace("/opendmarc_enable-\"(YES|NO)\"/" , "opendmarc_enable-\"$status\"" , $service_file_content);
        file_put_contents("{$service_dir}/{$service}" , $service_file_content,LOCK_EX);
        if (!file_exists("{$service_dir}/{$service}.sh")) {
                symlink ( "{$service_dir}/{$service}" , "{$service_dir}/{$service}.sh");
        }
        // include on install process
        // ln -s /usr/local/lib/libmilter.so.6 /usr/local/lib/libmilter.so.5
        if ($status=="YES"){
                exec("{$service_dir}/{$service}.sh restart",$savemsg_array);
        }else{
                exec("{$service_dir}/{$service}.sh onestop",$savemsg_array);
        }
}

function dmarc_create_txt_record($post) {
	//v=DMARC1; p=quarantine; rua=mailto:marcellocoutinho@gmail.com; fo=0; adkim=r; aspf=r; pct=20; rf=afrf

	if ($post['dmarcpolicy'] == 'disabled') {
		return "";
	}
	$txt  = "_dmarc.{$post['domain']}.\tIN\tTXT\t\".v=DMARC1; p={$post['dmarcpolicy']}; ";
	$txt .= ($post['dmarcrua'] != "" ? "rua=mailto:{$post['dmarcrua']}; " : "" );
	$txt .= ($post['dmarcruf'] != "" ? "ruf=mailto:{$post['dmarcruf']}; " : "" );
	$txt .= ($post['dmarcfo'] != "" ? "fo={$post['dmarcfo']}; " : "fo=0; ");
	$txt .= ($post['dmarcadkim'] != "" ? "adkim={$post['dmarcadkim']}; " : "" );
	$txt .= ($post['dmarcaspf'] != "" ? "aspf={$post['dmarcaspf']}; " : "" );
	$txt .= ($post['dmarcpct'] != "" ? "aspf={$post['dmarcpct']}; " : "" );
	$txt .= "pct=20; rf=afrf\"";
	return $txt;
}

function dmarc_text_area_decode($text) {
        return preg_replace('/\r\n/', "\n",base64_decode($text));
}

function check_dmarc_config($cfg) {
	global $dmarc_user;
	$ignore_hosts_file = "/usr/local/etc/mail/ignore.hosts";
	$dmarc_conf_file = "/usr/local/etc/mail/opendmarc.conf";
	$dmarc_trusted = dmarc_text_area_decode($cfg['dmarc_trusted']);
	if (preg_match("/\w+/" , $dmarc_trusted)) {
		$dmarc_trusted = "TrustedAuthservIDs " . $dmarc_trusted;
	}
	$dmarc_ignoreM = dmarc_text_area_decode($cfg['dmarc_ignorem']);
	if (preg_match("/\w+/" , $dmarc_ignoreM)) {
		$dmarc_ignoreM = "IgnoreMailFrom " . $dmarc_ignoreM;
	}
	$dmarc_ignoreH = dmarc_text_area_decode($cfg['dmarc_ignoreh']);
	file_put_contents($ignore_hosts_file , $dmarc_ignoreH , LOCK_EX);
	if (preg_match("/rejectfailures/",$cfg['dmarc_action'])) {
		$dmarc_reject = "yes";
	} else {
		$dmarc_reject = "no"; 
	}
	if (preg_match("/header/",$cfg['dmarc_action'])) {
                $dmarc_header = "yes";
        } else {
                $dmarc_header = "no";
        }
	if ($cfg['dmarc_email'] != "") {
                $dmarc_emailTo = "CopyFailuresTo {$cfg['dmarc_email']}";
	} else {
		$dmarc_emailTo = "";
        }

	$dmarc_conf=<<<EOF
##  AuthservID (string)
##      defaults to MTA name
#AuthservID {$cfg['dmarc_authservid']}
BaseDirectory /var/run/opendmarc
{$dmarc_emailTo}
DNSTimeout 5
# FailureReports false
# FailureReportsBcc postmaster@example.coom
# FailureReportsOnNone false
# FailureReportsSentBy USER@HOSTNAME
HistoryFile /var/run/opendmarc/opendmarc.dat
IgnoreHosts {$ignore_hosts_file}
{$dmarc_ignoreM}
RejectFailures {$dmarc_reject}
PidFile /var/run/opendmarc/pid
{$cfg['dmarc_reject']}
ReportCommand /usr/local/sbin/sendmail -t
Socket inet:8893@localhost
SoftwareHeader {$dmarc_header}
Syslog true
SyslogFacility mail
{$dmarc_trusted}
UserID {$dmarc_user}:{$dmarc_user}

EOF;

file_put_contents($dmarc_conf_file , $dmarc_conf , LOCK_EX);

return("inet:localhost:8893");
}

