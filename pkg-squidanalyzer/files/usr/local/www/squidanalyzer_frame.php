<?php

/*
 * squidanalyzer_frame.php
 *
 *
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
require_once("authgui.inc");

$uname = posix_uname();
if ($uname['machine'] == 'amd64') {
	ini_set('memory_limit', '512M');
}

// local file inclusion check
if(!empty($_REQUEST['file'])){
        $_REQUEST['file'] = preg_replace('/(\.+\/|\\\.*|\/{2,})*/',"", $_REQUEST['file']);
}

if (preg_match("/(\S+)\W(\w+.html)/", $_REQUEST['file'], $matches)) {
	// URL format
	// https://192.168.1.1/squidananlyzer_reports.php?file=2012Mar30-2012Mar30/index.html
	$url = $matches[2];
	$prefix = $matches[1];
} else {
	$url = "index.html";
	$prefix = "";
}

$url = ($_REQUEST['file'] == "" ? "index.html" : $_REQUEST['file']);
$dir = "/usr/local/squidreport";

$ww_dir = "/usr/local/www/squidreport";

//create static files
if (! is_dir($ww_dir)) {
	mkdir ($ww_dir,0644);
}

$static = array("images" , "sorttable.js" , "squidanalyzer.css" , "flotr2.js");

foreach ($static as $file) {
	if (!file_exists("$ww_dir/$file")) {
		system ("/bin/cp -r $dir/$file $ww_dir/$file");
	}
}

$rand = rand(100000000000, 999999999999);
$report = "";
if (file_exists("{$dir}/{$url}")) {
	$report = file_get_contents("{$dir}/{$url}");
} elseif (file_exists("{$dir}/{$url}.gz")) {
	$data = gzfile("{$dir}/{$url}.gz");
	$report = implode($data);
	unset ($data);
}
if ($report != "" ) {
	$pattern[0] = "/href=\W(\S+html)\W/";
	$pattern[1] = "@href=\W/squidreport(\S+week\d+)\W@";
	$replace[0] = "href=/squidanalyzer_frame.php?prevent={$rand}&file=$prefix/$1";
	$replace[1] = "href=/squidanalyzer_frame.php?prevent={$rand}&file=$1/index.html";
	$pattern[4] = '/<head>/';
	$replace[4] = '<head><META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"><META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">';
	print preg_replace($pattern, $replace, $report);
} else {
	print "Error: Could not find report index file.<br />Check and save Squid Analyzer settings.";
}

?>
