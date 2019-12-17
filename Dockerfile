FROM drupal:fpm-alpine

ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword

RUN apk add --no-cache unzip git openldap-clients \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && cd /var/www/html \
    && composer global require drush/drush:dev-master \
    && composer global require google/apiclient:"^2.0" \
    && echo 'TLS_REQCERT	never' >> /etc/ldap/ldap.conf
    
#RUN cd /var/www/html \
#    && drush dl services,ctools,views,date,calendar,openid_provider,xrds_simple,libraries,l10n_update

ADD profiles /var/www/html/profiles
ADD modules /var/www/html/modules
ADD themes /var/www/html/themes
RUN mkdir /var/www/html/sites/default/files \
    && chown -R www-data:www-data /var/www/html \
    && chmod 744 /var/www/html/sites/default/files

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

VOLUME ["/var/www/html/modules", "/var/www/html/themes", "/var/www/html/sites/default/files"]
EXPOSE 80
CMD ["run-httpd.sh"]