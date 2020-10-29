# Drupal學校架站包計劃（D4S: Drupal for School）
這是一個 [docker](https://www.docker.com/) 映像檔，此映像檔內容包含：debian 10.2(buster-slim) + php 7.3 + apache 2.4 + drupal 9.0.7 + drush 10 + D4S 專案模組 + D4S Bootstrap 版型（開發中，請勿啟用）。
目前已納入臺北市校園單一身分驗證服務的各級學程所有學校都可以使用，不再受限於各別校務行政系統的開放性。

drupal/console 尚未完全支援 drupal 9，因此改用 drush 10，兩者的差別請看[這篇文章](https://www.cmsdrupal.com/blog/drupal-console-vs-drush-should-you-run-both-or-stick-one-each-cli-tools-most-powerful-commands)。

目前已經完成的模組和功能概述如下：
* tpedu 模組：使用臺北市校園單一身分驗證帳號登入、介接學校全域資料、通過台灣無障礙網頁標章 2.0、校務行政關聯式欄位。
* gsync 模組：將臺北市校園單一身分驗證師生帳號同步到 G Suite 網域指定的子機構中。
* adsync 模組：將臺北市校園單一身分驗證 __教師__ 帳號同步到 Windows 網域。
* thumblink 模組：提供「縮圖連結」內容類型和「相關網站」區塊。
* tpedunews 模組：為您啟用 aggregator 模組，將教育局最新消息６個 RSS feed 新增到新聞聯播中，並提供「教育局最新消息」區塊。
* schoolnews 模組：提供「最新消息」內容類型以及「校園最新消息」頁面，可以直接設定為網站首頁。
* gevent 模組： 提供內容類型：「行事曆事件」、Views 顯示模式：「學校行事曆」、傳統行事曆頁面，編輯事件時會自動同步到 G Suite

## 單機測試環境
在 Windows 或 Mac 工作站上進行架站測試與套件開發，請先安裝 [docker desktop](https://www.docker.com/products/docker-desktop) ，然後啟動主控台，執行底下介紹的 docker-compose 指令。

## 單機運作環境
請依照 docker 官方文件安裝 [docker engine](https://docs.docker.com/engine/install/) 和 [docker compose](https://docs.docker.com/compose/install/)，然後在文字模式執行底下介紹的 docker-compose 指令。

## 私有雲運作環境
*   在 NAS 上建立 NFS 共享資料夾，並掛載到所有的集群電腦上，掛載點為 /nas_storage
*   在所有的集群電腦上安裝 [docker engine](https://docs.docker.com/engine/install/) 和 [docker compose](https://docs.docker.com/compose/install/)
*   啟用 Docker Swarm：

        docker swarm init
*   建立 overlay 網路：

        docker network create -d overlay --attachable core-infra
*   建立 docker swarm 儲存池，可透過 NFS 掛載 NAS 資料夾或使用本地空間：

        mkdir /storage
*   在儲存池內建立 Volume：certs、traefik、portainer
*   下載組態檔 [swarm-core.yml](https://raw.githubusercontent.com/fosstp/drupal4school/master/swarm-core.yml) 並修改組態中的網址設定：
    *   將管理員郵件 your@mail.com 修改為您的郵件地址
    *   將網域名稱 xxps 修改為貴校的網域
*   建立核心服務：

        docker stack deploy --compose-file=swarm-core.yml core
*   在 DNS 為管理頁面加入紀錄：

    >traefik.貴校網域.tp.edu.tw
    
    >portainer.貴校網域.tp.edu.tw
*   使用瀏覽器連結上述管理頁面

## docker-compose
要架設一個 drupal 網站最簡單的方法就是使 docker-compose 指令。請先下載 [docker-compose.yml 範例檔](https://github.com/fosstp/drupal4school/blob/master/docker-compose.yml)，下載完成後請修改檔案中的環境變數、磁碟掛載路徑...等等參數，然後再執行底下的指令：

    docker-compose up -d

要移除所有啟動的容器則使用以下指令：

    docker-compose down

要將 drupal 容器更新到最新版，而不影響已經運作之站台（保留所有站台資料和模組設定），請用以下指令：

    docker pull fosstp/drupal

    docker-compose up -d

環境變數說明如下：
* DB_HOST: 資料庫容器名稱，請直接使用預設值「mysql」，除非您要使用獨立資料庫（例如：已存在之容器、獨立主機、Vmware 虛擬機或其它線上資料庫）。
* DB_USER: 資料庫連線帳號，預設為「root」
* DB_PASSWORD: 資料庫連線密碼，預設為「dbpassword」，請務必修改。

資料庫名稱預設為 drupal，容器啟動時會自動為您建立。

* SITE_NAME: 網站名稱，預設為「快樂國小官方網站」
* SITE_MAIL: 網站聯絡信箱，預設為「webmaster@xxps.tp.edu.tw」
* SITE_ADMIN: 網站管理帳號，預設為「admin」
* SITE_ADMIN_MAIL: 網站管理員的電子郵件，your_mail@xxps.tp.edu.tw
* SITE_PASSWORD: 網站管理員密碼，預設為「your_password」，請務必修改密碼

以上環境變數將透過 drush 或 drupal console 自動為您安裝網站，無需透過網頁進行任何設定，網站將直接啟用。如要變更以上參數，必須將所有舊容器移除重新啟動（所有資料將會遺失，一切重來）：

    docker-compose down

    docker-compose up -d

## 手動建立 Drupal 容器
您可以依照以下步驟，手動建立所有必要的容器。 使用此映像檔的基本語法如下：

    docker run --name drupal -p 80:80 -d fosstp/drupal

要建立舊版的 drupal 環境，請在最後面加上版序，例如：

    docker run --name drupal -p 80:80 -d fosstp/drupal:8

或

    docker run --name drupal -p 80:80 -d fosstp/drupal:7

其中 --name 為容器名稱，範例為 drupal， -p 指定對外連線埠號，範例為 80， -d 指定來源映像檔，範例為本專案所建立的映像檔 fosstp/drupal。

容器啟動後，可以使用 http://localhost 或 http://主機的真實IP 語法連結 drupal 網站。

有許多資料庫可以支援此映像檔，我們建議您採用 mysql docker 映像檔來部署資料庫，說明如下。

## 手動建立 MySQL 容器
請使用下列指令建立 mysql 容器：

    docker run --name mysql -e MYSQL_DATABASE=drupal -e MYSQL_ROOT_PASSWORD=dbpassword -d mysql --default-authentication-plugin=mysql_native_password

上述指令之說明如下：

--name 為容器名稱 mysql，-e 為設定環境變數，環境變數 MYSQL_DATABASE 為容器啟動時要自動建立的資料庫 drupal，環境變數 MYSQL_ROOT_PASSWORD 則指定該資料庫系統的管理員 root 密碼為 dcpassword。

最後的容器啟動指令 --default-authentication-plugin=mysql_native_password 可以讓 MySql 8 使用 MySql 5 的加密演算法，由於 Linux 系統暫時還不支援 MySql 8 的編碼方式，因此想要與 phpMyAdmin 搭配使用就必須修改為向下相容。

## 手動建立 phpMyAdmin 容器
我們建議您使用以下指令，安裝 phpmyadmin 容器，作為管理資料庫之用：

    docker run --name phpmyadmin --link mysql:db -p 8080:80 -d phpmyadmin

在上面的範例中，我們使用 --link 參數將 mysql 容器指派給 phpmyadmin 容器，並且將主機別名設定為 db。當 phpmyadmin 容器啟動時，會自動連上 mysql 容器，但您仍然需要自己輸入管理員 root 及其密碼 dbpassword。
