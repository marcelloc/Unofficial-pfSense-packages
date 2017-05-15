#!/bin/sh

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
