#!/bin/bash
set -e
rm -f /var/run/apache2/apache2.pid
FIRST_STARTUP_DONE="/var/log/docker-drupal-first-startup-done"

if [ ! -e "$FIRST_STARTUP_DONE" ]; then
  cd /var/www/html
  TEST="ok"
  drush en locale translation views date calendar ctools services libraries i10n_update thumbnail_link simsauth sims_view sims_field gapps db2health adsync gevent
  [ ! -z "$(drush pm-list --status=enabled | grep locale)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep translation)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep views)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep date)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep calendar)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep ctools)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep services)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep libraries)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep i10n_update)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep thumbnail_link)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep simsauth)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep sims_view)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep sims_field)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep gapps)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep db2health)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep adsync)" ] || TEST="not ok"
  [ ! -z "$(drush pm-list --status=enabled | grep gevent)" ] || TEST="not ok"
                                          
  [ "${TEST}" == "ok" ] || touch ${FIRST_STARTUP_DONE}
fi
                                            
exec apache2 -DFOREGROUND
