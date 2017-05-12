<?php

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");

global $config;
$domains=array();

print "checking current domains configuration...";
if (is_array($config['installedpackages']['postfixdomains'])) {
	$postfix_domains=$config['installedpackages']['postfixdomains']['config'][0];
		if (is_array($postfix_domains['row'])) {
			foreach ($postfix_domains['row'] as $postfix_row) {
				$domains[$postfix_row['domain']] = array('domain' => $postfix_row['domain'],
									'mailserverip' => $postfix_row['mailserverip'],
									'dkim' => $postfix_row['dkim'],
									'bits' => $postfix_row['bits']);
			}
	}
}
print "ok\nMerging current dkim keys with domain config...";
//var_dump($domain);
if (is_array($config['installedpackages']['postfixdkim'])) {
	foreach ($config['installedpackages']['postfixdkim']['config'][0] as $dkims) {
		foreach ($dkims as $dkim) {
			$domains[$dkim['domain']]['private'] = $dkim['private'];
			$domains[$dkim['domain']]['pub'] = $dkim['pub'];		
		}
	}
}
print "ok";
foreach ($domains as $domain) {
	$config['installedpackages']['postfixdomainsng']['config'][]=$domain;
	unset($config['installedpackages']['postfixdomains']);
	unset($config['installedpackages']['postfixdkim']);
	
}
write_config();

?>
