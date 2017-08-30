# drupal for school
[中文說明](https://github.com/fosstp/drupal4school/blob/develop/README.zh-tw.md)

This docker image contains debian 8.2(jessie) + php 7 + apache 2.2 + drupal 8 + custom drupal modules & themes

If You want d4s for drupal 7.x, please pull fosstp/drupal:7.x-1.0b

## How to use this image
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
