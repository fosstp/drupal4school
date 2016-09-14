FROM drupal:7
MAINTAINER fosstp drupal team

RUN apt-get update \
    && apt-get -y --no-install-recommends install ksh unzip gcc make git freetds-dev php-pear libldap2-dev mariadb-client ksh \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap pcntl zip \
    && echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/20-memory.ini

#https://github.com/scrazy77/uploadprogress  upload progress for php7 scrazy
RUN cd ~ && git clone https://github.com/scrazy77/uploadprogress.git \
    && cd uploadprogress \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && cd .. \
    && rm -rf uploadprogress \
    && echo "extension=uploadprogress.so" > /usr/local/etc/php/conf.d/20-uploadprogress.ini


#https://www-304.ibm.com/support/docview.wss?rs=71&uid=swg27007053
ADD ibm /opt/ibm
RUN chmod +x /opt/ibm/dsdriver/installDSDriver \
    && ksh /opt/ibm/dsdriver/installDSDriver \
    && echo "/opt/ibm/dsdriver" | pecl install ibm_db2 \
    && { \
    echo 'extension=ibm_db2.so'; \
    echo 'ibm_db2.instance_name=db2inst1'; \
    } > /usr/local/etc/php/conf.d/30-ibm_db2.ini \
    && chmod a+w /usr/local/etc/php/ /usr/local/etc/php/conf.d \
    && chmod a+r -R /usr/local/lib/php/extensions \
    && echo 'TLS_REQCERT never' >> /etc/ldap/ldap.conf



#Now, install composer, drush then install google api client library.
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && ln -s /usr/local/bin/composer /usr/bin/composer \
    && git clone https://github.com/drush-ops/drush.git /usr/local/src/drush \
    && cd /usr/local/src/drush \
    && git checkout 7.x \
    && ln -s /usr/local/src/drush/drush /usr/bin/drush \
    && composer install \
    && cd /var/www/html \
    && composer require google/apiclient:1.*
    
ADD profiles /var/www/html/profiles
ADD modules /var/www/html/sites/all/modules
ADD themes /var/www/html/sites/all/themes
ADD translations /var/www/html/sites/all/translations
RUN mkdir -p /var/www/html/profiles/standard/translations/ \
    && cd /var/www/html/profiles/standard/translations/ \
    && curl -fSL "http://ftp.drupal.org/files/translations/7.x/drupal/drupal-7.x.zh-hant.po" -o drupal-7.x.zh-hant.po \
    && mkdir -p /var/www/html/profiles/drupal4school/translations/ \
    && cp /var/www/html/profiles/standard/translations/drupal-7.x.zh-hant.po /var/www/html/profiles/drupal4school/translations \
    && cd /var/www/html \
    && drush dl services,ctools,views,date,calendar,openid_provider,xrds_simple,libraries,l10n_update \
    && echo "\$conf['drupal_http_request_fails'] = FALSE;" >> /var/www/html/sites/default/default.settings.php \
    && mkdir /var/www/html/sites/default/files \
    && chown -R www-data:www-data /var/www/html \
    && chmod 777 /var/www/html/sites/all/translations \
    && chmod 744 /var/www/html/sites/default/files

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

VOLUME ["/var/www/html/profiles", "/var/www/html/sites/all/modules", "/var/www/html/sites/all/themes", "/var/www/html/sites/all/translations", "/var/www/html/sites/default/files"]
EXPOSE 80 443
CMD ["/usr/sbin/run-httpd.sh"]