# Drupal學校架站包計劃
這是一個 [docker](https://www.docker.com/) 映像檔，此映像檔內容包含：debian 10.2(buster-slim) + php 7.3 + apache 2.4 + drupal 8.8 + 臺北市教育人員單一身份驗證模組。

## 如何使用此映像檔
使用此映像檔的基本語法如下，其中第一個 drupal 為容器名稱，最後一個 fosstp/drupal 為 docker 映像檔名稱：

$ docker run --name drupal -p 80:80 -d fosstp/drupal

然後，使用 http://localhost 或 http://主機的真實IP 語法連結 drupal 網站。

有許多資料庫可以支援此映像檔，我們建議您採用 mysql docker 映像檔來部署資料庫。
當第一次連結此映像檔所建置的網站時，會進入簡要的設定程序，【資料庫設定】為設定程序中的關鍵步驟，該程序所需資料如下：

__資料庫名稱：__ drupal

__資料庫管理員：__ root

__資料庫管理密碼：__ 下方 docker 指令中所指定的密碼（範例為 dbpassword）

__資料庫主機：__ 下方 docker 指令中所指定的 --name 參數（範例為 mysql）

您也可以使用環境變數指定上述參數，例如：
docker run --name drupal -e DB_HOST=mysql -e DB_USER=root -e DB_PASSWORD=dbpassword -p 80:80 -d fosstp/drupal

## MySQL
$ docker run --name mysql -e MYSQL_ROOT_PASSWORD=dbpassword -d mysql --default-authentication-plugin=mysql_native_password

資料庫類型：MySQL, MariaDB, 或相容資料庫。上述範例則是使用 docker 官網組建的映像檔。
資料庫名稱：需要透過指令或 phpmyadmin 網頁介面先把空白資料庫建好。我們建議您使用以下指令，安裝一台 phpmyadmin 微虛擬機，作為管理資料庫之用：

$ docker run --name phpmyadmin --link mysql:db -p 8080:80 -d phpmyadmin/phpmyadmin

資料庫管理帳號/密碼：您可以使用以下環境變數指派給 mysql 微虛擬機，(-e MYSQL_USER=, -e MYSQL_PASSWORD=)，上述範例是使用預設的 root 帳號作為管理員，並指定管理員密碼。
