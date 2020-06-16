#!/bin/sh
set -e

if mysqlshow --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} drupal; then
    echo "database exist!"
else
    chmod -R 777 /var/www/html/sites
    echo "CREATE DATABASE drupal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD}
    exec drupal si drupal4school mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/drupal -n --langcode="zh-hant" --site-name="${SITE_NAME}" --site-mail="${SITE_MAIL}" --account-name="${SITE_ADMIN}" --account-mail="${SITE_ADMIN_MAIL}" --account-pass="${SITE_PASSWORD}" --force --no-ansi --no-interaction
fi

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    chown -R root:www-data /var/www/html
    chmod -R 750 /var/www/html
    chmod 2775 /var/www/html/sites
    for d in /var/www/html/sites
    do
        find $d -type d -exec chmod 2775 '{}' \;
        find $d -type f -exec chmod 664 '{}' \;
    done
    chmod 644 /var/www/html/sites/default/settings.php
    exec apache2-foreground
fi

cd /var/www/html
exec drupal cr all

if [ $# -gt 0 ]; then
    exec drupal $@
fi
