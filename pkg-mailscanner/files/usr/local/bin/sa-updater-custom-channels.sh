#!/usr/local/bin/bash
rules_dir=/usr/local/etc/mail/spamassassin
# ---------------------------------------------------------------------------------
# Spamassassin auto updater with custom channels 
# ---------------------------------------------------------------------------------
/usr/local/bin/sa-update -v --no-gpg --channel sought.rules.yerp.org
/usr/local/bin/sa-update -v --no-gpg --channel spamassassin.heinlein-support.de  
/usr/local/bin/sa-update -v --no-gpg --channel sa.zmi.at
/usr/local/bin/sa-update -v --no-gpg

# ---------------------------------------------------------------------------------
# KAM rules channels 
# ---------------------------------------------------------------------------------

/usr/bin/fetch -o $rules_dir/KAM.cf  http://www.pccc.com/downloads/SpamAssassin/contrib/KAM.cf

# ---------------------------------------------------------------------------------
# Spamassassin Bayes Ignore Header
# ---------------------------------------------------------------------------------

/usr/bin/fetch -o $rules_dir/23_bayes_ignore_header.cf http://svn.apache.org/repos/asf/spamassassin/trunk/rulesrc/sandbox/axb/23_bayes_ignore_header.cf

# ---------------------------------------------------------------------------------
# MailScanner upgate phishing.safe.sites.conf
# ---------------------------------------------------------------------------------

/usr/local/bin/bash /usr/local/libexec/MailScanner/update_phishing_sites

# ---------------------------------------------------------------------------------
# MailScanner upgate phishing.bad.sites.conf
# ---------------------------------------------------------------------------------

/usr/local/bin/bash /usr/local/libexec/MailScanner/update_bad_phishing_sites

# ---------------------------------------------------------------------------------
# Restart MailScanner 
# ---------------------------------------------------------------------------------

export mailscanner_enable="YES" 
/usr/local/etc/rc.d/mailscanner restart

echo ""
echo "MailScanner successfully notified about the update."
echo ""
