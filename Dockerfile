FROM centos:6
MAINTAINER leejoneshane

RUN yum -y install epel*
RUN rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-6.rpm
RUN yum -y --enablerepo=remi,remi-php56 install which tar zip unzip ksh gcc make php php-devel php-pecl-apcu php-cli php-pear php-pdo php-pecl-zip php-mysqlnd php-pgsql php-pecl-mongo php-sqlite php-pecl-memcache php-pecl-memcached php-pecl-uploadprogress php-gd php-mbstring php-mcrypt php-xml httpd
RUN yum -y --enablerepo=remi,remi-php56 update
RUN yum clean all

#https://www-304.ibm.com/support/docview.wss?rs=71&uid=swg27007053
RUN mkdir /opt/ibm \
    && cd /opt/ibm \
    && curl -fSL "https://www.dropbox.com/s/naq3p1hx852huxl/v10.5fp6_linuxx64_dsdriver.tar.gz?dl=0" -o dsdriver.tar.gz \
    && tar -xzf dsdriver.tar.gz \
    && rm -rf dsdriver.tar.gz \
    && chmod +x /opt/ibm/dsdriver/installDSDriver \
    && /opt/ibm/dsdriver/installDSDriver \
    && echo "/opt/ibm/dsdriver" | pecl install ibm_db2
RUN { \
	echo 'extension=ibm_db2.so'; \
	echo 'ibm_db2.instance_name=db2inst1'; \
    } > /etc/php.d/ibm_db2.ini
RUN echo 'TLS_REQCERT	never' >> /etc/openldap/ldap.conf
RUN { \
	echo 'opcache.memory_consumption=128'; \
	echo 'opcache.interned_strings_buffer=8'; \
	echo 'opcache.max_accelerated_files=4000'; \
	echo 'opcache.revalidate_freq=60'; \
	echo 'opcache.fast_shutdown=1'; \
	echo 'opcache.enable_cli=1'; \
    } > /etc/php.d/10-opcache.ini

RUN curl -fSL "https://github.com/drush-ops/drush/releases/download/8.0.0-rc3/drush.phar" -o drush.phar \
    && php drush.phar core-status \
    && chmod +x drush.phar \
    && mv drush.phar /usr/local/bin/drush \
    && drush init

RUN rm -rf /var/www/html \
    && cd /var/www \
    && drush dl drupal-8 \
    && mv /var/www/drupal*/ /var/www/html \
    && chmod a+w /var/www/html/sites/default \
    && mkdir /var/www/html/sites/default/files \
    && chown -R apache:apache /var/www/html \
    && chmod 775 /var/www/html/sites/default/files

ADD run-httpd.sh /usr/sbin/run-httpd.sh
RUN chmod +x /usr/sbin/run-httpd.sh

EXPOSE 80 443 9005
CMD ["/usr/sbin/run-httpd.sh"]
