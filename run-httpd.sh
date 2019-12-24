#!/bin/sh
set -e

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    #php /var/www/html/core/scripts/drupal install drupal4school
    chown -R www-data:www-data /var/www/html/sites
    chmod 744 /var/www/html/sites/default/files
    exec apache2-foreground
fi

drush cr

if [ $# -gt 0 ]; then
    exec drush $@
fi