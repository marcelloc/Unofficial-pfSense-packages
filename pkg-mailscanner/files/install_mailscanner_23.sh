#!/bin/sh

# *
# * install_mailscanner_23.sh
# *
# * part of unofficial packages for pfSense(R) software
# * Copyright (c) 2011-2017 Marcello Coutinho
# * All rights reserved.
# *
# * Licensed under the Apache License, Version 2.0 (the "License");
# * you may not use this file except in compliance with the License.
# * You may obtain a copy of the License at
# *
# * http://www.apache.org/licenses/LICENSE-2.0
# *
# * Unless required by applicable law or agreed to in writing, software
# * distributed under the License is distributed on an "AS IS" BASIS,
# * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# * See the License for the specific language governing permissions and
# * limitations under the License.

ASSUME_ALWAYS_YES=YES
export ASSUME_ALWAYS_YES

if [ "$(cat /etc/version | cut -c 1-3)" == "2.3" ]; then
prefix=https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-mailscanner/files

check_service_file=check_mailscanner_service.php
fetch -q -o /root/$check_service_file $prefix/$check_service_file

# /usr/local files

for file in 	www/mailscanner_about.php pkg/mailscanner.conf.template pkg/mailscanner.xml pkg/mailscanner_antispam.xml \
		pkg/mailscanner_attachments.xml pkg/mailscanner_report.xml pkg/mailscanner.inc pkg/mailscanner_alerts.xml \
		pkg/mailscanner_antivirus.xml pkg/mailscanner_content.xml pkg/mailscanner_sync.xml \
		www/shortcuts/pkg_mailscanner.inc bin/sa-updater-custom-channels.sh bin/sa-wrapper.pl
do
	echo "fetching  /usr/local/$file from github"
	fetch -q -o /usr/local/$file $prefix/usr/local/$file
done

# Enable freebsd Repo
repo_dir=/root/repo.bkp
mkdir -p $repo_dir
rm -f $repo_dir/*conf
cp /usr/local/etc/pkg/repos/*conf $repo_dir
sed -i "" -E "s/(FreeBSD.*enabled:) no/\1 yes/" /usr/local/etc/pkg/repos/*conf

#fix permission
chmod +x /usr/local/bin/sa-updater-custom-channels.sh
chmod +x /usr/local/bin/sa-wrapper.pl

# Install mailscanner package
# pkg lock pkg
pkg update
pkg install mailscanner bash dcc-dccd spamassassin p7zip rsync

# restore repository configuration state
cp $repo_dir/*conf /usr/local/etc/pkg/repos/.

#install services and menus
php /root/check_mailscanner_service.php

#install spamassassin-extremeshok_fromreplyto
plugin_dir=/usr/local/etc/mail/spamassassin
plugin_file=extremeshok_fromreplyto.zip

if [ ! -d $plugin_dir/plugins ];then
	mkdir -p $plugin_dir/plugins
fi

cd root

fetch -o $plugin_file https://github.com/extremeshok/spamassassin-extremeshok_fromreplyto/archive/master.zip
unzip -o $plugin_file
cp spamassassin-extremeshok_fromreplyto-master/plugins/*pm $plugin_dir/plugins/
cp spamassassin-extremeshok_fromreplyto-master/01_extremeshok_fromreplyto.cf $plugin_dir

#install shorturl mailscanner plugin
plugin_file=DecodeShortURLs.zip
fetch -o $plugin_file https://github.com/smfreegard/DecodeShortURLs/archive/master.zip
unzip -o $plugin_file
cp DecodeShortURLs-master/*pm $plugin_dir
cp DecodeShortURLs-master/*cf $plugin_dir

#install 7z and pdf patch
plugin_file=pdfid.zip 
fetch -o $plugin_file http://didierstevens.com/files/software/pdfid_v0_2_1.zip
unzip -o $plugin_file
cp p*py /usr/local/bin/
chmod +x /usr/local/bin/p*py
#fix python path
sed -i '.bak' "s@/usr/bin/env python@/usr/local/bin/python2@" /usr/local/bin/p*.py

#install unofficial sigs for improving malware protection
plugin_file=clamav-unofficial-sigs.zip
fetch -o $plugin_file https://github.com/extremeshok/clamav-unofficial-sigs/archive/master.zip
unzip -o $plugin_file
script_file=/usr/local/sbin/clamav-unofficial-sigs.sh
plugin_dir=clamav-unofficial-sigs
cp ${plugin_dir}-master/clamav-unofficial-sigs.sh $script_file

chmod +x $script_file
sed -i '.bak' "s@!/bin/bash@!/usr/local/bin/bash@" $script_file
for c_dir in /etc/$plugin_dir/ /var/log/$plugin_dir/
do
        if [ ! -d $c_dir ];then
                mkdir $c_dir
        fi
done
cp ${plugin_dir}-master/config/* /etc/$plugin_dir
cp /etc/$plugin_dir/os.pfsense.conf /etc/$plugin_dir/os.conf
sed -i '.bak' 's@clam_user=.*@clam_user="postfix"@' /etc/$plugin_dir/os.conf
sed -i '.bak' 's@#user_configuration.*@user_configuration_complete="yes"@' /etc/$plugin_dir/user.conf

# update spamassassin database
rehash
/usr/local/bin/sa-update -D

fi

for PatchFile in ConfigDefs.pl.patch Message.pm.patch SweepContent.pm.patch
  do
  fetch -o - -q $prefix/$PatchFile | patch -N -b -p0
  done

