<?php

function sync_tinyproxy(){
	global $config;
	$tinyproxy_server=$config['system']['hostname']. "." .$config['system']['domain'];

	$tcf_file=<<<EOF

User nobody
Group nobody
Listen 127.0.0.1
Port 8888
Timeout 600
DefaultErrorFile "/usr/local/share/tinyproxy/default.html"
StatFile "/usr/local/share/tinyproxy/stats.html"
LogFile "/var/log/tinyproxy.log"
LogLevel Info
PidFile "/var/run/tinyproxy.pid"
MaxClients 1000
MinSpareServers 5
MaxSpareServers 20
StartServers 20
MaxRequestsPerChild 0
Allow 127.0.0.1
ViaProxyName "tinyproxy"
ConnectPort 443
ConnectPort 563

EOF;

	file_put_contents("/usr/local/etc/tinyproxy.conf",$tcf_file,LOCK_EX);
}

function start_tinyproxy(){
	exec('/usr/local/etc/rc.d/tinyproxy onerestart || /usr/local/etc/rc.d/tinyproxy onestart');
}


function stop_tinyproxy(){
	exec('/usr/local/etc/rc.d/tinyproxy onestop');
}
?>
