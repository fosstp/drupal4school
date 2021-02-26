#!/bin/sh
set -e

if mysqlshow --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD} drupal; then
    echo "database exist!"
else
    echo "CREATE DATABASE drupal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql --host=${DB_HOST} --user=${DB_USER} --password=${DB_PASSWORD}
fi

if [ ! -f "/opt/drupal/web/sites/default/settings.php" ]; then
    cp -rp /root/sites/* /opt/drupal/web/sites
#    drupal si standard mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/drupal -n --langcode="zh-hant" --site-name="${SITE_NAME}" --site-mail="${SITE_MAIL}" --account-name="${SITE_ADMIN}" --account-mail="${SITE_ADMIN_MAIL}" --account-pass="${SITE_PASSWORD}" --force --no-ansi --no-interaction
#    drupal moi tpedu
    drush si standard --db-url=mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/drupal --locale="zh-hant" --site-name="${SITE_NAME}" --site-mail="${SITE_MAIL}" --account-name="${SITE_ADMIN}" --account-mail="${SITE_ADMIN_MAIL}" --account-pass="${SITE_PASSWORD}"
    drush en tpedu
fi

if [ ! -d "/opt/drupal/web/sites/default/files/adsync" ]; then
    mkdir -p /opt/drupal/web/sites/default/files/adsync
fi

if [ ! -d "/opt/drupal/web/sites/default/files/gsync" ]; then
    mkdir -p /opt/drupal/web/sites/default/files/gsync
fi

cd /opt/drupal/web/sites/default/files/config_*
if [ ! -d "sync" ]; then
    mkdir sync
fi
cd /opt/drupal

cp -rp /root/modules/* /opt/drupal/web/modules
cp -rp /root/themes/* /opt/drupal/web/themes

chown -R www-data:www-data /opt/drupal/web
chmod -R 750 /opt/drupal/web
chmod 2775 /opt/drupal/web/sites
chmod 2755 /opt/drupal/web/sites/default
for d in /opt/drupal/web/sites/default/files
do
    find $d -type d -exec chmod 2775 '{}' \;
    find $d -type f -exec chmod 664 '{}' \;
done
chmod 644 /opt/drupal/web/sites/default/settings.php
#drupal cc
drush cr

if [ ! -f "/var/run/apache2/apache2.pid" ]; then
    exec apache2-foreground
fi
