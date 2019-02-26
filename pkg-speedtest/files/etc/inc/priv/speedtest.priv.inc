<?php

/*
 * speedtest.priv.inc
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

global $priv_list;

$priv_list['page-diagnostics-speedtest'] = array();
$priv_list['page-diagnostics-speedtest']['name'] = "WebCfg - Diagnostics: SpeedTest package";
$priv_list['page-diagnostics-speedtest']['descr'] = "Allow access to SpeedTest package GUI";

$priv_list['page-diagnostics-speedtest']['match'] = array();
$priv_list['page-diagnostics-speedtest']['match'][] = "pkg.php?xml=speedtest.xml*";
$priv_list['page-diagnostics-speedtest']['match'][] = "pkg_edit.php?xml=speedtest.xml*";
$priv_list['page-diagnostics-speedtest']['match'][] = "diag_speedtest.php*";

?>
