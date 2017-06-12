<?php
/*
 * squidanalyzer_reports.php
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

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("guiconfig.inc");

define('POSTFIX_LOCALBASE','/usr/local');
/*
$pgtitle = array(gettext("Package"), gettext("Status: Squid Analyzer"), gettext("View"), gettext("Report"));
include("head.inc");

if ($savemsg) {
        print_info_box($savemsg);
}

$uname=posix_uname();
if ($uname['machine']=='amd64')
        ini_set('memory_limit', '512M');

$pf_version=substr(trim(file_get_contents("/etc/version")),0,3);


        $tab_array = array();
        $tab_array[] = array(gettext("General Settings"), false, "/pkg_edit.php?xml=squidanalyzer.xml&id=0");
        $tab_array[] = array(gettext("SquidAnalyzer Report"), true, "/squidanalyzer_reports.php");
        $tab_array[] = array(gettext("XMLRPC Sync"), false, "/pkg_edit.php?xml=squidanalyzer_sync.xml&id=0");
        display_top_tabs($tab_array);
*/
?>
<!--
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("View Postfix configuration files"); ?></h2></div>
        <div class="panel-body">
        <div class="table-responsive"> 
        <div class="panel-body">
        <div id="file_contents" class="table-responsive">-->
	</div>
	<script type="text/javascript">
        	//<![CDATA[
                var axel = Math.random() + "";
                var num = axel * 1000000000000000000;
                document.writeln('<iframe src="/squidanalyzer_frame.php?prevent='+ num +'?"  frameborder="0" align="center" width="100%" height="700"></iframe>');
                //]]>
	</script>

	<div>
	<?php include("foot.inc"); ?>
	</body>
	</html>

