#!/bin/sh
set -e

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    chown -R root:www-data /var/www/html
    chmod -R 750 /var/www/html
    chmod 2775 /var/www/html/sites/default/files
    for d in /var/www/html/sites/default/files
    do
      find $d -type d -exec chmod 2775 '{}' \;
      find $d -type f -exec chmod 664 '{}' \;
    done
    chmod 644 /var/www/html/sites/default/settings.php
    exec apache2-foreground
    exec drupal si drupal4school
fi

cd /var/www/html
exec drupal cr all

if [ $# -gt 0 ]; then
    exec drupal $@
fi
