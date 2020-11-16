FROM drupal

ENV TZ Asia/Taipei
ENV SITE_NAME "drupal 9"
ENV SITE_MAIL webmaster@xxps.tp.edu.tw
ENV SITE_ADMIN admin
ENV SITE_ADMIN_MAIL your_mail@xxps.tp.edu.tw
ENV SITE_PASSWORD your_password
ENV DB_HOST mysql
ENV DB_USER root
ENV DB_PASSWORD dbpassword
ENV EDITOR /usr/bin/mcedit

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && apt-get update \
    && apt-get -y --no-install-recommends install unzip git apt-utils mc libldap2-dev mariadb-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install ldap \
    && docker-php-ext-enable ldap \
    && echo 'y' | pecl install apcu \
    && docker-php-ext-enable apcu \
    && echo "TLS_REQCERT never\nTLS_CACERTDIR /var/www/html/sites/default/files/adsync\n" >> /etc/ldap/ldap.conf \
    && pecl install uploadprogress \
    && echo "date.timezone = Asia/Taipei" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "post_max_size = 100M\nupload_max_filesize = 100M\nextension = uploadprogress" > /usr/local/etc/php/conf.d/upload.ini \
    && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 3000" > /usr/local/etc/php/conf.d/execution_time.ini \
    && cd /opt/drupal \
    && cp -rp /opt/drupal/web/sites /root/sites \
#    && composer require drupal/date_recur:^3.0 drupal/date_recur_modular:^3.0 cache/filesystem-adapter google/apiclient:^2.0 drupal/console:~1.0 --prefer-dist --optimize-autoloader \
#    && curl https://drupalconsole.com/installer -L -o drupal.phar \
#    && mv drupal.phar /usr/local/bin/drupal \
#    && chmod +x /usr/local/bin/drupal
    && composer require drupal/date_recur:^3.0 drupal/date_recur_modular:^3.0 cache/filesystem-adapter google/apiclient:^2.0 drush/drush:^10 --prefer-dist --optimize-autoloader \
    && curl https://github.com/drush-ops/drush-launcher/releases/latest/download/drush.phar -L -o drush.phar \
    && mv drush.phar /usr/local/bin/drush \
    && chmod +x /usr/local/bin/drush

ADD modules /root/modules
ADD themes /root/themes
RUN mkdir -p /opt/drupal/web/sites/default/files \
    && chown -R www-data:www-data /opt/drupal/web \
    && chmod -R 750 /opt/drupal/web \
    && chmod -R 777 /opt/drupal/web/sites

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

EXPOSE 80
CMD ["run-httpd.sh"]