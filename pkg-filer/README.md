# Filer Unofficial packages for pfSense software

As many people knows, Netgate has trimed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

# Install instructions

You can enable unoffical repo by downloading repo file with

fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficial.conf

and select the package under GUI

or

Using console/ssh, fetch the package manually.

pkg add https://github.com/marcelloc/Unofficial-pfSense-packages/raw/master/pkg-wpad/files/pfSense-pkg-Wpad-0.1.txz

This package does not include any binary files.

Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.
