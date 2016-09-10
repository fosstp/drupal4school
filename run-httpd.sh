#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid

source /etc/apache2/envvars
exec apache2 -DFOREGROUND
