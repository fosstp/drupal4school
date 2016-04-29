drush language-add zh-hant
drush language-enable zh-hant
drush language-default zh-hant
drush -y en locale translation views date calendar ctools views services libraries l10n_update
drush -y en simsauth sims_views sims_field gapps db2health adsync gevent thumbnail_link
rm -rf /usr/sbin/first.sh
