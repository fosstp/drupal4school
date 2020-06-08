#!/bin/sh
set -e

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    chown -R root:www-data /var/www/private
    chmod -R 770 /var/www/private
    chown -R root:www-data /var/www/html
    chmod -R 750 /var/www/html
    chmod -R 774 /var/www/html/sites/default/files
    exec apache2-foreground
    #drupal install drupal4school
fi

if [ -f "/var/www/html/sites/default/settings.php" ]; then
    sed -ri \
        -e 's/^# \$settings\[\'file_private_path\'\] = .*/\$settings\[\'file_private_path\'\] = "/var/www/private"/g' \
        /var/www/html/sites/default/settings.php
fi

drupal cr all

if [ $# -gt 0 ]; then
    exec drush $@
fi