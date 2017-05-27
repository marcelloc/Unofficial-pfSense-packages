# Wpad Unofficial packages for pfSense software

As many people knows, Netgate has trimed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

The wpad package confgigures antoher nginx http instance to host wpad file and proxy/squidguard error page as well. This way you can keep you firewall GUI under HTTPS.

# Install instructions

Under console/ssh, fetch the install script, check what it does if you want and then execute it.

cd /root

fetch https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-wpad/files/install_wpad_23.sh

sh ./install_wpad_23.sh

Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.
