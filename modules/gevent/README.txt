CONTENTS OF THIS FILE
---------------------

  * Summary
  * Requirements
  * Api functions
  * Credits


SUMMARY
-------

此模組用來將您自定的內容類型同步到 Google 行事曆。

當您思考要為站台提供行事曆時，通常有兩個選擇，一是管理較為簡單，但效能較差的內容類型，
將行事曆事件新增為內容類型，將會由系統核心的 node 模組來進行管理，由於內容類型日益增加
而且預設允許修訂版本的管理，每個事件都會被儲存在兩個不同的資料表中，以便進行版本比對
因此效率低落。

另一個選擇是是建立一個新的 entity，由於系統核心只把 entity 當成程式邏輯的一部份
因此不提供 entity 管理介面，您必須安裝額外的模組來管理 entity，或是自行撰寫模組

不論你的選擇是哪一個，您都必須要有專為行事曆而設計的日期欄位，這個欄位由 Date API 模組
提供，有了這個欄位後才能建立行事曆事件，如果您選擇效率較高的 entity 想必您已經會開發模組
因此同步到 Google 行事曆功能應該可以自己完成設計。

本模組僅提供給透過網站管理介面，手動自訂內容類型的管理者使用，因為您用的是一般通用方法
行事曆事件也僅被 drupal 系統視為一般頁面內容，雖然您知道使用 Views 模組和 Calendar 模組
可以快速建立一個行事曆頁面，它看起來幾乎跟 Google 行事曆一樣，但是卻只能把行事曆事件儲存
在站台，無法提供 Google 行事曆的推播和行程規劃功能，因此您需要安裝此模組來將行事曆事件
同步到 Google 行事曆，以便獲得這些額外的好處。

此模組僅提供即時同步，批次同步功能您可以安裝 Calendar iCal 模組。

此模組會在您建立好的行事曆頁面中，增加ㄧ個傳統表格式行事曆分頁，並提供友善列印功能
如果貴單位還有印製行事曆給員工的習慣，或許您會需要這個功能！

REQUIREMENTS
------------
1. Date API
   
2. 校務行政系統中的 Google Apps 同步模組

 API FUNCTIONS
 -------------

1. gevent_service()
   This function will return a Google Calendar Service object.

2. gevent_test()
   check your module settings is complete or not!

CREDITS
-------

程式開發與維護: 李忠憲 <leejoneshane@gmail.com>