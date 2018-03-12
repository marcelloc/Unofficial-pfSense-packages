# E2guardian Unofficial packages for pfSense software

As many people knows, Netgate has trimed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

# Install instructions

enable unofficial repo under pfSense 2.4

fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficial.24.conf

E2guardian requires a cache/upstream server , so if you do not want to use squid together with e2guardian, point e2guardian proxy configuration to 127.0.0.1 port 8888 to use tinyproxy lightweight cache server instead.

http://www.shallalist.de/Downloads/shallalist.tar.gz is one of compatible blacklists for e2guardian. Configure it under blacklist tab.

Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.
