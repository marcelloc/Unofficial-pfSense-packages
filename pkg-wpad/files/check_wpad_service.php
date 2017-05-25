<?php
require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("xmlrpc.inc");
require_once("xmlrpc_client.inc");

global $config;

if ( ! is_array($config['installedpackages'])) {
	$config['installedpackages']=array();
}

if ( ! is_array($config['installedpackages']['menu'])) {
        $config['installedpackages']['menu']=array();
}


// Check service configuration
$found=array(0,0,0,0,0);
$write_config=0;

//Check menu configuration

$found=array(0,0,0);
foreach ($config['installedpackages']['menu'] as $menu) {
	switch ($menu['name']) {
		case 'Wpad':
			$found[0]++;
			break;
	}

}

if ( $found[0] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(	'name' => 'Wpad',
						'tooltiptext' => 'automatic proxy configuration tool',
						'section' => 'Services',
						'url' => '/pkg.php?xml=wpad.xml');
}

print "$write_config";
if ( $write_config > 0 ) {
	print "creating menu...\n";
	write_config('Installing unofficial wpad package');
}

?>
