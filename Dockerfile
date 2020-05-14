FROM drupal

ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword

RUN apt-get update \
    && apt-get -y --no-install-recommends install unzip git apt-utils mc ldap-utils \
    && rm -rf /var/lib/apt/lists/* \
    && echo 'TLS_REQCERT	never' >> /etc/ldap/ldap.conf \
    && pecl install uploadprogress \
    && echo "extension = uploadprogress" > /usr/local/etc/php/conf.d/uploadprogress.ini \
    && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution_time.ini \
    && docker-php-ext-install mbstring \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer require google/apiclient:"^2.0" \
    && curl https://drupalconsole.com/installer -L -o drupal.phar \
    && mv drupal.phar /usr/local/bin/drupal \
    && chmod +x /usr/local/bin/drupal

ADD profiles /var/www/html/profiles
ADD modules /var/www/html/modules
ADD themes /var/www/html/themes
RUN mkdir /var/www/html/sites/default/files \
    && chown -R root:www-data /var/www/html \
    && chmod -R 750 /var/www/html \
    && chmod 770 /var/www/html/sites/default/files

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

VOLUME ["/var/www/html/sites"]
EXPOSE 80
ENTRYPOINT ["run-httpd.sh"]