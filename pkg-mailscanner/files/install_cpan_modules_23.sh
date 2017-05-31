#!/bin/sh

# *
# * install_cpan_23.sh
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

if [ ! -f /usr/bin/cc ] ;then
 fetch $prefix/mk.tar.gz &&
 tar -xvzf mk.tar.gz -C /
fi

# Enable freebsd Repo
repo_dir=/root/repo.bkp
mkdir -p $repo_dir
rm -f $repo_dir/*conf
cp /usr/local/etc/pkg/repos/*conf $repo_dir
sed -i "" -E "s/(FreeBSD.*enabled:) no/\1 yes/" /usr/local/etc/pkg/repos/*conf

pkg install gcc

# restore repository configuration state
cp $repo_dir/*conf /usr/local/etc/pkg/repos/.

rehash

export PERL_MM_USE_DEFAULT=1

cpan -f -i Razor2::Client::Agent
cpan -f -i Geo::IP
cpan -f -i Net::Patricia
cpan -f -i LWP::UserAgent
cpan -f -i Net::Server::Daemonize
cpan -f -i Net::Server::Multiplex
cpan -f -i IO::Multiplex
cpan -f -i Net::Server::PreFork
cpan -f -i Net::DNS
cpan -f -i Time::HiRes
cpan -f -i Storable

fi
