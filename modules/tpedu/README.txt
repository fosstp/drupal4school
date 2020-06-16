CONTENTS OF THIS FILE
---------------------

  * Summary
  * Installation
  * New Token
  * Api functions
  * Hook functions
  * Credits


SUMMARY
-------



INSTALLATION
------------

1. 請透過系統提供的模組安裝功能進行安裝
2. 啟用模組後，先從管理介面中的界面翻譯頁面，點選匯入分頁，將壓縮黨內的兩個中文語系檔上傳到系統。
3. 從管理介面啟用你所需要的模組，並進行模組設定。

目前模組尚未成立 Drupal.org 專頁，有任何問題請以電子郵件直接聯絡作者。

NEW TOKEN
---------

所謂 TOKEN 是系統將動態內容提供給模組或使用者運用的一個巨集取代系統，例如：使用者可以在所新增的文章裡，使用：
親愛的[user:name]，您好：
經過巨集取代後，在網頁顯示出來的，將會是登入者的真實姓名，例如：
親愛的李忠憲，您好：
不同的使用者登入都會在文章中看到自己的名字。

此模組會透過系統提供的 hook_username_alter 函式來修改使用者名稱的顯示，同時也會提供額外的 TOKEN 給您使用，由於系統內建的
[user:name] 名稱是來自 hook_username_alter 函式，因此根據您在模組內的設定，可能顯示為帳號名稱（非來自校務行政系統的使用
者）、真實姓名、所屬部門＋真實姓名、所屬部門＋職稱＋真實姓名、職稱＋真實姓名。

其他新增的 TOKEN 有：
[user:username]將代入目前使用者的帳號，通常是英文字母或數字。
[user:gender]將代入目前使用者的性別。
[user:userclass]將代入使用者的身份，teacher 或是 student，必須自行翻譯成中文。
[user:idno]將代入目前使用者的身分證字號。
[user:birthyear]將代入目前使用者的出生年，為西元年四位數字。
[user:birthmonth]將代入目前使用者的出生月份。
[user:birthday]將代入目前使用者的出生月份的日期。
如果想要完整的生日，可以使用 [user:birthyear]/[user:birthmonth]/[user:birthday] 來取得。
[user:proclass]將代入目前使用者的任教班級，無任教班級時為空字串。
[user:depname]將代入目前使用者的所屬部門。
[user:titlename]將代入目前使用者的職稱。
[user:stdno]將代入目前使用者的學號。
[user:class]將代入目前使用者的就讀班級。
[user:seat]將代入目前使用者的座號。

API FUNCTIONS
-------------


CREDITS
-------

Current Maintainer: 李忠憲 <leejoneshane@gmail.com>