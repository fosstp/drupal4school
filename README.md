# Drupal學校架站包計劃
這是一個 [docker](https://www.docker.com/) 映像檔，此映像檔內容包含：debian 10.2(buster-slim) + php 7.3 + apache 2.4 + drupal 8.9.1 + 臺北市校園單一身份驗證模組。

目前已經完成的模組和功能概述如下：
tpedu 模組：使用臺北市校園單一身分驗證帳號登入、介接學校全域資料、通過台灣無障礙網頁標章 2.0、校務行政關聯式欄位。
gsync 模組：將臺北市校園單一身分驗證師生帳號同步到 G Suite 網域指定的子機構中。

還在開發的模組有：AD 同步模組、行事曆（與 G Suite 線上同步功能）、最新消息（包含局端的 RSS 新聞聯播）

## docker-compose
要架設一個 drupal 網站最簡單的方法就是使用以下指令：

$ docker-compose up -d

要移除所有啟動的容器則使用以下指令：

$ docker-compose down

當然，您應該要先修改 docker-compose.yml 組態檔中的網路埠號、資料庫密碼、磁碟掛載路徑...等等參數，然後再建立容器。或者，您也可以依照以下步驟，手動建立所有必要的容器。

## Drupal
使用此映像檔的基本語法如下：

$ docker run --name drupal -p 80:80 -d fosstp/drupal

其中 --name 為容器名稱，範例為 drupal， -p 指定對外連線埠號，範例為 80， -d 指定來源映像檔，範例為本專案所建立的映像檔 fosstp/drupal。

容器啟動後，可以使用 http://localhost 或 http://主機的真實IP 語法連結 drupal 網站。

有許多資料庫可以支援此映像檔，我們建議您採用 mysql docker 映像檔來部署資料庫，說明如下。

## MySQL
請使用下列指令建立 mysql 容器：

$ docker run --name mysql -e MYSQL_DATABASE=drupal -e MYSQL_ROOT_PASSWORD=dbpassword -d mysql --default-authentication-plugin=mysql_native_password

上述指令之說明如下：

--name 為容器名稱 mysql，-e 為設定環境變數，環境變數 MYSQL_DATABASE 為容器啟動時要自動建立的資料庫 drupal，環境變數 MYSQL_ROOT_PASSWORD 則指定該資料庫系統的管理員 root 密碼為 dcpassword。

最後的容器啟動指令 --default-authentication-plugin=mysql_native_password 可以讓 MySql 8 使用 MySql 5 的加密演算法，由於 Linux 系統暫時還不支援 MySql 8 的編碼方式，因此想要與 phpMyAdmin 搭配使用就必須修改為向下相容。

## phpMyAdmin
我們建議您使用以下指令，安裝 phpmyadmin 容器，作為管理資料庫之用：

$ docker run --name phpmyadmin --link mysql:db -p 8080:80 -d phpmyadmin

在上面的範例中，我們使用 --link 參數將 mysql 容器指派給 phpmyadmin 容器，並且將主機別名設定為 db。當 phpmyadmin 容器啟動時，會自動連上 mysql 容器，但您仍然需要自己輸入管理員 root 及其密碼 dbpassword。

## 如何讓 Drupal 連結 Mysql？
當第一次連結此映像檔所建置的網站時，會進入簡要的設定程序，【資料庫設定】為設定程序中的關鍵步驟，該程序所需資料如下：

__資料庫名稱：__ drupal

__資料庫管理員：__ root

__資料庫管理密碼：__ MYSQL docker 指令中所指定的密碼（範例為 dbpassword）

__資料庫主機：__ MYSQL docker 指令中所指定的 --name 參數（範例為 mysql）

您也可以使用環境變數指定上述參數，例如：

docker run --name drupal -e DB_HOST=mysql -e DB_USER=root -e DB_PASSWORD=dbpassword -p 80:80 -d fosstp/drupal
