#!/bin/sh
set -e

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    chown -R root:www-data /var/www/html
    chmod -R 750 /var/www/html
    chmod -R 774 /var/www/html/sites/default/files
    exec apache2-foreground
    #drupal install drupal4school
fi

cd /var/www/html
drupal cr all

if [ $# -gt 0 ]; then
    exec drush $@
fi