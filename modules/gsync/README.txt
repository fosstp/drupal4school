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

此模組讓你直接使用全誼校務行政系統來登入 Drupal 網站，此模組依靠系統核心模組 USER 所內建的 user_external 相關函式庫
，透過這個函式庫程式開發者可以在不新增資料表到系統情況下，實現帳號管理委由外掛模組處理的可能性。

全誼公司所開發的校務行政系統，使用雙服務的方式來管理帳號，IBM DB2 資料庫提供帳號管理功能，用來儲存使用者資訊，LDAP 服務則
提供帳號認證服務，用來儲存使用者自訂的密碼。

使用此模組將會由校務行政系統來管理 Drupal 的使用者，所以有關使用者資訊的變更都必須由校務行政系統來處理，例如：改變所屬部門
、職稱、電子郵件...等等，在 Drupal 的使用者編輯頁面中（profile form）變更使用者名稱或密碼，都會寫回校務行政系統，為了將來
能與 AD、Gmail 進行整合，因此模組提供 hook 函式給第三方模組可以同步進行相關資料的變更。

此模組將會關閉使用者註冊頁面，無論原始設定中你是否允許使用者註冊，只允許管理員可以新增使用者。另為了管理方便，在使用者管理頁面
，模組提供一個新的批次功能「重置校務行政系統密碼」，讓管理員可以一次把忘記密碼的所有使用者重置完成。老師帳號的密碼將會改為身份
證字號，學生密碼則改為您自訂的密碼樣式。

在校務行政系統資料庫中學生既沒有所屬部門（來自資料表:SCHDEPT）也沒有職稱（來自資料表:SCHPOSITION）所以此模組將使用自訂的
Drupal 角色（學生）作為學生登入後的所屬部門，然後使用學生目前就讀的班級名稱作為學生的職稱。當您啟用角色同步功能時，模組將會
立即讀取 IBM DB2 SCHDEPT 資料表中的資料並儲存到 Drupal 角色資料表中，如果使用者尚未登入，則這些角色會是空的。您可以手動
將使用者加入到這些同步的角色裡，或是啟用登入時自動同步角色的功能，來自動指派角色給使用者。注意：開啟同步後除了管理員角色之外
，其餘角色都會被清空並根據使用者的新身分重新指派角色給使用者。因此如果您手動設定某些帳號為管理員，不論如何同步他都會是管理員
，直到您手動移除他的管理員角色為止。

在校務行政系統中，無論學生帳號功能是否開啟，此模組都將使用學生的學號當成帳號，並且使用學生身分證字號後六個數字當密碼，這兩項
設定可以根據貴校的現況自行修改，如果您允許學生可以自訂密碼，那麼新密碼將儲存於 Drupal 資料庫中，學生將僅能使用自訂密碼來登
入，而無法再使用預設密碼登入，學生第一次登入時，會預先將預設密碼儲存為自訂密碼，以方便學生登入修改自訂密碼。此模組會讀取
STUDENT 資料表中的 MAIL 欄位作為學生的電子郵件，如果貴校學生並未輸入此項資料，您可以自訂該電子郵件的樣式，預設會使用學生學號
作為郵件信箱的帳號，您也可以自訂郵件帳號的來源。郵件的網域尾碼請自行輸入到設定欄位中，如果您有替學生申請 Google Apps 服務，
請直接輸入 Gmail 的網域尾碼。

模組也提供允許 Drupal 本地帳號登入的功能，以應付緊急需求，在啟用模組之初，從校務行政系統登入的帳號，並不具備管理員身份，您仍然
必須使用預設的 admin 帳號來管理網站，一旦額外的管理員設定後，您就可以將此功能關閉。當校務行政系統失去網路連線時，將會造成所有
帳號無法登入網站，如果您有開啟此項功能，則全校老師仍然可以使用可以使用儲存於 Drupal 資料庫中的密碼成功登入。如果您確認網路連線
障礙可以很快排除，或因為安排在同一臺虛擬機宿主主機裡不會有網路連線問題，建議將此功能關閉，以提高網站效能。

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

如果需要自行開發模組或透過系統核心的 PHP Filter 模組來撰寫程式，您可以從系統提供的使用者物件中，取得來自校務行政系統的額外資訊。
首先介紹取得使用者物件的方法：

global $user; //取得系統提供的目前使用者物件
或
$user = user_load_by_name('shane'); //取得帳號為 shane 的使用者物件

使用者物件中，包含以下變數：
$user->id 使用者的資料庫索引值，為數字
$user->name 使用者的帳號，為英文字母或數字
$user->realname 使用者的真實姓名，為中文字串
$user->gender 使用者的性別，male 為男性，female 為女性
$user->userclass 使用者的身份別，teacher 為老師，student 為學生，可以使用 if ($user->userclass == 'teacher') 來判斷使用者身份
$user->idno 使用者的身分證字號
$user->empid 使用者在校行政系統資料表中的索引值，如果是老師則為 TEABAS 資料表中的 TEAID 欄位，如果是學生帳號，則為 STUDENT 資料表中的 ID 欄位，可以配合模組所提供的 db2_query 函式來撈取校務行政系統資料庫。
$user->birthyear 使用者的出生年，西元
$user->birthmonth 使用者的出生月
$user->birthday 使用者出生月的第幾天
$user->proclass 老師的任教班級，當 $user->userclass == 'teacher' 時，才能使用
$user->depid 老師所屬的部門代號，可用來進一步撈取校務行政系統資料庫，當 $user->userclass == 'teacher' 時，才能使用
$user->titleid 老師的職務代號，當 $user->userclass == 'teacher' 時，才能使用
$user->depname 如果是老師帳號，為老師的所屬部門中文名稱，如果是學生則為 “學生” 兩個字
$user->titlename 如果是老師帳號，為老師的中文職稱，如果是學生則為就讀班級的中文名稱
$user->stdno 學生的學號，當 $user->userclass == 'student' 時，才能使用
$user->class 學生的就讀班級，為三位數字代號，當 $user->userclass == 'student' 時，才能使用
$user->seat 學生的座號，為數字，當 $user->userclass == 'student' 時，才能使用

底下介紹此模組提供的 API 函式：

1. get_current_seme()
   使用此函式取得目前的學年、學期和六年級學生的入學年，以陣列傳回。
   例如：
   $sdate = get_current_seme();
   print $sdate['year']; //得到目前的學年為三位數字
   print $sdate['seme']; //得到目前的學期，1 為上學期，2 為下學期
   print $sdate['seyear']; //得到目前的六年級學生的入學年，為三位數字

2. get_seme($year, $month)
   使用此函式自動幫你計算，所輸入的年份和月份，是屬於哪個學年，哪個學期，該學年的六年級學生是哪一年入學
   例如：
   $sdate = get_seme(103,12);
   print $sdate['year']; // 103
   print $sdate['seem']; // 1
   print $sdate['seyear']; // 098

3. db2_test()
   測試 IBM DB2 是否能成功連線，傳回值為 TRUE 或 FALSE
   例如：
   if (db2_test()) {
     // 連線成功
   }

4. ldap_test()
   測試 LDAP 伺服器是否能成功連線，傳回值 0 為成功連線，1 為網路不通，2 為帳號無法登入
   例如：
   $ret = ldap_test();
   if ($ret == 0) {
     // 連線成功
   }

5. db2_query($query, array $args = array(), array $options = array())
   此函式用來撈取校務行政系統資料庫，參數最少需要一個 SQL 字串，如果有帶變數，請勿直接寫入 SQL 字串中，而應該使用
   $args 陣列來帶變數，這樣才能防止 SQL injection 的駭客入侵手法。最後一個選用參數 $options 是用來傳遞資料庫
   操作參數給 IBM DB2，例如：資料紀錄游標的形式...等，詳細內容請看 http://php.net/manual/en/function.db2-set-option.php
   使用範例如下：
   1. 簡易查詢（所有變數來自程式，因此不會有 SQL injection）
   $rs = db2_query("select HOMEPAGE from TEABAS where TEAID='$user->empid'");
   if (db2_fetch_row($rs)) {
     print $rs[0]; //取得指定老師的班級網頁連結 
   }
   2. 複雜查詢（透過表單取得變數，需要檢查是否有 SQL injection 時使用）
   $sql = "select * from TEABAS where TEANAME like '%?%'"; // 注意：問號代表要帶入變數的位置
   $rs = db2_query($sql, array($_POST['search_word'])); //注意：陣列的元素個數必須匹配，查詢字串中?的個數，且使用相同順序
   echo "符合查詢條件的老師有：";
   while (db2_fetch_row($rs)) {
      echo $db2_result($rs, 'TEANAME'); //取得欄位 TEANAME 的資料
   }

6. db2_operate($query, array $args = array(), array $options = array())
   此函式用來對校務行政系統資料庫進行 SELECT 以外的操作，例如：INSERT、DELETE、UPDATE。參數設置的規則請參考 db2_query 的說明
   使用範例如下：
   $sql = "DELETE from TEABAS where TEANAME like '%?%'"; // 注意：問號代表要帶入變數的位置
   $ret = db2_operate($sql, array($_POST['search_word'])); //注意：陣列的元素個數必須匹配，查詢字串中?的個數，且使用相同順序
   if ($ret) {
      echo '所有符合條件的帳號都已經刪除！';
   }

7. ldap_admin()	
   取得 LDAP 伺服器的管理權限，以便讓後續的 LDAP 操作可以在管理權限下完成
   例如：
   $ldap_conn = ldap_admin();
   $ldap_users = ldap_search($ldap_conn, "(cn=P123456789)") //搜尋整個 LDAP 找出身分證字號為 P123456789 的使用者
   $ldap_user = ldap_first_entry($ldap_conn, $ldap_users); //取得使用者物件
   $dn = ldap_get_dn($ldap_conn, $ldap_user); //取得該使用者的命名空間
   ldap_delete($ldap_conn, $dn); //從 LDAP 伺服器刪除該使用者帳號 

8. ldap_login($user, $pass)
   檢測帳號密碼是否能通過 LDAP 的驗證，如果能則後續的 LDAP 操作會以該使用者的權限來進行
   例如：
   if (ldap_login('somebody', '1234')) {
     //登入成功     
   }
    
9. ldap_change_pass($user, $pass)
   變更 LDAP 使用者的密碼
   例如：
   $new_password = 'test';
   if (ldap_change_pass($user->name, $new_password)) {
     //密碼變更成功
   }

10. ldap_change_uid($user, $uid)
   變更 LDAP 使用者的 uid
   例如：
   $new_account = 'test';
   if (ldap_change_uid($user->name, $new_account)) {
     //使用者名稱變更成功
   }

HOOK FUNCTIONS
--------------

function hook_tpedu_teacher_resetpw($teachers, $result) {
  foreach ($teachers as $teacher) {
    if (isset($result->success[$teacher->uid]) && $result->success[$teacher->uid]) {
      if (alt_change_pass($teacher->name, $teacher->org_pass)) {
        $result->success[$teacher->uid] = TRUE;
      }
      else {
        $result->success[$teacher->uid] = FALSE;
      }
    }
  }
}

function hook_tpedu_student_resetpw($students, $result) {
  foreach ($students as $student) {
    if (isset($result->success[$student->uid]) && $result->success[$student->uid]) {
      if (alt_change_pass($student->name, $student->org_pass)) {
        $result->success[$student->uid] = TRUE;
      }
      else {
        $result->success[$student->uid] = FALSE;
      }
    }
  }
}

function hook_tpedu_sync_username($old_account, $new_account) {
  if (ad_account_exist($old_account)) {
    $memberof = ad_get_group($old_account);
    ad_delete_account($old_account);
    ad_create_account($new_account);
    ad_set_group($new_account, $memberof);
  }
}

function hook_tpedu_sync_password($account, $new_pass) {
  if (ad_account_exist($account)) {
    ad_replace_password($account, $new_pass);
  }
}

CREDITS
-------

Current Maintainer: 李忠憲 <leejoneshane@gmail.com>