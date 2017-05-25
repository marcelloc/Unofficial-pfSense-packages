<?php

/*
 * wpad.priv.inc
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

$priv_list['page-services-wpad'] = array();
$priv_list['page-services-wpadr']['name'] = "WebCfg -Services: Wpad package";
$priv_list['page-services-wpad']['descr'] = "Allow access to Wpad package GUI";

$priv_list['page-services-wpad']['match'] = array();
$priv_list['page-services-wpad']['match'][] = "pkg.php?xml=wpad.xml*";
$priv_list['page-services-wpad']['match'][] = "pkg_edit.php?xml=wpad.xml*";
$priv_list['page-services-wpad']['match'][] = "pkg_edit.php?xml=wpad_sync.xml*";

?>
