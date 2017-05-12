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
$dmarc_base_dir="/home/{$dkim_user}/keys";

$pfs_version = substr(trim(file_get_contents("/etc/version")),0,3);

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


