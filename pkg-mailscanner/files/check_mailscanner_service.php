<?php
require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("xmlrpc.inc");
require_once("xmlrpc_client.inc");
#require_once("/usr/local/pkg/postfix.inc");


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
	if ($service['name'] == 'mailscanner') {
		$found[3]++;
	}
	if ($service['name'] == 'clamd') {
                $found[4]++;
        }

}
if ( $found[3] == 0 ) {
	$write_config++;
	$config['installedpackages']['service'][]=array('name' => 'mailscanner',
							'rcfile' => 'mailscanner',
							'executable' => 'perl_mailscanner',
							'description' => 'MailScanner'); 
}

if ( $found[4] == 0 ) {
        $write_config++;
        $config['installedpackages']['service'][]=array('name' => 'clamd',
                                                        'rcfile' => 'clamav.sh',
                                                        'executable' => 'clamd',
                                                        'description' => 'Clamav antivirus');
}

//Check menu configuration

foreach ($config['installedpackages']['menu'] as $menu) {
	switch ($menu['name']) {
		case 'Mailscanner':
			$found[0]++;
			break;
	}

}

if ( $found[0] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(	'name' => 'Mailscanner',
						'tooltiptext' => 'Configure MailScanner service',
						'section' => 'Services',
						'url' => '/pkg_edit.php?xml=mailscanner.xml');
}

print "$write_config";
if ( $write_config > 0 ) {
	print "creating menu and services...\n";
	write_config('Installing unofficial mailscanner package');
}

?>
