#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid

if [-f /usr/sbin/first.sh]; then
  /usr/sbin/first.sh
fi

exec apache2 -DFOREGROUND
