# drupal for school
[中文說明](https://github.com/fosstp/drupal4school/blob/master/README.zh-tw.md)

[![Build Status](https://travis-ci.org/fosstp/drupal4school.svg?branch=master)](https://travis-ci.org/fosstp/drupal4school)
[![](https://images.microbadger.com/badges/version/fosstp/drupal.svg)](http://microbadger.com/images/fosstp/drupal "Get your own version badge on microbadger.com")
[![](https://images.microbadger.com/badges/image/fosstp/drupal.svg)](http://microbadger.com/images/fosstp/drupal "Get your own image badge on microbadger.com")

This docker image contains debian 8.2(jessie) + php 5.6 + apache 2.2 + drupal 7 + custom drupal modules & themes

How to use this image
The basic pattern for starting a drupal instance is:

$ docker run --name some-drupal -d fosstp/drupal
If you'd like to be able to access the instance from the host without the container's IP, standard port mappings can be used:

$ docker run --name some-drupal -p 8080:80 -d fosstp/drupal
Then, access it via http://localhost:8080 or http://host-ip:8080 in a browser.

There are multiple database types supported by this image, most easily used via standard container linking. In the default configuration, SQLite can be used to avoid a second container and write to flat-files. More detailed instructions for different (more production-ready) database types follow.

When first accessing the webserver provided by this image, it will go through a brief setup process. The details provided below are specifically for the "Set up database" step of that configuration process.

## MySQL
$ docker run --name some-drupal --link some-mysql:mysql -d fosstp/drupal

Database type: MySQL, MariaDB, or equivalent
Database name/username/password: details for accessing your MySQL instance (MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE; see environment variables in the description for mysql)
ADVANCED OPTIONS; Database host: mysql (for using the /etc/hosts entry added by --link to access the linked container's MySQL instance)

## PostgreSQL
$ docker run --name some-drupal --link some-postgres:postgres -d fosstp/drupal

Database type: PostgreSQL
Database name/username/password: details for accessing your PostgreSQL instance (POSTGRES_USER, POSTGRES_PASSWORD; see environment variables in the description for postgres)
ADVANCED OPTIONS; Database host: postgres (for using the /etc/hosts entry added by --link to access the linked container's PostgreSQL instance)
