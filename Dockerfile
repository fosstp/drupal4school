FROM drupal:7
MAINTAINER fosstp drupal team

RUN apt-get update \
    && apt-get -y --no-install-recommends install ksh unzip gcc make git freetds-dev php-pear libldap2-dev mariadb-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install ldap pcntl zip \
    && sed -i 's/memory_limit = -1/memory_limit = 256M/g' /usr/local/etc/php/php.ini

#https://pecl.php.net/package/uploadprogress
RUN pecl install uploadprogress \
    && echo "extension=uploadprogress.so" > /usr/local/etc/php/conf.d/20-uploadprogress.ini

#https://www-304.ibm.com/support/docview.wss?rs=71&uid=swg27007053
RUN mkdir /opt/ibm \
    && cd /opt/ibm \
    && curl -fSL "https://www.dropbox.com/s/naq3p1hx852huxl/v10.5fp6_linuxx64_dsdriver.tar.gz?dl=0" -o dsdriver.tar.gz \
    && tar -xzf dsdriver.tar.gz \
    && rm -rf dsdriver.tar.gz \
    && chmod +x /opt/ibm/dsdriver/installDSDriver \
    && /opt/ibm/dsdriver/installDSDriver \
    && echo "/opt/ibm/dsdriver" | pecl install ibm_db2 \
    && { \
	echo 'extension=ibm_db2.so'; \
	echo 'ibm_db2.instance_name=db2inst1'; \
    } > /usr/local/etc/php/conf.d/30-ibm_db2.ini \
    && chmod a+w /usr/local/etc/php/ /usr/local/etc/php/conf.d \
    && chmod a+r -R /usr/local/lib/php/extensions \
    && echo 'TLS_REQCERT	never' >> /etc/ldap/ldap.conf

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
    
RUN mkdir -p /var/www/html/profiles/standard/translations/ \
    && cd /var/www/html/profiles/standard/translations/ \
    && curl -fSL "http://ftp.drupal.org/files/translations/7.x/drupal/drupal-7.x.zh-hant.po" -o drupal-7.x.zh-hant.po \
    && drush language-add zh-hant \
    && drush language-enable zh-hant \
    && drupal drush language-default zh-hant

RUN cd /var/www/html \
    && drush dl services,ctools,views,date,calendar,openid_provider,xrds_simple,libraries,l10n_update

RUN cp /var/www/html/sites/default/default.settings.php /var/www/html/sites/default/settings.php \
    && echo "\$conf['drupal_http_request_fails'] = FALSE;" >> /var/www/html/sites/default/settings.php

ADD modules /var/www/html/sites/all/modules
ADD themes /var/www/html/sites/all/themes
ADD translations /var/www/html/sites/all/translations
RUN chown -R www-data:www-data /var/www/html \
    && chmod 777 /var/www/html/sites/all/translations

ADD run-httpd.sh /usr/sbin/run-httpd.sh
ADD first.sh /usr/sbin/first.sh
RUN chmod +x /usr/sbin/run-httpd.sh /usr/sbin/first.sh

EXPOSE 80 443 9005
CMD ["/usr/sbin/run-httpd.sh"]
