UPDATE: Since 2.4.4, only official packages are listed under package manager by default.
To install unofficial/extra packages on pfSense 2.4.4 or higher, you need to apply via system patches the file 244_unofficial_packages_list.patch

Unofficial packages for pfSense software



As many people know already, Netgate has removed a lot of packages from official repo since pfSense® 2.3.

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

Install instructions:

You can enable unoffical repo running the commands below via SSH for pfSense 2.3 or higher

fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficial.conf

After fetching the repo file, you will be able to see these packages under System -> Package Manager
