#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid
FIRST_STARTUP_DONE="/var/log/docker-drupal-first-startup-done"
cd /var/www/html

if [ ! -e "$FIRST_STARTUP_DONE" ] && [ ! -z "$(drush sql-connect | grep error)" ]; then
  TEST="ok"
  drush dl utf8mb4_convert-7.x
  drush utf8mb4-convert-databases
  drush en locale translation views date calendar ctools services libraries l10n_update thumbnail_link simsauth sims_views sims_field gapps db2health adsync gevent
  [ ! -z "$(drush pm-list --status=enabled | grep locale)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep translation)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep views)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep date)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep calendar)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep ctools)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep services)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep libraries)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep l10n_update)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep thumbnail_link)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep simsauth)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep sims_views)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep sims_field)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep gapps)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep db2health)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep adsync)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep gevent)" ] || TEST="not ok"
  
  [ "${TEST}" == "ok" ] || touch ${FIRST_STARTUP_DONE}
fi

source /etc/apache2/envvars
exec apache2 -DFOREGROUND
