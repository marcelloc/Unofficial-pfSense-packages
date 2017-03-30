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
	if ($service['name'] == 'e2guardian') {
		$found[3]++;
	}
	if ($service['name'] == 'tinyproxy') {
                $found[4]++;
        }

}
if ( $found[3] == 0 ) {
	$write_config++;
	$config['installedpackages']['service'][]=array('name' => 'e2guardian',
							'rcfile' => 'e2guardian.sh',
							'executable' => 'e2guardian',
							'description' => 'e2guardian'); 
}

if ( $found[4] == 0 ) {
        $write_config++;
        $config['installedpackages']['service'][]=array('name' => 'tinyproxy',
                                                        'rcfile' => 'tinyproxy',
                                                        'executable' => 'tinyproxy',
                                                        'description' => 'Light http proxy');
}

//Check menu configuration

$found=array(0,0,0);
foreach ($config['installedpackages']['menu'] as $menu) {
	switch ($menu['name']) {
		case 'E2guardian Proxy':
			$found[0]++;
			break;
	}

}

if ( $found[0] == 0 ) {
$write_config++;
$config['installedpackages']['menu'][]=array(	'name' => 'E2guardian Proxy',
						'tooltiptext' => 'Configure E2guardian',
						'section' => 'Services',
						'url' => '/pkg_edit.php?xml=e2guardian.xml&amp;id=0');
}

print "$write_config";
if ( $write_config > 0 ) {
	print "creating menu and services...\n";
	write_config('Installing unofficial e2guardian package');
}

?>
