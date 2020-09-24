# Mailscanner Unofficial packages for pfSense® software

UPDATE: Since 2.4.4, only official packages are listed under package manager by default.
To install unofficial/extra packages on pfSense 2.4.4 or higher, you need to apply via system patches the file 244_unofficial_packages_list.patch

Unofficial packages for pfSense software



As many people know already, Netgate has removed a lot of packages from official repo since pfSense® 2.3.

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

# Install instructions

You can enable unoffical repo running the commands below via SSH for pfSense 2.3 or higher

fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficial.conf

After fetching the repo file and applying the patch described above, you will be able to see these packages under System -> Package Manager

# CPAN modules - ATTENTION!

Once you install, some cpan modules will be still missing . I consider they important to spamassassin to work properly but to install it on freebsd or pfSense, you will need gcc and other compile stuff installed.

Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Digest::SHA1 ('require' failed)
Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Geo::IP ('require' failed)
Mar 28 17:15:48.929 [92279] dbg: diag: [...] module not installed: Net::CIDR::Lite ('require' failed)
Mar 28 17:15:48.930 [92279] dbg: diag: [...] module not installed: Razor2::Client::Agent ('require' failed)
Mar 28 17:15:48.931 [92279] dbg: diag: [...] module not installed: LWP::UserAgent ('require' failed)
Mar 28 17:15:48.931 [92279] dbg: diag: [...] module not installed: Net::Patricia ('require' failed)

If you decide to complete spamassassin features, run this second script to pfSense be able to compile cpan modules.

cd /root

pkg install gcc8

rehash

fetch https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-mailscanner/files/install_cpan_modules_24.sh

sh ./install_cpan_modules_24.sh


Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.


