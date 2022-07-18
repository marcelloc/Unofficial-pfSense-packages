<?php
/*
 * zfs_compression.widget.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) Scott Dale
 * Copyright (c) 2004-2005 T. Lechat <dev@lechat.org>
 * Copyright (c) Jonathan Watt <jwatt@jwatt.org>
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2021 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally part of m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("pkg-utils.inc");

function get_zfs_stats() {
    exec('zfs get compressratio',$zfs_list);
    if (empty($zfs_list)) {
        print_info_box(gettext("No zfs compression info."), 'warning', false);
        return;
    }
    
    print("<thead>\n");
    print(	"<tr>\n");
    print(		"<th>" . gettext("Name")     . "</th>\n");
    print(		"<th>" . gettext("Ratio")  . "</th>\n");
    print(	"</tr>\n");
    print("</thead>\n");
    print("<tbody>\n");
    
    foreach ($zfs_list as $zfs) {
        $fld = explode(" ",preg_replace("/\s+/"," ",$zfs));
        if ($fld[0] == "NAME") {
            continue;
        }
        $txtcolor = "";
        $upgradeavail = false;
        $vergetstr = "";
        $missing = false;
        $status = gettext('ok');
        $statusicon = 'check';
        
        print("<tr>\n");
        print(		'<td><span class="' . $txtcolor . '">' . $fld[0] . "</span></td>\n");
        print(		"<td>\n");
        print(			'<i title="' . $fld[0] . '" class="fa fa-compress"></i> ');
        
        print("&nbsp;".$fld[2]);
        
        print(	"</td>\n");
        print("</tr>\n");
    }
    
    print("</tbody>\n");
    
}
?>

<div class="table-responsive">
	<table id="pkgtbl" class="table table-striped table-hover table-condensed">
		<?php get_zfs_stats(); ?>
	</table>
</div>

