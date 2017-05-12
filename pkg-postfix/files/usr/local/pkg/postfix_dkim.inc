<?php
/*
 * postfix_dkim.inc
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

$shortcut_section = "postfix";
require_once("config.inc");
require_once("functions.inc");
require_once("globals.inc");
require_once("interfaces.inc");
require_once("notices.inc");
require_once("pkg-utils.inc");
require_once("services.inc");
require_once("util.inc");

$dkim_user="mailnull";
$dkim_base_dir="/home/{$dkim_user}/keys";

$pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);

function postfix_check_dkim() {
   global $dkim_user,$dkim_base_dir,$savemsg,$config;
/* obsolete. Moved to button and pkg.php
   if (is_array($config['installedpackages']['postfixdomains']['config'])) {
        $show_count=0;
        $new_config=array();
        foreach($config['installedpackages']['postfixdomains']['config'][0][row] as $domain_id => $saved_domains) {
          if ($saved_domains['dkim'] == 'public') {
             $savemsg.= "<br><div style='word-wrap: break-word; width: 600px'>".file_get_contents("{$dkim_base_dir}/{$saved_domains['domain']}/default.txt")."</div><br>";
             $show_count++;
             $config['installedpackages']['postfixdomains']['config'][0][row][$domain_id]['dkim']="yes";
         }
        if ($saved_domains['dkim'] == 'reset') {
             $savemsg.= "<br>Info: New Domain Keys for {$saved_domains['domain']} are ready to use.<br>";
             $show_count++;
             $config['installedpackages']['postfixdomains']['config'][0][row][$domain_id]['dkim']="yes";
         }

       }
       if ($show_count > 0) {
        write_config('Postfix - restore domain option after show key or reset dkim selection.');
       }
  }
*/
}

function postfix_domain_validate_input($post, &$input_errors) {
        global $dkim_user,$dkim_base_dir,$savemsgi,$config;
        $remain_domains=array();
	$dkim_domain = "";
        //print "<PRE>";
        foreach ($post as $key => $value) {
                if ($key == "domain") {
			$dkim_domain = $value;
                        if (!is_domain($value)) {
                                $input_errors[] = "{$value} is not a valid domain name.";
			}
                } else if ($key == "mailserverip") {
			list($t_ip, $t_port) = explode(":", $value);
                        if (!is_ipaddr($t_ip) && !is_hostname($t_ip)) {
                                $input_errors[] = "{$t_ip} is not a valid IP address or host name.";
			}
                } else if ($key == "private" && $value == "" && $dkim_domain != "") {
			//remove files from disk if domain exists
			remove_dkim_files($dkim_domain,'yes');
		}
        }
}

function remove_dkim_files($dkim_domain,$remove_dir){
	global $dkim_base_dir;
	if ($dkim_domain != "" && is_dir("{$dkim_base_dir}/{$dkim_domain}")) {
		unlink_if_exists("{$dkim_base_dir}/{$dkim_domain}/default.private");
                unlink_if_exists("{$dkim_base_dir}/{$dkim_domain}/default.txt");
		if ($remove_dir == 'yes') {
                	rmdir("{$dkim_base_dir}/{$dkim_domain}");
		}
	}
}

function check_dkim_service($status){
        $service="milter-opendkim";
        $service_dir="/usr/local/etc/rc.d";
        $service_file_content=file_get_contents("{$service_dir}/{$service}");
        $service_file_content=preg_replace("/milteropendkim_enable=\"(YES|NO)\"/","milteropendkim_enable=\"$status\"",$service_file_content);
        file_put_contents("{$service_dir}/{$service}",$service_file_content,LOCK_EX);
        if (!file_exists("{$service_dir}/{$service}.sh")) {
                symlink ( "{$service_dir}/{$service}","{$service_dir}/{$service}.sh");
        }
        // include on install process
        // ln -s /usr/local/lib/libmilter.so.6 /usr/local/lib/libmilter.so.5
        if ($status=="YES"){
                system("{$service_dir}/{$service}.sh restart");
        }else{
                system("{$service_dir}/{$service}.sh stop");
        }
}
function check_dkim_dir($dir,$user){
        if (! file_exists($dir)){
                log_error("[postfix] Creating dkim folder {$dir}");
                mkdir($dir, 0750, true);
        }
        chown($dir, $user);
        chgrp($dir, 'wheel');
}

function dkim_save_files($domain,$private,$pub) {
	global $dkim_user,$dkim_base_dir;
	$dkim_dir = "$dkim_base_dir/$domain";
	check_dkim_dir($dkim_dir,$dkim_user);
	file_put_contents("{$dkim_dir}/default.private",base64_decode($private),LOCK_EX);
        file_put_contents("{$dkim_dir}/default.txt",base64_decode($pub),LOCK_EX);
        chmod ("{$dkim_dir}/default.private",0640);
        chmod ("{$dkim_dir}/default.txt",0640);
}

function dkim_create_key($domain,$bits,$gui_id) {
	global $config,$dkim_user,$dkim_base_dir;

	$dkim_dir = "$dkim_base_dir/$domain";
	check_dkim_dir($dkim_dir,$dkim_user);
	log_error("[postfix] Creating Domain key for {$domain} under {$dkim_dir}");
	system("/usr/local/sbin/opendkim-genkey -D {$dkim_dir} -b {$bits} -h rsa-sha256 --subdomains -d {$domain} -s default");
	$dkim_private=base64_encode(file_get_contents("{$dkim_base_dir}/{$domain}/default.private"));
	$dkim_pub=base64_encode(file_get_contents("{$dkim_base_dir}/{$domain}/default.txt"));
	chmod ("{$dkim_base_dir}/{$domain}/default.private",0640);
	chmod ("{$dkim_base_dir}/{$domain}/default.txt",0640);
                
	// update gui with private and pub info
        $config['installedpackages']['postfixdomainsng']['config'][$gui_id]['private'] = $dkim_private;
        $config['installedpackages']['postfixdomainsng']['config'][$gui_id]['pub'] = $dkim_pub;
        
	chown("{$dkim_dir}/default.txt",$dkim_user);
        chown("{$dkim_dir}/default.private",$dkim_user);
}

function check_dkim_config (){
 global $config;
 $postfix_config=$config['installedpackages']['postfix']['config'][0];
        if (is_array($config['installedpackages']['postfixdomainsng'])) {
                $postfix_domains=$config['installedpackages']['postfixdomainsng']['config'];
                $dkim_domains=array();
                foreach ($postfix_domains as $id => $domain) {
                        // var_dump($domain);
                        if ($domain['dkim'] != 'no' ) {
				$dkim_domains[$domain['domain']]['id'] = $id;
                        	$dkim_domains[$domain['domain']]['private'] = $domain['private'];
			  	$dkim_domains[$domain['domain']]['pub'] = $domain['pub'];
                          	if (preg_match ("/\d+/",$domain['bits'])) {
                               		$dkim_domains[$domain['domain']]['bits'] = $domain['bits'];
                          	} else {
                                	$dkim_domains[$domain['domain']]['bits'] = '1024';
                          	}
                          	$internal_hosts .= "{$domain['mailserverip']}/32\n";
                        }
                }
                //var_dump($dkim_bits);
                //exit;
                //Include Mynetwork hosts on DKIM Internal hosts
                if (is_array($config['installedpackages']['postfixacl']['config']) && count($dkim_domains) > 0){
                        $mynetwork = preg_replace( "/\r/" , "" , base64_decode($config['installedpackages']['postfixacl']['config'][0]['mynetworks']));
                        $myhosts = explode( "\n" , $mynetwork);
                        foreach ($myhosts as $myhost) {
                                if (preg_match ( "@/@" , $myhost)) {
                                       $internal_hosts .= "{$myhost}\n";
                                } else {
                                       $internal_hosts .= "{$myhost}/32\n";
                                }
                        }
                }
        }
        $check_dkmi = "off";
        if (is_array($config['installedpackages']['postfixantispam'])) {
                $postfix_antispam = $config['installedpackages']['postfixantispam']['config'][0];
                if ($postfix_antispam['check_dkim'] == 'on') {
                $check_dkmi = "on";
                }
                if ( preg_match ("/\w/",$postfix_antispam['on_badsignature'])) {
                        $On_BadSignature = $postfix_antispam['on_badsignature'];
                } else {
                        $On_BadSignature = 'a';
                }
                if ( preg_match ("/\w/" , $postfix_antispam['on_keynotfound'])) {
                        $On_KeyNotFound = $postfix_antispam['on_keynotfound'];
                } else {
                        $On_KeyNotFound = 'a';
                }
                if ( preg_match ("/\w/" , $postfix_antispam['on_nosignature'])) {
                        $On_NoSignature = $postfix_antispam['on_nosignature'];
                } else {
                        $On_NoSignature = 'a';
                }

                #var_dump($postfix_antispam);
        }
        #var_dump ($dkim_domains);
        #var_dump ($postfix_domains);
	if (preg_match('/\w+/',$postfix_antispam['except_domains'])) {
		$except_domains="ExemptDomains {$postfix_antispam['except_domains']}";
	} else {
		$except_domains="";
	}
        if (count($dkim_domains) >0) {
        	$dkim_mode = "s";
        } else {
        	$dkim_mode = "";
        }
        if ($check_dkmi == "on") {
        	$dkim_mode .= "v";
        }
        if ($check_dkmi == "on" || count($dkim_domains) >0){
                // print "configure dkim\n";
                $config_domains = "";
                foreach($dkim_domains as $domain => $domain_info) {
                	$config_domains .= "Domain $domain\n";
                }
                $dkim_user="mailnull";
                $dkim_base_dir="/home/{$dkim_user}/keys";
                $internal_hosts_file = "{$dkim_base_dir}/dkim-InternalHosts.txt";
                $internal_hosts_conf = "ExternalIgnoreList  refile:{$dkim_base_dir}/dkim-InternalHosts.txt\n";
                $internal_hosts_conf.= "InternalHosts       refile:{$dkim_base_dir}/dkim-InternalHosts.txt";
                check_dkim_dir("/home/{$dkim_user}",$dkim_user);
                check_dkim_dir($dkim_base_dir,$dkim_user);
                // https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-dkim-with-postfix-on-debian-wheezy
                $config_template=<<<EOF
LogWhy               yes
Syslog               yes
SyslogSuccess        yes
AutoRestart          yes
Background           yes
Canonicalization     relaxed/relaxed
DNSTimeout           5
Mode                 {$dkim_mode}
SignatureAlgorithm   rsa-sha256
SubDomains           no
#UseASPDiscard       no
#Version             rfc4871
X-Header             no
On-BadSignature     {$On_BadSignature}
On-KeyNotFound      {$On_KeyNotFound}
On-NoSignature      {$On_NoSignature}
On-InternalError    a
On-DNSError         a
{$except_domains}

{$config_domains}
{$internal_hosts_conf}
Selector default
KeyFile {$dkim_base_dir}/default.private
# Gives the location of a private key to be used for signing ALL messages.
#KeyFile   {$dkim_base_dir}/default.private

# Gives the location of a file mapping key names to signing keys. In simple terms,
# this tells OpenDKIM where to find your keys. If present, overrides any KeyFile
# setting in the configuration file.
KeyTable    refile:{$dkim_base_dir}/KeyTable

# Defines a table used to select one or more signatures to apply to a message based
# on the address found in the From: header field. In simple terms, this tells
# OpenDKIM how to use your keys.
SigningTable refile:{$dkim_base_dir}/SigningTable

Socket inet:8891@localhost
ReportAddress antispam
SendReports yes

EOF;
                file_put_contents("/usr/local/etc/mail/opendkim.conf",$config_template,LOCK_EX);

                //check if dkim is market to be regenerated
                if (is_array($config['installedpackages']['postfixdomainsng']['config'])) {
                        $reset_domains=array();
                        foreach($config['installedpackages']['postfixdomainsng']['config'] as $saved_domains) {
                                if ($saved_domains['dkim'] == 'reset') {
                                   $reset_domains[]=$saved_domains['domain']; //CHANGE HERE FROM COMBO SAVE TO BUTTON CLICK
                                }
                        }
                }
                print "<PRE>";
                //check domain keys

		//list current keys on disk
		$domains_on_disk=array();
		foreach (glob("$dkim_base_dir/*") as $domain_path) {
			$file_domain=array_pop(split("/",$domain_path));
			//var_dump($file_domain);
			if (is_dir($domain_path) && is_domain($file_domain)) {
				if (! array_key_exists ($file_domain,$dkim_domains)) {
					//domain does not exists on GUI
					remove_dkim_files($file_domain,'yes');
				}
				if (file_exists("$domain_path/default.private")) {
					$domains_on_disk[$file_domain]['private']=base64_encode(file_get_contents("$domain_path/default.private"));
					$domains_on_disk[$file_domain]['pub']=base64_encode(file_get_contents("$domain_path/default.txt"));
				}
			}
		}

		//check domains
		//var_dump($dkim_domains);
		$dkim_create = array();
		$keys_created="";
		foreach ($dkim_domains as $dkim_domain => $dkim_info) {
                	$new_saved_keys = array();
                        $keys_removed = "";
			//var_dump($dkim_domain);
			//var_dump($domains_on_disk);
			if (array_key_exists($dkim_domain,$domains_on_disk)) {
				// dkim key found on disk
				if ($dkim_info['private'] == "") {
				   //read key from disk
				}
				if ($dkim_info['private'] != $domains_on_disk[$dkim_domain]['private'] ){
					//replace local file key with key on GUI
					dkim_save_files($dkim_domain,$dkim_info['private'],$dkim_info['pub']);
				}
			} else {
				// key exists on GUI but not on disk
				dkim_create_key($domain,$dkim_info['bits'],$dkim_info['id']);
				$keys_created .= "$domain, ";
			}
                }

		// create Signing and key Table
		$key_table = "";
		$sign_table = "";
                foreach ($dkim_domains as $domain => $domain_info) {
                        //check if key exists
                        $dkim_dir = "{$dkim_base_dir}/{$domain}";
                        // print "dir: $dkim_dir\n";
			if (file_exists("{$dkim_dir}/default.private")) {
                        	$key_table .= "default._dkim.{$domain} {$domain}:default:{$dkim_dir}/default.private\n";
                        	$sign_table .= "*@{$domain} default._dkim.{$domain}\n";
			}
                }
                file_put_contents("{$dkim_base_dir}/SigningTable" , $sign_table , LOCK_EX);
                chown("{$dkim_base_dir}/SigningTable",$dkim_user);

                file_put_contents("{$dkim_base_dir}/KeyTable" , $key_table , LOCK_EX);
                chown("{$dkim_base_dir}/KeyTable" , $dkim_user);

                file_put_contents($internal_hosts_file , $internal_hosts , LOCK_EX);
                chown($internal_hosts_file , $dkim_user);

                if ($keys_created != "" ){
                        write_config("DKIM keys created for {$keys_created}");
                }

                check_dkim_service('YES');
                $return=<<<EOF2

# Add the DKIM milter
milter_default_action = accept
milter_protocol = 2
smtpd_milters = inet:localhost:8891
non_smtpd_milters = inet:localhost:8891

EOF2;
        } else {
                //print "stop dkim\n";
                check_dkim_service('NO');
                $return="";
        }
        return($return);
}

//check_dkim_config();
