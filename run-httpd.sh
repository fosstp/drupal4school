#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid

echo "export $(env | grep DATABASE_PASSWORD)" >> /etc/apache2/envvars
source /etc/apache2/envvars
exec apache2 -DFOREGROUND
