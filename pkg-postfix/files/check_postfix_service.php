<?php
require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("xmlrpc.inc");
require_once("xmlrpc_client.inc");
require_once("/usr/local/pkg/postfix.inc");


global $config;

if ( ! is_array($config['installedpackages'])) {
	$config['installedpackages']=array();
}

if ( ! is_array($config['installedpackages']['service'])) {
        $config['installedpackages']['service']=array();
}

if ( ! is_array($config['installedpackages']['menu'])) {
        $config['installedpackages']['menu']=array();
}


// Check service configuration
$found=array(0,0,0,0,0);
$write_config=0;
foreach ($config['installedpackages']['service'] as $service) {
	if ($service['name'] == 'postfix') {
		$found[3]++;
	}
	if ($service['name'] == 'opendkim') {
                $found[4]++;
        }
	if ($service['name'] == 'opendmarc') {
                $found[5]++;
        }

}
if ( $found[3] == 0 ) {
	$write_config++;
	$config['installedpackages']['service'][]=array('name' => 'postfix',
							'rcfile' => 'postfix.sh',
							'executable' => 'master',
							'description' => 'postfix'); 
}

if ( $found[4] == 0 ) {
        $write_config++;
        $config['installedpackages']['service'][]=array('name' => 'opendkim',
                                                        'rcfile' => 'milter-opendkim.sh',
                                                        'executable' => 'opendkim',
                                                        'description' => 'Open domain keys service');
}

if ( $found[5] == 0 ) {
        $write_config++;
        $config['installedpackages']['service'][]=array('name' => 'opendmarc',
                                                        'rcfile' => 'opendkim.sh',
                                                        'executable' => 'opendmarc',
                                                        'description' => 'Open dmarc service');
}

//Check menu configuration

$found=array(0,0,0);
foreach ($config['installedpackages']['menu'] as $menu) {
	switch ($menu['name']) {
		case 'Postfix Forwarder':
			$found[0]++;
			break;
		case 'Search Mail':
                        $found[1]++;
                        break;
		case 'Postfix Queue':
                        $found[2]++;
                        break;
	}

}

if ( $found[0] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(	'name' => 'Postfix Forwarder',
						'tooltiptext' => 'Configure Postfix Forwarder',
						'section' => 'Services',
						'url' => '/pkg_edit.php?xml=postfix.xml&amp;id=0');
}

if ( $found[1] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(   'name' => 'Search Mail',
                                                'tooltiptext' => 'Search postfix logs',
                                                'section' => 'Diagnostics',
                                                'url' => '/postfix_search.php');
}
if ( $found[2] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(   'name' => 'Postfix Queue',
                                                'tooltiptext' => 'Check postfix queue',
                                                'section' => 'Status',
                                                'url' => '/postfix_queue.php');
}

print "$write_config";
if ( $write_config > 0 ) {
	print "creating menu and services...\n";
	write_config('Installing unofficial postfix package');
}

?>
