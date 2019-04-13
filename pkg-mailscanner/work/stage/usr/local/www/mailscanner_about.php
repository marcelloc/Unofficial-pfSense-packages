<?php

/*
 * mailscanner_about.php
 *
 * part of pfSense (https://www.pfsense.org)
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

$pgtitle = array(gettext("Package"), gettext("Services: Mailscanner"), gettext("About"));
$shortcut_section = "mailscanner";
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("General"), false, "/pkg_edit.php?xml=mailscanner.xml&id=0");
$tab_array[] = array(gettext("Attachments"), false, "/pkg_edit.php?xml=mailscanner_attachments.xml&id=0");
$tab_array[] = array(gettext("Antivirus"), false, "/pkg_edit.php?xml=mailscanner_antivirus.xml&id=0");
$tab_array[] = array(gettext("Content"), false, "/pkg_edit.php?xml=mailscanner_content.xml&id=0");
$tab_array[] = array(gettext("AntiSpam"), false, "/pkg_edit.php?xml=mailscanner_antispam.xml&id=0");
$tab_array[] = array(gettext("Alerts"), false, "/pkg_edit.php?xml=mailscanner_alerts.xml&id=0");
$tab_array[] = array(gettext("Reporting"), false, "/pkg_edit.php?xml=mailscanner_report.xml&id=0");
$tab_array[] = array(gettext("XMLRPC Sync"), false, "/pkg_edit.php?xml=mailscanner_sync.xml&id=0");
$tab_array[] = array(gettext("Help"), true, "/mailscanner_about.php");
display_top_tabs($tab_array);

?>
<link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
<div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title"><?=gettext("About Mailscanner Forwarder"); ?></h2></div>
        <div class="panel-body">
        <div class="table-responsive">
		<table class="table table-hover table-condensed">
                                <tbody>

						<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Credits");?>&nbsp;</td>
                        <td width="78%" class="vtable"><?=gettext("Package Created by <a target=_new href='https://forum.pfsense.org/index.php?action=profile;u=4710'>Marcello Coutinho</a><br><br>");?></td>
                        </tr>
						<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Donatios");?>&nbsp;</td>
                        <td width="78%" class="vtable"><?=gettext("If you like this package, please donate for this community package developer.<br><br>");?></td>
                        </tr>
						</table>

				</div>
			</td>
		</tr>


	</table>
	<br>
<?php include("foot.inc"); ?>
</body>
</html>
