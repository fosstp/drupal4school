#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid
FIRST_STARTUP_DONE="/var/log/docker-drupal-first-startup-done"

if [ ! -e "$FIRST_STARTUP_DONE" ]; then
  echo "export $(env | grep DATABASE_PASSWORD)" >> /etc/apache2/envvars
  touch ${FIRST_STARTUP_DONE}
fi

source /etc/apache2/envvars
exec apache2 -DFOREGROUND
