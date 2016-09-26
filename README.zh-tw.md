# Drupal學校架站包計劃

[![Build Status](https://travis-ci.org/fosstp/drupal4school.svg?branch=master)](https://travis-ci.org/fosstp/drupal4school)
[![](https://images.microbadger.com/badges/version/fosstp/drupal.svg)](http://microbadger.com/images/fosstp/drupal "Get your own version badge on microbadger.com")
[![](https://images.microbadger.com/badges/image/fosstp/drupal.svg)](http://microbadger.com/images/fosstp/drupal "Get your own image badge on microbadger.com")

這是一個 [docker](https://www.docker.com/) 映像檔，此映像檔內容包含：debian 8.2(jessie) + php 5.6 + apache 2.2 + drupal 7 + 校務行政模組及無障礙版型

如何使用此映像檔
使用此映像檔的基本語法如下，其中第一個 drupal 為微虛擬機名稱，最後一個 fosstp/drupal 為映像檔名稱：

$ docker run --name drupal -d fosstp/drupal

如果您想要對外提供服務，必須讓訪客透過主機的真實IP連接微虛擬機，而非使用微虛擬機的虛擬介面IP，使用以下語法進行通訊埠對應：

$ docker run --name some-drupal -p 80:80 -p 443:443 -d fosstp/drupal

然後，使用 http[s]://localhost 或 http[s]://主機的真實IP 語法連結 drupal 網站。

有許多資料庫可以支援此映像檔，我們建議您採用 docker 映像檔來部署資料庫。根據 Drupal 7 的預設值，SQLite 是最簡單易用的資料庫，採用文檔形式儲存資料。其他還有許多資料庫系統可以採用，細節在下文中介紹：
當第一次連結此映像檔所建置的網站時，會進入簡要的設定程序，【資料庫設定】為設定程序中的關鍵步驟，該程序所需資料會在說明中提供給您！

## MySQL
$ docker run --name mysql -e MYSQL_ROOT_PASSWORD=資料庫管理密碼 -d mysql/mysql-server

$ docker run --name drupal --link mysql:db -e DATABASE_PASSWORD=資料庫管理密碼 -d fosstp/drupal

資料庫類型：MySQL, MariaDB, 或相容資料庫。上述範例則是使用 mysql 官網(非 docker 官網)組建的 docker 映像檔。
資料庫名稱：需要透過指令或 phpmyadmin 網頁介面先把空白資料庫建好。我們建議您使用以下指令，安裝一台 phpmyadmin 微虛擬機，作為管理資料庫之用：

$ docker run --name phpmyadmin --link mysql:db -p 8080:80 -d phpmyadmin/phpmyadmin

資料庫管理帳號/密碼：您可以使用以下環境變數指派給 mysql 微虛擬機，(-e MYSQL_USER=, -e MYSQL_PASSWORD=)，上述範例是使用預設的 root 帳號作為管理員，並指定管理員密碼。
資料庫主機名稱：上述範例是透過 --link mysql:db 參數，在 drupal 微虛擬機的 /etc/hosts 新增一台名稱為 db 的紀錄，並連結到名稱為 mysql 的微虛擬機。

## PostgreSQL

$ docker run --name postgres -e POSTGRES_PASSWORD=資料庫管理密碼 -d postgres

$ docker run --name drupal --link postgres:db -e DATABASE_PASSWORD=資料庫管理密碼 -d fosstp/drupal

資料庫類型：PostgreSQL
資料庫名稱/管理帳號/密碼：<詳情請參考[DockerHub](https://hub.docker.com/_/postgres/)的說明>
資料庫主機名稱：上述範例是透過 --link postgres:db 參數，在 drupal 微虛擬機的 /etc/hosts 新增一台名稱為 db 的紀錄，並連結到名稱為 postgres 的微虛擬機。
