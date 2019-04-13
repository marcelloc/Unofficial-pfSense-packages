<?php

global $priv_list;

$priv_list['page-services-mailscanner'] = array();
$priv_list['page-services-mailscanner']['name'] = "WebCfg - Services: Mailscanner config.";
$priv_list['page-services-mailscanner']['descr'] = "Allow access to mailscanner gui pages.";
$priv_list['page-services-mailscanner']['match'] = array();
$priv_list['page-services-mailscanner']['match'][] = "pkg_edit.php?xml=mailscanner*";
$priv_list['page-services-mailscanner']['match'][] = "mailscanner*.php*";

?>
