# Mailscanner Unofficial packages for pfSense software

As many people knows, Netgate has trimed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

# Install instructions

Under console/ssh, fetch the install script, check what it does if you want and then execute it.

cd /root

fetch https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-mailscanner/files/install_mailscanner_23.sh

sh ./install_mailscanner_23.sh


Once you install, some cpan modules will be still missing . I consider they important to spamassassin to work properly but to install it on freebsd or pfSense, you will need gcc and other compile stuff installed.

Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Digest::SHA1 ('require' failed)
Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Geo::IP ('require' failed)
Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Net::CIDR::Lite ('require' failed)
Mar 28 17:15:48.930 [92279] dbg: diag: [...] module not installed: Razor2::Client::Agent ('require' failed)
Mar 28 17:15:48.931 [92279] dbg: diag: [...] module not installed: LWP::UserAgent ('require' failed)
Mar 28 17:15:48.931 [92279] dbg: diag: [...] module not installed: Net::Patricia ('require' failed)

If you decide to complete spamassassin features, run this second script to pfSense be able to compile cpan modules.

cd /root

fetch https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-mailscanner/files/install_cpan_modules_23.sh

sh ./install_cpan_modules_23.sh


Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.


