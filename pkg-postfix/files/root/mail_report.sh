#!/bin/sh

#
# mail_report.sh
#
# part of Unofficial packages for pfSense(R) softwate
# Copyright (c) 2017 Marcello Coutinho
# All rights reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

log=/tmp/today.log
log_partial=/tmp/today.partial.log
grep -vE "(warning|hold): header " $1 > $log

echo "Message with hits on deep tests:"
grep -iE "reject.*Service" $log |
 while read a b c d e f g h i j k l m
        do
        from=`echo $m| sed "s/.*from=./from=/i;s/, to=.*//;s/>//"`
        to=`echo $m| sed "s/.*, to=./to=/i;s/, proto.*//;s/>//"`
        error=`echo $m | sed "s/.*NOQUEUE:/NOQUEUE:/i;s/; from=.*//"`
        echo $a $b $c $from $to $error
        done > $log_partial

grep "Service currently unavailable" $log_partial
echo ""
echo "Permanent Messages reject log:"
grep -v "Service currently unavailable" $log_partial | cut -d ' ' -f 4- |sort | uniq -c
echo ""
/usr/local/bin/pflogsumm $1
rm -f $log
rm -f $log_partial
