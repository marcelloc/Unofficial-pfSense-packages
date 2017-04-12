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
		www/shortcuts/pkg_mailscanner.inc bin/sa-updater-custom-channels.sh
do
	echo "fetching  /usr/local/$file from github"
	fetch -q -o /usr/local/$file $prefix/usr/local/$file
done

# Enable freebsd Repo
repo1=/usr/local/etc/pkg/repos/FreeBSD.conf
repo2=/usr/local/etc/pkg/repos/pfSense.conf
cp $repo1 /root/FreeBSD.bkp.conf
echo "FreeBSD: { enabled: yes  }" > $repo1

cp $repo2 /root/pfSense.bkp.conf
cp /usr/local/etc/pkg/repos/pfSense.conf /root/pfSense.bkp.conf
cat $repo2 | sed "s/enabled: no/enabled: yes/" > /tmp/pfSense.conf &&
cp /tmp/pfSense.conf $repo2

#fix permission
chmod +x /usr/local/bin/sa-updater-custom-channels.sh

# Install mailscanner package
pkg install mailscanner bash dcc-dccd spamassassin

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
cp DecodeShortURLs-master/*pm $plugin_dir/plugins/
cp DecodeShortURLs-master/*cf $plugin_dir

# update spamassassin database
rehash
/usr/local/bin/sa-update -D

fi
