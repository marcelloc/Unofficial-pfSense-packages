#!/usr/local/bin/bash

/usr/local/bin/sa-learn --spam "$@" >> /var/log/postfix/sa-learn.log

exit 0
