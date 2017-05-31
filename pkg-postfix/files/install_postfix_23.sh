#!/bin/sh

# *
# * install_postfix_23.sh
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
prefix=https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-postfix/files

# /etc/inc files
file=/etc/inc/priv/postfix.priv.inc
fetch -q -o $file $prefix/$file

check_service_file=check_postfix_service.php
fetch -q -o /root/$check_service_file $prefix/$check_service_file

# /usr/local files

#datatable files
dtdir=www/vendor/datatable

for share_dir in /usr/local/share/pfSense-pkg-postfix/ \
		/usr/local/www/vendor/datatable/css \
		/usr/local/www/vendor/datatable/js \
		/usr/local/www/vendor/datatable/images
   do 
	if [ ! -d $share_dir ];then
		mkdir -p $share_dir
	fi
   done

for file in 	bin/adexport.pl pkg/postfix.inc pkg/postfix.xml pkg/postfix_acl.xml pkg/postfix_antispam.xml \
		pkg/postfix_domains.xml pkg/postfix_recipients.xml pkg/postfix_sync.xml share/pfSense-pkg-postfix/info.xml \
		www/postfix.php www/postfix_about.php www/postfix_queue.php www/postfix_recipients.php www/postfix_search.php \
		www/postfix_view_config.php www/shortcuts/pkg_postfix.inc www/widgets/widgets/postfix.widget.php \
		pkg/postfix_dkim.inc $dtdir/se-1.2.0.zip $dtdir/css/jquery.dataTables.min.css bin/sa-learn-pipe.sh\
		$dtdir/js/jquery.dataTables.min.js www/postfix.sql.php bin/postwhite pkg/postfix_postwhite.template \
		www/postfix_cloud_domains.php www/postfix_migrate_config.php pkg/postfix_dmarc.inc pkg/postfix_postfwd.inc
 do
	echo "fetching  /usr/local/$file from github"
	fetch -q -o /usr/local/$file $prefix/usr/local/$file
done

fetch -q -o /root/postfix_report.php $prefix/root/postfix_report.php
fetch -q -o /root/mail_report.sh $prefix/root/mail_report.sh

#fix some permissions
chmod +x /root/mail_report.sh
chmod +x /usr/local/bin/postwhite
chmod +x /root/postfix_report.php
chmod +x /usr/local/bin/adexport.pl 
chmod +x /usr/local/www/postfix.php
chmod +x /usr/local/bin/sa-learn-pipe.sh

#other minor fixes
cp /usr/local/$dtdir/DataTables-1.10.13/images/sort_both.png /usr/local/$dtdir/images/sort_both.png
cp /usr/local/$dtdir/DataTables-1.10.13/images/sort_asc.png /usr/local/$dtdir/images/sort_asc.png

# Enable freebsd Repo
repo_dir=/root/repo.bkp
mkdir -p $repo_dir
rm -f $repo_dir/*conf
cp /usr/local/etc/pkg/repos/*conf $repo_dir
sed -i "" -E "s/(FreeBSD.*enabled:) no/\1 yes/" /usr/local/etc/pkg/repos/*conf

# Install postfix package
pkg update
pkg install postfix-sasl libspf2 opendkim libmilter py27-postfix-policyd-spf-python p5-perl-ldap postfix-postfwd opendmarc pflogsumm zip

# restore repository configuration state
#cp /root/pfSense.bkp.conf $repo2
cp $repo_dir/*conf /usr/local/etc/pkg/repos/.

#check some libs
if [ ! -f /usr/local/lib/libmilter.so.5 ];then
  ln -s /usr/local/lib/libmilter.so.6 /usr/local/lib/libmilter.so.5
fi

fetch -q -o /usr/local/etc/postfix/yahoo_static_hosts.txt https://raw.githubusercontent.com/stevejenkins/postwhite/master/yahoo_static_hosts.txt

#install spf tools
if [ -f master.zip ];then
  mv master.zip master.old.zip
fi

if [ ! -d /usr/local/bin/spf-tools ];then
   fetch https://github.com/jsarenik/spf-tools/archive/master.zip
   unzip master.zip
   mv spf-tools-master /usr/local/bin/spf-tools
   rm -f master.zip
fi
#check postwhite
if [ ! -f /usr/local/etc/postfix/postscreen_spf_whitelist.cidr ];then
 /usr/local/bin/bash /usr/local/bin/postwhite
fi

#install services and menus
php /root/check_postfix_service.php

# unzip datagrid modules
cd /usr/local/$dtdir
/usr/bin/unzip -o se-1.2.0.zip 
cd -

echo "updating soft bounce message status on databases.."
for a in /var/db/postfix/20*db

do
echo -n $a
echo "update mail_noqueue set status='soft bounce' where status_info like '%Service currently unavailable%';" | sqlite3 $a
echo " ok"
done

# pkg unlock pkg

php /usr/local/www/postfix_migrate_config.php
fi
