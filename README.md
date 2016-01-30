# drupal
This docker image contains centos 6 + php 5.4 + ibm db2 v10.5 x64 client + apache 2.2 + drupal 7

This image not include database manager.
There has a lot database images can work with Drupal, so pull what you want, like:
docker pull mysql
then you can run drupal with this command:
docker run --name drupal --link mysql:mysql -p 80:80 -p 443:443 -d leejoneshane/drupal
