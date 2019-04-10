<?php
/*
 * postfix_report.php
 *
 * part of Unofficial packages for pfSense(R) softwate
 * Copyright (c) 2017 Marcello Coutinho
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require("/etc/inc/phpmailer/class.phpmailer.php");
require("/etc/inc/phpmailer/class.smtp.php");

global $config;

// Select report day(s)
if (preg_match("/^\d+$/",$argv[1])) {
        $days="-{$argv[1]}";
} else {
        $days=-1;
}

//current time
$curr_time= time();

// day of week
$dw = date( "w", strtotime("$days days",$curr_time)); //report week day

// check postfix xml configuration
$postfix_config=$config['installedpackages']['postfix'];
if (is_array($postfix_config)) {
        $report=$config['installedpackages']['postfix']['config'][0];
} else {
        die("Postfix package GUI not configured.\n");
}

// check report options
if ($report[report_status] != "on") {
        die("Postfix e-mail reports not enabled.\n");
}

if (preg_match("/\w+/",$report['report_frequency'])) {
        $report_log_file = create_report_file($curr_time, $report['report_frequency'], $days, $dw);
} else {
        die("No frequency selected for postfix e-mail reports.\n");
}

if ( preg_match("/\w+/",$report['report_destination'])) {
        $destinations=base64_decode($report['report_destination']);
        $destinations=preg_replace("/\s+/","|",$destinations);
        $destinations=explode("|",$destinations);
} else {
        die("No e-mail addreses to send reports.\n");
}

if (! preg_match("/\S+@\S+/",$report[report_from])) {
        die("Postfix from address is invalid.\n");
}

function grep_mail_log($curr_time, $days, $maillog, $file) {
    $m=date('M',strtotime("$days days",$curr_time));
    $j=substr(" (0| )".date('j',strtotime("$days days",$curr_time)),-7);
    if ( ! file_exists($maillog)) {
        die("$maillog does not exists");
    }
    $arg = "\"{$m}{$j}\"";
    print "/usr/bin/grep -E $arg $maillog >> $file\n";
    system("/usr/bin/grep -E $arg $maillog >> $file");
}

function create_report_file($curr_time,$frequency, $days, $dw) {
  $maillog="/var/log/maillog";
  $postfix_db_date = date("Y-m-d",strtotime("$days days",$curr_time));
  $new_log_file="/tmp/{$postfix_db_date}.log";
  // html body report file
  if (file_exists($new_log_file)) {
        unlink($new_log_file);
  }
  if (preg_match("/weekly/",$frequency) && $dw == 6 ) {
        print "Weekly report($dw)...\n";
        $start_day = ($days -6);
        for ($z = $start_day; $z <= $days; $z++) {
           grep_mail_log($curr_time, $z, $maillog, $new_log_file);
        }
  }else{
        print "Daily report($dw)...\n";
        grep_mail_log($curr_time, $days , $maillog, $new_log_file);
  }
  return($new_log_file);
}

        /*
        // find new place for
        // rotate logs
        rename("$maillog.new","$maillog.$postfix_db_date");
        system("/bin/tail -2000 $maillog.$postfix_db_date > $maillog.new");
        system("/usr/bin/killall -HUP syslogd");
        $m="";
        $j="";
        $log_file="$maillog.$postfix_db_date";
        */

function print_table_line($m,$t='td'){
        array_shift($m);
        $return = "<tr>\n<$t>";
        foreach ($m as $f) {
                $return .= "{$f}</{$t}>\n<{$t}>";
        }
        $return = preg_replace("/<$t>$/","</tr>\n",$return);
        return ($return);
}
$attach= <<<EOF
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />

<style>
table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    border: 1px solid #ddd;
    font-family:"Lucida Console";
    font-size: 0.875em;
}

th, td {
    border: none;
    text-align: left;
    padding: 8px;
    font-family:"Lucida Console";
    font-size: 0.875em;
}
tr:nth-child(even){background-color: #f2f2f2}
</style>
</head>
<body>
<div style="overflow-x:auto;font-family:'Lucida Console'">
EOF;

$tds=1;
$td="</td>\n<td>";

exec("/bin/sh /root/mail_report.sh {$report_log_file}",$mreport);

foreach ($mreport as $line) {
        if (preg_match("/message (deferral|bounce|reject|reject warning) detail/",$line)) {
                $tds=0;
        }
        if (preg_match("/^(message |Permanent|Grand|Per-Hour|Host.Domain|Senders|Recipients|smtp delivery|Warnings|Fatal |Panics|Master )/i",$line)) {
                $attach .= "</table><br><b>$line</b></p>\r\n";
                $attach .= "<table style='width:90%;'>\n";
                if (preg_match("/Message with hits on deep tests/",$line)) {
                        $attach .= print_table_line(array('','Date','From','To'),'th');
                }
                if (preg_match("/Permanent Messages reject log/",$line)) {
                        $attach .= print_table_line(array('','count','From','To','Reject info'),'th');
                }
                if (preg_match("/Grand Totals/",$line)) {
                        $attach .= print_table_line(array('','count','Status'),'th');
                }
                if (preg_match("/Recipients by message (count|size)/",$line)) {
                        $attach .= print_table_line(array('','count','e-mail'),'th');
                }

        }
         else if (preg_match("/(messages$|Postfix log summaries|----|^\s+$|^$)/",$line)){
                $attach .= "";
        }
        else if ($tds == 0){
                $attach .= "<tr><td>$line</td></tr>\n";
        }
        else if(preg_match("/(\w+\s+\d+\s+[0-9,:]+) from=(\S+) to=(\S+) (.*)Service currently unavailable/",$line,$m)){
                $m[2]=preg_replace("/(@|\.)/","$1_",$m[2]);
                $m[3]=preg_replace("/(@|\.)/","$1_",$m[3]);
                $attach .= print_table_line($m);
        }
        else if(preg_match("/(\d+) from=(\S+ | )to=(\S+) Service unavailable..(.*)/",$line,$m)){
                // mask rbl ips from report
                $m[3]=preg_replace("/(@|\.)/","$1_",$m[3]);
                $m[4]=preg_replace("/(@|\.)/","$1_",$m[4]);
                $m[5]=preg_replace("/(@|\.)/","$1_",$m[5]);
                $attach .= print_table_line($m);
        } 
        else if(preg_match("/(\d+\W\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/",$line,$m)){
                $attach .= print_table_line($m);
        }
        else if(preg_match("/(sent cnt)\s+(bytes)\s+(defers)\s+(avg dly)\s+(max dly)\s+(host.domain)/",$line,$m)){
                $attach .= print_table_line($m,'th');
        }
        else if(preg_match("/(time)\s+(received)\s+(delivered)\s+(deferred)\s+(bounced)\s+(rejected)/",$line,$m)){
                $attach .= print_table_line($m,'th');
        }
        else if(preg_match("/(msg cnt)\s+(bytes)\s+(host.domain)/",$line,$m)){
                $attach .= print_table_line($m,'th');
        }
        else if(preg_match("/(\d+)\s+(\S+)\s+(\d+)\s+([0-9,.]+\s\w)\s+([0-9,.]+\s\w)\s+(\S+)/",$line,$m)){
                $attach .= print_table_line($m);
        }
        else if (preg_match("/(\w+)\s+(bytes.*|rec.*|del.*|for.*|def.*|bou.*|rej.*|hel.*|di.*|sen.*)/",$line,$m)){
                $attach .= print_table_line($m);
        }
        else if(preg_match("/(\d+)\s+(\w+)\s+(\S+)$/",$line,$m)){
                $attach .= print_table_line($m);
        }
        else if (preg_match("/(\w+)\s+(\S+)$/",$line,$m)){
                $attach .= print_table_line($m);
        }
        else {
                $attach .= "<tr><td>$line</td></tr>\n";
        }
}
//get database name
if ($days == 0) {
        $postfix_db_date = date("Y-m-d");
        $report_date = date("F j");
} else {
        $postfix_db_date = date("Y-m-d",strtotime("$days day",$curr_time));
        $report_date =date("F j",strtotime("$days day",$curr_time));
}
$postfix_db_file = "/var/db/postfix/{$postfix_db_date}.db";

if (!file_exists($postfix_db_file)) {
        die("Error. {$postfix_db_file} does not exists.\n");
}

$attach .="</body></html>";
//file_put_contents("/usr/local/www/report.php",$attach,LOCK_EX);
if ($argv[2] == 'local') {
 echo "Local report created\n";
 exit;
}
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Host     = "127.0.0.1";
$mail->Port     = "25";
$mail->From  = $report['report_from'];
$mail->FromName  ="Firewall e-mail Report";
$mail->AddReplyTo= $report['report_from'];
if (preg_match("/weekly/",$report['report_frequency']) && $dw == 6) {
        $report_date2 =date("F j",strtotime(($days -6)." day",$curr_time));
        $mail->Subject="{$report_date2} - {$report_date} weekly e-mail report";
} else {
        $mail->Subject="{$report_date} daily e-mail report";
}
foreach ($destinations as $email) {
        if (preg_match("/\S+@\S+/",$email)) {
                $mail->AddAddress($email);
        }
}

if ($report['report_attachment'] == 'on') {
  // do report domain and ip mask to avoid spam being marked as spam
  $fields1 = "date,'' as sid,replace(fromm, '.', '._'),replace(too,'.','._'),'' as size,'' as subject,helo,status,replace(status_info, '.','._'),'' as relay,'' as dsn,'' as server,'NOQUEUE' as log";
  $sql1 = "select {$fields1} from mail_noqueue;";

  $fields2 = "date,sid,replace(fromm,'.','._'),replace(too,'.','._'),size,subject,helo,replace(mail_status.info,'.','._') as status,status_info,relay,dsn,mail_from.server,'QUEUE' as log";
  $sql2 = "select {$fields2} from mail_from, mail_to ,mail_status where mail_from.id=mail_to.from_id and mail_to.status=mail_status.id;";
 
  if (preg_match("/weekly/",$report['report_frequency']) && $dw == 6 ) {
        $start_day = ($days -6);
        for ($z = $start_day; $z <= $days; $z++) {
           $postfix_db_date2 = date("Y-m-d",strtotime("$z days",$curr_time));
           $week_postfix_db_file = "/var/db/postfix/{$postfix_db_date2}.db";
           echo "weekly sql to csv extract on {$week_postfix_db_file}\n";
           system("echo \"{$sql1}\" | /usr/local/bin/sqlite3 {$week_postfix_db_file} -csv >> /tmp/raw.tmp");
           system("echo \"{$sql2}\" | /usr/local/bin/sqlite3 {$week_postfix_db_file} -csv >> /tmp/raw.tmp");
        }
  } else {
       system("echo \"{$sql1}\" | /usr/local/bin/sqlite3 {$postfix_db_file} -csv >> /tmp/raw.tmp");
       system("echo \"{$sql2}\" | /usr/local/bin/sqlite3 {$postfix_db_file} -csv >> /tmp/raw.tmp");
  }
 
  $csv_file="{$postfix_db_date}_raw_log.csv";
  $zip_file="{$postfix_db_date}_raw_log.zip";
  file_put_contents("/root/{$csv_file}","sep=,\ndate,sid,from,to,size,subject,helo,status,status_info,relay,dsn,server,log\n",LOCK_EX);
  system("/usr/bin/sort /tmp/raw.tmp >> /root/{$csv_file}");
  // do report email mask to avoid spam report being marked as spam
  system("/usr/bin/sed -i '.bak' 's/@/@_/g' /root/{$csv_file}");
  system("cd /root;/usr/local/bin/zip -D {$postfix_db_date}_raw_log.zip {$csv_file}");
  unlink('/tmp/raw.tmp');

  //$mail->AddAttachment($csv_file,"raw_log.csv",'base64','text/csv');
  $mail->AddAttachment("/root/{$zip_file}",$zip_file,'base64','application/zip, application/octet-stream');
}
$mail->IsHTML(true);
$mail->MsgHTML($attach);

if($mail->send()) {
        echo "Message has been sent.\n";
} else {
        echo "Message was not sent.\n";
        echo 'Mailer error: ' . $mail->ErrorInfo;
}
if ($report['report_attachment'] == 'on') {
        if (file_exists("/root/{$csv_file}")) {
                unlink("/root/{$csv_file}");
        }
        if (file_exists("/root/{$zip_file}")) {
                unlink("/root/{$zip_file}");
        }
}

?>
