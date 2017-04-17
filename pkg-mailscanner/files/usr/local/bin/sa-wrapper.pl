#!/usr/local/bin/perl -w
# Time-stamp: <05 April 2004, 13:37 home>
#
# sa-wrapper.pl
#
# SpamAssassin sa-learn wrapper
# (c) Alexandre Jousset, 2004
# This script is GPL'd
#
# Thanks to: Chung-Kie Tung for the removal of the dir
#            Adam Gent for bug report
#
# v1.2

use strict;
use MIME::Tools;
use MIME::Parser;

my $DEBUG = 0;
my $UNPACK_DIR = '/tmp';
my $SA_LEARN = '/usr/local/bin/sa-learn';

#my @DOMAINS = qw/gtmp.org winnink.org/;

my ($spamham, $sender) = @ARGV;

sub recurs
{
   my $ent = shift;
   if ($ent->head->mime_type eq 'message/rfc822') {
       if ($DEBUG) {
       unlink "/tmp/spam.log.$$" if -e "/tmp/spam.log.$$";
       open(OUT, "|$SA_LEARN -D --$spamham --single >>/tmp/spam.log.$$ 2>&1") or die "Cannot pipe $SA_LEARN: $!";
       } else {
       open(OUT, "|$SA_LEARN --$spamham --single") or die "Cannot pipe $SA_LEARN: $!";
       }
       $ent->bodyhandle->print(\*OUT);
       close(OUT);
       return;
   }
   my @parts = $ent->parts;
   if (@parts) {
       map { recurs($_) } @parts;
   }
}

#my ($domain) = $sender =~ /\@(.*)$/;
#unless (grep { $_ eq $domain } @DOMAINS) {
#    die "I don't recognize your domain !";
#}
if ($DEBUG) {
   MIME::Tools->debugging(1);
   open(STDERR, ">/tmp/spam_err.log");
}
my $parser = new MIME::Parser;
$parser->extract_nested_messages(0);
$parser->output_under($UNPACK_DIR);
my $entity;
eval {
   $entity = $parser->parse(\*STDIN);
};

if ($@) {
   die $@;
} else {
   recurs($entity);
}

$parser->filer->purge;
rmdir $parser->output_dir;