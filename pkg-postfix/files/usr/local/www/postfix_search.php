<?php
/*
 * postfix_search.php
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
require_once("guiconfig.inc");

$pgtitle = array(gettext("Package"), gettext("Services: Postfix relay and antispam"), gettext("Search"), gettext("Mail"));
$shortcut_section = "postfix";
include("head.inc");

if ($savemsg) {
        print_info_box($savemsg);
}


$uname=posix_uname();
if ($uname['machine']=='amd64')
        ini_set('memory_limit', '768M');

$pf_version=substr(trim(file_get_contents("/etc/version")),0,3);

	$tab_array = array();
	$tab_array[] = array(gettext("General"), false, "/pkg_edit.php?xml=postfix.xml&id=0");
	$tab_array[] = array(gettext("Domains"), false, "/pkg.php?xml=postfix_domains.xml");
	$tab_array[] = array(gettext("Recipients"), false, "/pkg_edit.php?xml=postfix_recipients.xml&id=0");
	$tab_array[] = array(gettext("Access Lists"), false, "/pkg_edit.php?xml=postfix_acl.xml&id=0");
	$tab_array[] = array(gettext("Antispam"), false, "/pkg_edit.php?xml=postfix_antispam.xml&id=0");
	$tab_array[] = array(gettext("Sync"), false, "/pkg_edit.php?xml=postfix_sync.xml&id=0");
	$tab_array[] = array(gettext("View config"), false, "/postfix_view_config.php");
	$tab_array[] = array(gettext("Search mail"), true, "/postfix_search.php");
	$tab_array[] = array(gettext("Queue"), false, "/postfix_queue.php");
	$tab_array[] = array(gettext("About"), false, "/postfix_about.php");
	display_top_tabs($tab_array);
?>

<link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
<script src="/vendor/jquery/jquery-1.12.0.min.js" type="text/javascript"></script>
<script src="/vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="/vendor/datatable/js/jquery.dataTables.min.js" type="text/javascript"></script>

<div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title"><?=gettext("Search Options"); ?></h2></div>
        <div class="panel-body">
                <div class="table-responsive">
                        <form id="paramsForm" name="paramsForm" method="post" action="">
                        <table class="table table-hover table-condensed">
                                <tbody>
				<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("From: ");?></td>
                        <td width="78%" class="vtable"><textarea id="from" rows="2" cols="50%"></textarea>
                        <br/>
			<span class="vexpl">
			<?=gettext("with wildcard'*' only one line else one email per line.<br>");?>
                        </span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("To: ");?></td>
                        <td width="78%" class="vtable"><textarea id="to" rows="2" cols="50%"></textarea>
                        <br/>
			<span class="vexpl">
			<?=gettext("with wildcard'*' only one line else one email per line.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("SID: ");?></td>
                        <td width="78%" class="vtable"><textarea id="sid" rows="2" cols="20%"></textarea>
                        <br>
			<span class="vexpl">
			<?=gettext("Postfix queue file unique id. One per line.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Subject: ");?></td>
                        <td width="78%" class="vtable"><input type="text" class="formfld unknown" id="subject" size="65%">
                        <br/>
			<span class="vexpl">
			<?=gettext("Subject to search, wildcard is '*'");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Message_id: ");?>
                        <td width="78%" class="vtable"><input type="text" class="formfld unknown" id="msgid" size="65%">
                        <br>
			<span class="vexpl">
			<?=gettext("Message unique id.");?>
			</span>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("server: ");?></td>
                        <td width="78%" class="vtable"><input type="text" class="formfld unknown" id="server" size="30%">
                        <br>
			<span class="vexpl">
			<?=gettext("postfix server.");?>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Relay: ");?></td>
                        <td width="78%" class="vtable"><input type="text" class="formfld unknown" id="relay" size="30%">
                        <br>
			<span class="vexpl">
			<?=gettext("Message destination server");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Message Status: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="status">
                        	<option value="" selected="selected">any</option>
                        	<option value="sent">sent</option>
							<option value="bounced">bounced</option>
							<option value="soft bounce">soft bounce</option>
							<option value="reject">reject</option>
							<option value="spam">spam</option>
							<option value="hold">hold</option>
							<option value="incoming">incoming</option>
						</select>
			<br>
			<span class="vexpl">
			<?=gettext("Max log messages to fetch per Sqlite file.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Query Limit: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="queuemax">
                        	<option value="50" selected="selected">50</option>
							<option value="150">150</option>
							<option value="250">250</option>
							<option value="500">500</option>
							<option value="1000">1000</option>
							<option value="unlimited">Unlimited</option>
						</select>
			<br>
			<span class="vexpl">
			<?=gettext("Max log messages to fetch per Sqlite file.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Sqlite files: ");?></td>
                        <td width="78%" class="vtable">

                        	<?php if ($handle = opendir('/var/db/postfix')) {
                        		$total_files=0;
                        		$array_files=array();
                        		while (false !== ($file = readdir($handle)))
                        			if (preg_match("/(\d+-\d+-\d+).db$/",$file,$matches))
                        				$array_files[]=array($file,$matches[1]);
                        		closedir($handle);
                        		asort($array_files);
								foreach ($array_files as $file)
                        		$select_output= '<option value="'.$file[0].'">'.$file[1]."</option>\n" . $select_output;

                        			echo '<select name="drop1" id="Select1" size="'.(count($array_files)>10?10:count($array_files)+2).'" multiple="multiple">';
                        			echo $select_output;
                        			echo '</select><br><span class="vexpl">'.gettext("Select what database files you want to use in your search.").'</span></td></td>';
                        	                        			}?>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Message Fields: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="fields" size="14" multiple="multiple">
                        	<option value="date"   selected="selected">Date</option>
                        	<option value="from"   selected="selected">From</option>
                        	<option value="to" 	   selected="selected">To</option>
                        	<option value="delay" selected="selected">Delay</option>
                        	<option value="status" selected="selected">Status</option>
                        	<option value="status_info">Status Info</option>
                        	<option value="server">Server</option>
                        	<option value="subject">Subject</option>
				<option value="size">Size</option>
				<option value="sid">SID</option>
				<option value="msgid">msgid</option>
				<option value="bounce">bounce</option>
				<option value="relay">Relay</option>
				<option value="helo">Helo</option>
				<option value="log">Log source</option>
			</select>
			<br/>
			<span class="vexpl">
			<?=gettext("Max log messages to fetch per Sqlite file.");?>
			</span>
			</td>
			</tr>

			<tr>
			<td width="22%" valign="top"></td>
                        <td width="78%"><input name="Submit" type="button" class="formbtn" id="search" value="<?=gettext("Search");?>" onclick="getsearch_results('search')">
                        </td>
			</table>
			</form>
			</div>
			</div>
			</div>
			</div>

<!-- table results -->
<br/>
	<div class="panel panel-default" style="margin-right:auto;margin-left:auto;width:95%;">
        <div class="panel-body">
	<div id="search_results" class="table-responsive">
</div>
</div>
</div>
<script type="text/javascript">

function loopSelected(id)
{
  var selectedArray = new Array();
  var selObj = document.getElementById(id);
  var i;
  var count = 0;
  for (i=0; i<selObj.options.length; i++) {
    if (selObj.options[i].selected) {
      selectedArray[count] = selObj.options[i].value;
      count++;
    }
  }
  return(selectedArray);
}

function getsearch_results(sbutton,Wday,Wstatus) {
		var $new_from=$('#from').val().replace("\n", "','");
		var $new_to=$('#to').val().replace("\n", "','");
		var $new_sid=$('#sid').val().replace("\n", "','");

		//check if its a widget funcion call
		if (typeof Wday != "undefined") {
		  var $files= Wday + '.db';
		} else {
		  var $files="";
 		  $('#Select1').each(function () {
                                        var sThisVal = (this.checked ? "1" : "0");
                                        $files += ($files=="" ? $(this).val() : "," + $(this).val());
                                        });

		}

		//check if its a widget funcion call
                if (typeof Wstatus != "undefined") {
                   var $status= Wstatus;
	   	   var $fields='date,from,to,status,subject,status_info,delay';
		   var $queue = 'ALL';
                } else {
                   var $status= $('#status').val();
		   var $fields="";
 		   $('#fields').each(function () {
                                        var sThisVal = (this.checked ? "1" : "0");
                                        $fields += ($fields=="" ? $(this).val() : "," + $(this).val());
                                        });

                }
		var $errors=0;
		//alert($status + ' ' + $files + ' ' + $queue);
		if ( $files == "null" ){
			alert ("Please select at least one file.");
			$errors++;
			}
		if ( $fields == "null" ){
			alert ("Please select at least one message field to display results.");
			$errors++;
			}
		else{
		if (sbutton == "search"){
			$('search').value="Searching...";}
		else{
			$('export').value="exporting...";}
		$('search_results').innerHTML="";
		var url = "/postfix.php";

		if ($errors === 0) {
			jQuery.ajax(url,
                		{
                		type: 'post',
                		data: {
					from: 	$new_from,
					to:	$new_to,
					sid:	$new_sid,
					limit:	$('#queuemax').val(),
					fields:	$fields,
					status:	$status,
					server:	$('#server').val(),
					subject:$('#subject').val(),
					msgid:	$('#msgid').val(),
					files:	$files,
					queue:	'all',
					relay:	$('#relay').val(),
					sbutton:sbutton
                        	},
                		success: function(ret){
					$('#search_results').html(ret);
	                		scroll(0,1100);
        	        		$('#search').value="Search";
                			$('#export').value="Export";
                        		//$('#' + content).html(ret);
                        	}
                	}
                	);

			}
		 }

	function activitycallback_postfix_search(transport) {
		$('search_results').innerHTML = transport.responseText;
		scroll(0,1100);
		$('search').value="Search";
		$('export').value="Export";
	}
}
<?
if (isset($_REQUEST['widget'])){
	list($wfile,$wstatus)=explode(",",$_REQUEST['widget']);
	$queue_select= ($wstatus=='reject' ? 'NOQUEUE' : 'QUEUE');
	$select_search=<<<EOF
$( document ).ready(function() {
	$('#Select1').val('{$wfile}.db').change();
	$('#queuetype').val('{$queue_select}').change();
	$('#queuemax').val('unlimited').change();
	getsearch_results('search','{$wfile}','{$wstatus}')
	});
EOF;
print $select_search;
}
?>

</script>
<!-- </form> -->
<?php include("foot.inc"); ?>
</body>
</html>
