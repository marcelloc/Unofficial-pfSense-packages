<?php

/*
 * postfix_about.php
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

$pgtitle = array(gettext("Package"), gettext("Services: Postfix relay and antispam"), gettext("About"));
$shortcut_section = "postfix";
include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("General"), false, "/pkg_edit.php?xml=postfix.xml&id=0");
$tab_array[] = array(gettext("Domains"), false, "/pkg.php?xml=postfix_domains.xml");
$tab_array[] = array(gettext("Recipients"), false, "/pkg_edit.php?xml=postfix_recipients.xml&id=0");
$tab_array[] = array(gettext("Access Lists"), false, "/pkg_edit.php?xml=postfix_acl.xml&id=0");
$tab_array[] = array(gettext("Antispam"), false, "/pkg_edit.php?xml=postfix_antispam.xml&id=0");
$tab_array[] = array(gettext("Sync"), false, "/pkg_edit.php?xml=postfix_sync.xml&id=0");
$tab_array[] = array(gettext("View config"), false, "/postfix_view_config.php");
$tab_array[] = array(gettext("Search mail"), false, "/postfix_search.php");
$tab_array[] = array(gettext("Queue"), false, "/postfix_queue.php");
$tab_array[] = array(gettext("About"), true, "/postfix_about.php");
display_top_tabs($tab_array);

?>
<link rel="stylesheet" href="/vendor/datatable/css/jquery.dataTables.min.css">
<div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title"><?=gettext("About Postfix Forwarder"); ?></h2></div>
        <div class="panel-body">
        <div class="table-responsive">
		<table class="table table-hover table-condensed">
                                <tbody>

						<tr>
                        <td width="22%" valign="top" class="vncell"><?=gettext("Credits");?>&nbsp;</td>
                        <td width="78%" class="vtable"><?=gettext("Package v2 Created by <a target=_new href='https://forum.pfsense.org/index.php?action=profile;u=4710'>Marcello Coutinho</a><br><br>");?></td>
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
