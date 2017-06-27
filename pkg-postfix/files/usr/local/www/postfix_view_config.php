<?php
/*
 * postfix_view_config.php
 *
 *
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
require_once("guiconfig.inc");

define('POSTFIX_LOCALBASE','/usr/local');

if (isset($_REQUEST['file'])){
        $files= array(	'main'  => POSTFIX_LOCALBASE . "/etc/postfix/main.cf",
        		'master'=> POSTFIX_LOCALBASE . "/etc/postfix/master.cf",
        		'recipients'=> POSTFIX_LOCALBASE . "/etc/postfix/relay_recipients",
        		'header' => POSTFIX_LOCALBASE . "/etc/postfix/header_check",
        		'mime' => POSTFIX_LOCALBASE . "/etc/postfix/mime_check",
        		'body' => POSTFIX_LOCALBASE . "/etc/postfix/body_check",
        		'domains' => POSTFIX_LOCALBASE . "/etc/postfix/auto_whitelisted_domains",
        		'cidr' => POSTFIX_LOCALBASE . "/etc/postfix/auto_whitelisted_cidr",
			'cal_pcre' => POSTFIX_LOCALBASE . "/etc/postfix/cal_pcre",
        		'cal_cidr' => POSTFIX_LOCALBASE . "/etc/postfix/cal_cidr",
        		'aliases' => POSTFIX_LOCALBASE . "/etc/postfix/aliases",
        		'spf' => POSTFIX_LOCALBASE . "/etc/postfix/postscreen_spf_whitelist.cidr",
        		'transport' => POSTFIX_LOCALBASE . "/etc/postfix/transport",
        		'local_recipients' => POSTFIX_LOCALBASE . "/etc/postfix/local_recipients",
        		'postfwd_conf' => POSTFIX_LOCALBASE . "/etc/postfix/postfwd.conf",
        		'sender_access' => POSTFIX_LOCALBASE . "/etc/postfix/sender_access",
        		'virtual' => POSTFIX_LOCALBASE . "/etc/postfix/virtual",
        		'virtual_alias_maps' => POSTFIX_LOCALBASE . "/etc/postfix/virtual_alias_maps");
	$file=preg_replace("/\W+/","",$_REQUEST['file']);

	if ( $files[$file] != "" && file_exists($files[$file])){
                print "<PRE>" . $files[$file] . "\n" . file_get_contents($files[$file]) ."</PRE>";
        }
exit;
}


$pgtitle = array(gettext("Package"), gettext("Services: Postfix relay and antispam"), gettext("View"), gettext("Config"));
$shortcut_section = "postfix";
include("head.inc");

if ($savemsg) {
        print_info_box($savemsg);
}

$uname=posix_uname();
if ($uname['machine']=='amd64')
        ini_set('memory_limit', '250M');

$pf_version=substr(trim(file_get_contents("/etc/version")),0,3);


        $tab_array = array();
        $tab_array[] = array(gettext("General"), false, "/pkg_edit.php?xml=postfix.xml&id=0");
        $tab_array[] = array(gettext("Domains"), false, "/pkg.php?xml=postfix_domains.xml");
        $tab_array[] = array(gettext("Recipients"), false, "/pkg_edit.php?xml=postfix_recipients.xml&id=0");
        $tab_array[] = array(gettext("Access Lists"), false, "/pkg_edit.php?xml=postfix_acl.xml&id=0");
        $tab_array[] = array(gettext("Antispam"), false, "/pkg_edit.php?xml=postfix_antispam.xml&id=0");
        $tab_array[] = array(gettext("Sync"), false, "/pkg_edit.php?xml=postfix_sync.xml&id=0");
        $tab_array[] = array(gettext("View config"), true, "/postfix_view_config.php");
        $tab_array[] = array(gettext("Search mail"), false, "/postfix_search.php");
        $tab_array[] = array(gettext("Queue"), false, "/postfix_queue.php");
        $tab_array[] = array(gettext("About"), false, "/postfix_about.php");
        display_top_tabs($tab_array);
?>

<link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("View Postfix configuration files"); ?></h2></div>
        <div class="panel-body">
        <div class="table-responsive">

<br>
<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <ul class="nav navbar-nav">
      <li><a href="#" onClick="get_postfix_file('main');">main.cf</a></li>
      <li><a href="#" onClick="get_postfix_file('master');">master.cf</a></li>
      <li><a href="#" onClick="get_postfix_file('recipients');">relay_recipients</a></li>
      <li><a href="#" onClick="get_postfix_file('header');">heade_check</a></li>
      <li><a href="#" onClick="get_postfix_file('mime');">mime_check</a></li>
      <li><a href="#" onClick="get_postfix_file('body');">body_check</a></li>
      <li><a href="#" onClick="get_postfix_file('domains');">whitelisted domains</a></li>
      <li><a href="#"onClick="get_postfix_file('cidr');">whitelisted CIDRs</a></li>
      <li><a href="#" onClick="get_postfix_file('cal_pcre');">cal_pcre</a></li>
      <li><a href="#" onClick="get_postfix_file('cal_cidr');">cal_cidr</a></li>
      <li><a href="#" onClick="get_postfix_file('aliases');">aliases</a></li>
      <li><a href="#" onClick="get_postfix_file('spf');">whitelisted spf</a></li>
      <li><a href="#" onClick="get_postfix_file('transport');">transport</a></li>
      <li><a href="#" onClick="get_postfix_file('virtual');">virtual</a></li>
      <li><a href="#" onClick="get_postfix_file('local_recipients');">local_recipients</a></li>
      <li><a href="#" onClick="get_postfix_file('postfwd_conf');">postfwd.conf</a></li>
      <li><a href="#" onClick="get_postfix_file('sender_access');">sender_access</a></li>
      <li><a href="#" onClick="get_postfix_file('virtual_alias_maps');">virtual_alias_maps</a></li>
    </ul>
  </div>
</nav>

	</div>
	</div>
        <div class="panel panel-default" style="margin-right:auto;margin-left:auto;width:95%;">
        <div class="panel-body">
        <div id="file_contents" class="table-responsive">
</div>
</div>
</div>

	<script type="text/javascript">
	function get_postfix_file(file) {
			var url = "/postfix_view_config.php";
			jQuery.ajax(url,
                                {
                                type: 'post',
                                data: {
                                        file:   file
                                },
                                success: function(ret){
                                        $('#file_contents').html(ret);
                                        //scroll(0,1100);
                                }
                        }
                        );

		}
	</script>
	<?php include("foot.inc"); ?>
	</body>
	</html>

