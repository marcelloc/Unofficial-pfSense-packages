<?php
/*
 * postfix_queue.php
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

$pgtitle = array(gettext("Package"), gettext("Services: Postfix relay and antispam"), gettext("Queue"));
$shortcut_section = "postfix";


$uname=posix_uname();
if ($uname['machine']=='amd64')
        ini_set('memory_limit', '250M');

if ($savemsg) {
        print_info_box($savemsg);
}

define('POSTFIX_LOCALBASE','/usr/local');

if ($_REQUEST['cmd']!=""){
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

	if ($_REQUEST['cmd'] =='mailq'){
		#exec("/usr/local/bin/mailq" . escapeshellarg('^'.$m.$j." ".$hour.".*".$grep)." /var/log/maillog", $lists);
		exec(POSTFIX_LOCALBASE."/bin/mailq", $mailq);
		print '<table id="dtresult" class="display" width="98%" border="0" cellpadding="8" cellspacing="0">';
		$tss=array('thead','tfoot');
		$dbc=array('SID','size','date','sender','info','Recipient');
                foreach ($tss as $t){
                        $$t = "<" . $t . "><tr>\n";
                        foreach ($dbc as $c){
                           $$t .= "<th>".ucfirst($c)."</th>";
                        }
                        $$t .= "</tr></" . $t . ">";
                }
                print "{$thead}\n<tbody>\n";

		$sid="";
		foreach ($mailq as $line){
			if (preg_match("/Mail queue is empty",$line,$matches))
				print "{$matches[1]}";
			if(preg_match("/-Queue ID- --Size--/",$line,$matches))
				print"";
			elseif (preg_match("/(\w+)\s+(\d+)\s+(\w+\s+\w+\s+\d+\s+\d+:\d+:\d+)\s+(.*)/",$line,$matches)){
				print '<th>'.$td.$matches[1].'</th>'.$td.$matches[2].'</th>'.$td.$matches[3].'</th>'.$td.$matches[4];
				$sid=$matches[1];
				}
			elseif (preg_match("/(\s+|)(\W\w+.*)/",$line,$matches) && $sid !="")
				print $td.$matches[2].'</th>';
			elseif (preg_match("/\s+(\w+.*)/",$line,$matches) && $sid !=""){
				print $td.$matches[1].'</th></tr>';
				$sid="";
			}
		}
		//print '</table>';
		print "</tbody>\n{$tfoot}\n</table>";
	}
	if ($_REQUEST['cmd'] =='qshape'){
		if ($_REQUEST['qshape']!="")
			exec(POSTFIX_LOCALBASE."/bin/qshape -".preg_replace("/\W/","",$_REQUEST['type'])." ". preg_replace("/\W/","",$_REQUEST['qshape']), $qshape);
		else
			exec(POSTFIX_LOCALBASE."/bin/qshape", $qshape);

		print '<table id="dtresult" class="display" width="98%" border="0" cellpadding="8" cellspacing="0">';
		$sid="";
		foreach ($qshape as $line){
			if (preg_match("/\s+(T\s.*)/",$line,$matches)){
		                $dbc=explode (" ",preg_replace("/\s+/"," ",$matches[1]));
				$tss=array('thead','tfoot');

                		foreach ($tss as $t){
                        		$$t = "<" . $t . "><tr><th></th>\n";
                        			foreach ($dbc as $c){
							$$t .= "<th>{$c}</th>\n";
                        				}
                        		$$t .= "</tr></" . $t . ">";
                		}
                		print "{$thead}\n<tbody>\n";

				/*
				print "<tr>";
				foreach (explode (" ",preg_replace("/\s+/"," ",$matches[1])) as $count)
					print "<th>{$count}</th>";
				print "</tr>";
				*/
			}
			else{
				print "<tr>\n";
				$line=preg_replace("/^\s+/","",$line);
				$line=preg_replace("/\s+/"," ",$line);
				foreach (explode (" ",$line) as $count)
					print "<th>{$count}</th>\n";
				print "</tr>\n";
			}

		}
	}
print "</tbody>\n{$tfoot}\n</table>";

print <<<EOF
<script>
$(document).ready(function() {
    $('#dtresult').DataTable({
        scrollY:        '50vh',
        scrollCollapse: true,
        dom: 'Bfrtip',
        colReorder: {
            realtime: false
        },
        buttons: [
            {
                extend: 'copyHtml5',
                exportOptions: {
                 columns: ':contains("Office")'
                }
            },
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ]
        });
} );
</script>
EOF;

exit;
}

include("head.inc");

?>

	<form action="postfix_view_config.php" method="post">

	<div id="mainlevel">
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr><td>
	<?php
		$tab_array = array();
		$tab_array[] = array(gettext("General"), false, "/pkg_edit.php?xml=postfix.xml&id=0");
		$tab_array[] = array(gettext("Domains"), false, "/pkg.php?xml=postfix_domains.xml");
		$tab_array[] = array(gettext("Recipients"), false, "/pkg_edit.php?xml=postfix_recipients.xml&id=0");
		$tab_array[] = array(gettext("Access Lists"), false, "/pkg_edit.php?xml=postfix_acl.xml&id=0");
		$tab_array[] = array(gettext("Antispam"), false, "/pkg_edit.php?xml=postfix_antispam.xml&id=0");
		$tab_array[] = array(gettext("Sync"), false, "/pkg_edit.php?xml=postfix_sync.xml&id=0");
		$tab_array[] = array(gettext("View config"), false, "/postfix_view_config.php");
		$tab_array[] = array(gettext("Search mail"), false, "/postfix_search.php");
		$tab_array[] = array(gettext("Queue"), true, "/postfix_queue.php");
		$tab_array[] = array(gettext("About"), false, "/postfix_about.php");
		display_top_tabs($tab_array);
	?>
<link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
<script src="/vendor/jquery/jquery-1.12.0.min.js" type="text/javascript"></script>
<script src="/vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="/vendor/datatable/js/jquery.dataTables.min.js" type="text/javascript"></script>
<div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title"><?=gettext("Postfix Queue"); ?></h2></div>
        <div class="panel-body">
                <div class="table-responsive">
                        <form id="paramsForm" name="paramsForm" method="post" action="">
                        <table class="table table-hover table-condensed">
                                <tbody>
			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("queue command: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="cmd">
                        	<option value="mailq" selected="selected">mailq</option>
                        	<option value="qshape" selected>qshape</option>
						</select>
			<br/>
			<span class="vexpl">
			<?=gettext("Select queue command to run.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("update frequency: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="updatef">
                        	<option value="5" selected="selected">05 seconds</option>
                        	<option value="15">15 Seconds</option>
							<option value="30">30 Seconds</option>
							<option value="60">One minute</option>
							<option value="1" selected>Never</option>
						</select>
			<br/>
			<span class="vexpl">
			<?=gettext("Select how often queue cmd will run.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("qshape Report flags: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="qshape" multiple="multiple" size="5">
                        	<option value="hold">hold</option>
							<option value="incoming">incoming</option>
							<option value="active">active</option>
							<option value="deferred">deferred</option>
							<option value="maildrop">maildrop</option>
						</select>
			<br/>
			<span class="vexpl">
			<?=gettext("Select how often queue will be queried.");?>
			</span>
			</td>
			</tr>

			<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("qshape Report type: ");?></td>
                        <td width="78%" class="vtable">
                        <select name="drop3" id="qtype">
							<option value="s" selected>sender domain</option>
							<option value="p">parent domain</option>
						</select>
			<br/>
			<span class="vexpl">
			<?=gettext("Select between sender or parent domains to order by.");?>
			</span>
			</td>
			</tr>

			<tr>
			<td width="22%" valign="top"></td>
                        <td width="78%"><input name="Submit" type="button" class="formbtn" id="run" value="<?=gettext("show queue");?>" onclick="get_queue('mailq')"><div id="search_help"></div></td>
			</table>
			</div>
			</td>
			</tr>

			</table>
			<br>
				<div>
				<table class="tabcont" width="100%" border="0" cellpadding="8" cellspacing="0">
								<tr>
	     						<td class="tabcont" >
	     						<div id="file_div"></div>

								</td>
							</tr>
						</table>
					</div>
	</div>
	</form>
<br/>
        <div class="panel panel-default" style="margin-right:auto;margin-left:auto;width:95%;">
        <div class="panel-body">
        <div id="search_results" class="table-responsive">
</div>
</div>
</div>

	<script type="text/javascript">
	function get_queue(loop) {
			//prevent multiple instances
			if ($('#run').val()=="running..." && loop!= 'running'){
			$('#updatef').val(1);
			$('#run').val("show queue");

			}
			if ($('#run').val()=="show queue" || loop== 'running'){
				$('#run').val("running...");
				$('#search_help').innerHTML ="<br><strong>You can change options while running.<br>To Stop search, change update frequency to Never.</strong>";
				var q_args="";
				$('#qshape').each(function () {
                                        var sThisVal = (this.checked ? "1" : "0");
                                        q_args += (q_args=="" ? $(this).val() : "," + $(this).val());
                                        });
				//var pars = 'cmd='+$('cmd').options[$('cmd').selectedIndex].value;
				//var pars = pars + '&qshape='+q_args;
				//var pars = pars + '&type='+$('qtype').options[$('qtype').selectedIndex].value;
				var url = "/postfix_queue.php";
				var $errors=0;
				if ( q_args == "null" ){
	                        	alert ("Please select at least one qshape Report flags.");
					$('#updatef').val(1);
                        		$('#run').val("show queue");
        	        	        $errors++;
                	       	 }
				if ($errors === 0) {
	                        jQuery.ajax(url,
        	                        {
                	                type: 'post',
                        	        data: {
						cmd:	$('#cmd').val(),
                                	        qshape: q_args,
						type:	$('#type').val(),
                                	},
                           	     success: function(ret){
                                	        $('#search_results').html(ret);
                                        	scroll(0,1100);
						//$('#run').val("show queue");
						//alert($('#updatef').val());
						if($('#updatef').val() > 1){
			                                setTimeout('get_queue("running")', $('#updatef').val() * 1000);
                        			} else {
							$('#run').val("show queue");
						}

                               		}
                        	}
                        	);

                        	}

				}
			}
	</script>
	<?php
	include("fend.inc");
	?>
	</body>
	</html>
