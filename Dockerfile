FROM drupal

ENV SITE_NAME "drupal 8"
ENV SITE_MAIL webmaster@xxps.tp.edu.tw
ENV SITE_ADMIN admin
ENV SITE_ADMIN_MAIL your_mail@xxps.tp.edu.tw
ENV SITE_PASSWORD your_password
ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword

RUN apt-get update \
    && apt-get -y --no-install-recommends install unzip git apt-utils mc ldap-utils mariadb-client \
    && rm -rf /var/lib/apt/lists/* \
    && echo 'y' | pecl install apcu \
    && docker-php-ext-enable apcu \
    && echo 'TLS_REQCERT	never' >> /etc/ldap/ldap.conf \
    && pecl install uploadprogress \
    && echo "extension = uploadprogress" > /usr/local/etc/php/conf.d/uploadprogress.ini \
    && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution_time.ini \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && cd /var/www/html \
    && composer require google/apiclient:"^2.0" drupal/console:~1.0 --prefer-dist --optimize-autoloader \
    && curl https://drupalconsole.com/installer -L -o drupal.phar \
    && mv drupal.phar /usr/local/bin/drupal \
    && chmod +x /usr/local/bin/drupal \
    && composer upgrade

ADD modules /var/www/html/modules
RUN mkdir /var/www/html/sites/default/files \
    && chown -R root:www-data /var/www/html \
    && chmod -R 750 /var/www/html \
    && chmod -R 777 /var/www/html/sites

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

VOLUME ["/var/www/html/sites"]
EXPOSE 80
ENTRYPOINT ["run-httpd.sh"]