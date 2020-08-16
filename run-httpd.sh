#!/bin/sh
set -e

if mysqlshow --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} drupal; then
    echo "database exist!"
else
    echo "CREATE DATABASE drupal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD}
fi

if [ ! -f "/var/www/html/sites/default/settings.php" ]; then
    cd /var/www/html
    cp /opt/drupal/web/sites/default/default.settings.php sites/default/settings.php
    drupal si standard mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/drupal -n --langcode="zh-hant" --site-name="${SITE_NAME}" --site-mail="${SITE_MAIL}" --account-name="${SITE_ADMIN}" --account-mail="${SITE_ADMIN_MAIL}" --account-pass="${SITE_PASSWORD}" --force --no-ansi --no-interaction
    drupal moi tpedu
fi

if [ ! -d "/var/www/html/sites/default/files/adsync" ]; then
    mkdir -p /var/www/html/sites/default/files/adsync
fi

if [ ! -d "/var/www/html/sites/default/files/gsync" ]; then
    mkdir -p /var/www/html/sites/default/files/gsync
fi

cd /var/www/html
chown -R www-data:www-data ./
chmod -R 750 ./
chmod 2775 sites
chmod 2755 sites/default
for d in sites/default/files
do
    find $d -type d -exec chmod 2775 '{}' \;
    find $d -type f -exec chmod 664 '{}' \;
done
chmod 644 sites/default/settings.php
drupal cc

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    exec apache2-foreground
fi
