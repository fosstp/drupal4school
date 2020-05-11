#!/bin/sh
set -e

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    #php /var/www/html/core/scripts/drupal install drupal4school
    chown -R root:www-data /var/www/html
    chmod -R 750 /var/www/html
    chmod -R 770 /var/www/html/sites/default/files
    exec apache2-foreground
fi

drush cr

if [ $# -gt 0 ]; then
    exec drush $@
fi