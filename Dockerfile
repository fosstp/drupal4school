FROM drupal

ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword

RUN apt-get update \
    && apt-get -y --no-install-recommends install unzip git apt-utils \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && cd /var/www/html \
    && composer require google/apiclient:"^2.0" \
    && composer require drush/drush \
    && curl -sSOL https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar \
    && mv drush.phar /usr/local/bin/drush \
    && chmod 744 /usr/local/bin/drush \
    && echo 'TLS_REQCERT	never' >> /etc/ldap/ldap.conf
    
#RUN cd /var/www/html \
#    && drush dl services,ctools,views,date,calendar,openid_provider,xrds_simple,libraries,l10n_update

ADD profiles /var/www/html/profiles
ADD modules /var/www/html/modules
ADD themes /var/www/html/themes
RUN mkdir /var/www/html/sites/default/files \
    && chown -R www-data:www-data /var/www/html \
    && chmod 775 /var/www/html/sites/default/files

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

VOLUME ["/var/www/html/sites"]
EXPOSE 80
ENTRYPOINT ["run-httpd.sh"]