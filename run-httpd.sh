#!/bin/sh
set -e
if [ ! -d "/var/www/html/vendor/google" ]; then
    composer require google/apiclient:"^2.0"
fi
if [ ! -d "/var/www/html/vendor/drush" ]; then
    composer require drush/drush
    ln -s /var/www/html/vendor/bin/drush /usr/local/bin/drush
    chmod 744 /usr/local/bin/drush
fi

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    #php /var/www/html/core/scripts/drupal install drupal4school
    chown -R www-data:www-data /var/www/html/sites
    chmod 775 /var/www/html/sites/default/files
    exec apache2-foreground
fi

drush cr

if [ $# -gt 0 ]; then
    exec drush $@
fi