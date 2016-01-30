CONTENTS OF THIS FILE
---------------------

  * Summary
  * Configuration
  * Api functions
  * Credits


SUMMARY
-------

這個模組可以把校務行政帳號批次匯入到微軟網域主控站，並會自動依照所屬部門以及職稱區分群組
此模組依賴校務行政登入模組的 hook 函式進行模組整合，請先安裝啟用該模組。

CONFIGURATION
-------------

微軟的 AD 與一般所使用的 OpenLDAP 有一個基本的差異，就是在安全通訊上使用不同的通訊協定
AD 使用 LDAPS 進行安全連線而 OpenLDAP 則是使用 LDAP-TLS 進行安全連線，前者使用 636
連接埠，後者使用 389 連接埠。這些差異使得在 Drupal 上想要管理 AD 需要許多的調校。

首先是 AD 預設不開啟 LDAPS，因此除了使用 ADSI 工具之外，並無法去修改使用者密碼，或新增
、刪除使用者。可惜的是，如果你的 Drupal 裝在 Linux 上，那當然沒有 ADSI 可以用。唯一的
方法就是讓 AD 打開 LDAPS。

要開啟 LDAPS 需要有 SSL 憑證，SSL 憑證可以付費取得，也可以自行產生。最簡單的方法就是在
AD 上安裝 CA 憑證伺服器，如果在安裝時有選擇要作為 企業 用途，而非 獨立 運作，那麼企業級
CA 伺服器（簡稱：AD-CS）會自動為網域內的所有主控站產生憑證金鑰。而不需要從產生憑證要求檔
開始一連串的製作憑證作業。

AD-CS 安裝完成後，透過系統管理工具裡的憑證管理員，將憑證會出成為 .cer 憑證檔案，複製到
Linux 上的 /etc/openldap/certs 資料夾內，這樣就完成 LDAPS 憑證的安裝。

前面說過 OpenLDAP 進行安全連線時，會透過 TLS 而不會使用 SSL，因此下一步就是要修改 OpenLDAP
設定檔，讓它改變金鑰驗證的程序為 SSL，請修改 /etc/openldap 目錄裡的 ldap.conf 檔案，
在檔案任何位置加入一行：
TLS_REQCERT never

設定好了，記得要重新啟動 apache 讓變更生效。

API FUNCTIONS
-------------

1. ad_test()
檢測 AD 相關設定值是否正確。

2. ad_admin()
使用 LDAPS 連接 AD 以便取得完整控制權。

3. ad_login($user, $pass)
使用 LDAP 通訊協定進行一般使用者的登入驗證。

4. ad_change_uid($user, $new_account)
改變 AD 使用者的登入帳號，改變後使用者帳號不變，但是登入時必須使用新的登入名稱。

5. pwd_encryption($password)
把純文字密碼重新加密為 AD 所使用的密碼。

6. ad_change_pass($user, $new_pass)
改變 AD 使用者的登入密碼。

CREDITS
-------
模組開發與維護: 李忠憲 <leejoneshane@gmail.com>