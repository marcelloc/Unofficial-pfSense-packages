# Filer Unofficial packages for pfSense software

As many people knows, Netgate has trimed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

The filer package allows you to create files/scripts on file system and keep it on config.xml backup

# Install instructions

If you enabled the Unofficial repo, you can add this package under System -> Package Manager

Or add it under console/ssh.

cd /root

fetch https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-filer/files/install_filer_23.sh

sh ./install_filer_23.sh

Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.
