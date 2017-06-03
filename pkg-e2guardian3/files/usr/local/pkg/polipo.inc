<?php

function sync_polipo(){
	global $config;
	$polipo_server=$config['system']['hostname']. "." .$config['system']['domain'];

	$pcf_file=<<<EOF

proxyAddress = "127.0.0.1"    # IPv4 only
allowedClients = 127.0.0.1
proxyName = {$polipo_server}

chunkHighMark = 50331648
objectHighMark = 16384

diskCacheFilePermissions=0640
diskCacheDirectoryPermissions=0750

diskCacheRoot = ""

EOF;

	file_put_contents("/usr/local/etc/polipo/config",$pcf_file,LOCK_EX);
}

function start_polipo(){
	exec('/usr/local/etc/rc.d/polipo onerestart || /usr/local/etc/rc.d/polipo onestart');
}


function stop_polipo(){
	exec('/usr/local/etc/rc.d/polipo onestop');
}
?>
