<?php
/*
 * postfix_postfwd.inc
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

function postfix_check_postfwd_service($cfg) {
	global $savemsg_array;
	if ($cfg['postfwd_enabled'] == "on") {
		$status = "YES";
	} else {
		$status = "NO";
	}
	$service="postfwd";
	$service_dir="/usr/local/etc/rc.d";
	$service_file_content=file_get_contents("{$service_dir}/{$service}");
	$service_file_content=preg_replace("/postfwd_enable:=\"(YES|NO)\"/" , "postfwd_enable:=\"$status\"" , $service_file_content);
	$service_file_content=preg_replace("@name}.pid@" , "name}/pid" , $service_file_content);
	$service_file_content=preg_replace("@/usr/local/etc/\S+conf@" , '/usr/local/etc/postfix/${name}.conf' , $service_file_content);
	$service_file_content=preg_replace("@nobody@" , 'mailnull' , $service_file_content);
	file_put_contents("{$service_dir}/{$service}" , $service_file_content , LOCK_EX);
	if (!file_exists("{$service_dir}/{$service}.sh")) {
		symlink ( "{$service_dir}/{$service}" , "{$service_dir}/{$service}.sh");
	}
	$dir = "/var/run/postfwd";
	if (!is_dir($dir) && !file_exists($dir)) {
                        mkdir($dir, 0755,TRUE);
			chown($dir, 'mailnull');
                	chgrp($dir, 'mailnull');
                }

	if ($status=="YES"){
		exec("{$service_dir}/{$service}.sh restart",$savemsg_array);
	} else {
		exec("{$service_dir}/{$service}.sh onestop",$savemsg_array);
	}
}

function postfix_check_postfwd($status,$position,$rules){
	$postfwd_conf_file = POSTFIX_LOCALBASE . "/etc/postfix/postfwd";
	$rule_position= ($position != "" ? $position : "before");
	$conf_spaces="\n\t\t\t\t";
	$conf_inet="check_policy_service inet:127.0.0.1:10040,";
	if ($status == 'on') {
		if ($rule_position == "before") {
			$postfwd_before="{$conf_inet}{$conf_spaces}";
			$postfwd_after="";
		} else {
			$postfwd_after="{$conf_spaces}{$conf_inet}";
			$postfwd_before="";
		}
		file_put_contents("{$postfwd_conf_file}_tmp.conf" , base64_decode($rules) , LOCK_EX);
		$new_md5=md5_file("{$postfwd_conf_file}_tmp.conf");
		if (file_exists("{$postfwd_conf_file}.conf")) {
			$current_md5 = md5_file("{$postfwd_conf_file}.conf");
		}
		if ($new_md5 != $current_md5) {
			rename("{$postfwd_conf_file}_tmp.conf" , "{$postfwd_conf_file}.conf");
		}
		$return =array($postfwd_before , $postfwd_after);
	 } else {
                //return empty
                $return = array("","");
        }
	return($return);
}

?>
