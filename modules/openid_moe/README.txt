; $Id $

OpenID MOE
==============

這是為了與台灣教育部的單一簽入系統相容而設計，該系統將 OpenID 的標準欄位轉變用途，用來傳輸加密後之
身分證字號、姓名、學校與身份資訊

DESCRIPTION
-----------

此模組僅支援 OpenID Provider 用於將上述四個欄位提供給教育部各項網頁應用服務
詳細的欄位轉換與對應，不在此詳述。

REQUIREMENTS
-------------

OpenID Providers

* 校務行政系統認證模組
* OpenID Provider - provides OpenID authentication to other sites.
* OpenID Provider AX - implements OpenID Attribute Exchange provider side.


CREDITS
---------------
模組開發：李忠憲 <leejoneshane@gmail.com>
