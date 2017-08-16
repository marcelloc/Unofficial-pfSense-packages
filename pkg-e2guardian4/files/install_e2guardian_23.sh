#!/bin/sh

# *
# * install_e2guardian_23.sh
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
prefix=https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/pkg-e2guardian/files

check_service_file=check_e2guardian_service.php
fetch -q -o /root/$check_service_file $prefix/$check_service_file

# /usr/local files

for file in 	pkg/e2guardian.xml pkg/e2guardian_antivirus_acl.xml pkg/e2guardian_blacklist.xml pkg/e2guardian_config.xml \
		pkg/e2guardian_content_acl.xml pkg/e2guardian_file_acl.xml pkg/e2guardian_groups.xml pkg/e2guardian_header_acl.xml \
		pkg/e2guardian_ldap.xml pkg/e2guardian_limits.xml pkg/e2guardian_log.xml pkg/e2guardian_phrase_acl.xml \
		pkg/e2guardian_search_acl.xml pkg/e2guardian_pics_acl.xml pkg/e2guardian_sync.xml pkg/e2guardian_site_acl.xml \
		pkg/e2guardian_url_acl.xml pkg/e2guardian.inc pkg/pkg_e2guardian.inc pkg/e2guardian.conf.template \
		pkg/e2guardian_ips_header.template pkg/e2guardian_rc.template pkg/e2guardian_users_footer.template \
		pkg/e2guardian_users_header.template pkg/e2guardianfx.conf.template www/e2guardian.php \
		www/e2guardian_about.php www/e2guardian_ldap.php www/shortcuts/pkg_e2guardian.inc pkg/tinyproxy.inc	
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

# Install e2guardian freebsd package and deps
pkg install e2guardian squid ca_root_nss

# remove non ssl e2guardian packages after deps install
pkg remove e2guardian

# install 3.5.1 package with ssl support
pkg add $prefix/e2guardian-3.5.1.txz

# restore repository configuration state
cp /root/pfSense.bkp.conf $repo2
cp /root/FreeBSD.bkp.conf $repo1

#install services and menus
php /root/$check_service_file

#patch pkg_edit.php to restore or include #mainarea div necessary for submenu
fetch -o - -q $prefix/pkg_edit.patch | patch -N -b -p0
fetch -o - -q $prefix/pkg.patch | patch -N -b -p0
fi
