CONTENTS OF THIS FILE
---------------------

  * Summary
  * Requirements
  * Installation
  * Google Configuration
  * Configuration
  * Api functions
  * Credits


SUMMARY
-------

這個模組可以把校務行政帳號批次匯入到 Google Apps 網域，並會自動依照所屬部門以及職稱區分群組
此模組依賴校務行政登入模組的 hook 函式進行模組整合，請先安裝啟用該模組。

REQUIREMENTS
------------
1. Libraries API - This modules depends on libraries apis module.
You can download from http://drupal.org/project/libraries
    
2. Google client library - You need to download google api php client library
from https://github.com/google/google-api-php-client

3. 校務行政系統認證模組

GOOGLE CONFIGURATION
--------------------
1. Visit https://console.developers.google.com
2. 建立一個新專案
3. 在 API 頁面，啟用您需要的 Google Data API，以 Google Apps 來說您需要
   Directory API(包含於 Admin SDK 中)，您也可以順便把 Calendar API 打開，
   假如您稍候想要安裝 Google 行事曆同步模組的話
4. 到憑證頁面，新增用戶端 ID，您需要建立網頁應用程式以及服務帳號兩種用戶端，
   前者是給 Google 單一登入用的，後者用來同步帳號和密碼
5. 將所產生的用戶端相關資訊抄下來，將憑證金鑰 .P12 檔案下載回來
6. 修改 redirect_uri 相關資訊以配合 Drupal 網站的運作，在模組設定畫面中會告訴您
   該如何設定
7. 完成模組設定後，會發現連線測試失敗，先別急，您還沒設定完成
8. 請在 Google Apps 主控台裡，把 Google Data API 存取權限授權給您建立的專案，
   用戶端名稱輸入服務帳號的 client ID，要授權的 API 範圍輸入：
   https://www.googleapis.com/auth/admin.directory.group,
   https://www.googleapis.com/auth/admin.directory.group.member,
   https://www.googleapis.com/auth/admin.directory.orgunit,
   https://www.googleapis.com/auth/admin.directory.user,
   https://www.googleapis.com/auth/admin.directory.user.alias,
   如果想使用行事曆同步，請加上
   https://www.googleapis.com/auth/calendar
9. 這些步驟都完成後，請重新啟動 apache 讓 Google API php client 函式庫起作用 

 API FUNCTIONS
 -------------

1. gapps_sso()
   修改系統登入畫面，提供 Google 登入連結並自動進行單一登入

2. gapps_service(domain)
   連結到您指定的 Google Apps 網域，自動進行認證，認證成功後傳回 Google Directory Service 物件

3. gapps_test(domain);
   測試 Google Apps 是否能正常連線，檢查必要的設置是否已經設定完成！

4. gapps_change_uid(domain,user_name,new_name)
   將指定網域的使用者修改成新的使用者名稱

5. gapps_change_pass(domain,user_name,new_password)
   變更指定網域使用者的密碼

CREDITS
-------

模組開發與維護：李忠憲 <leejoneshane@gmail.com>