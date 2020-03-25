FROM drupal

ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword

RUN apt-get update \
    && apt-get -y --no-install-recommends install unzip git apt-utils mc \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mbstring \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
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