# auto-drupal

fosstp 臺北市自由軟體推動小組 — 自動安裝校園網站 Drupal 指令集

## 環境需求

請先確認您的環境：

1. 必須為 Debian / Ubuntu 或 RedHat / CentOS 等 linux 作業系統 (實體機或虛擬機皆可)

   (建議全新安裝一個最小化 linux，若不是全新安裝請先移除原本的網頁伺服器與 Mysql 伺服器避免衝突)

2. 必須已經設定好網路，能夠上網

3. 使用系統管理員 root 權限，執行下列指令進行自動化安裝程序，並於安裝程序中依照提示輸入相關資料

## 安裝流程

### Debian / Ubuntu 系統

請使用系統管理員 root 權限，執行底下指令
```bash
wget -O auto-drupal.sh https://raw.githubusercontent.com/liao-chianan/auto-drupal/master/auto-drupal.sh;chmod +x auto-drupal.sh;./auto-drupal.sh
```

------------------------------------------------------------------------------------------------------------------
### RedHat / CentOS 系統

請使用系統管理員 root 權限，執行底下指令

```bash
yum -y install wget;wget -O auto-drupal.sh https://raw.githubusercontent.com/liao-chianan/auto-drupal/master/auto-drupal.sh;chmod +x auto-drupal.sh;./auto-drupal.sh
```

### Qnap / Synology 系統 (終止開發)

**Qnap / Synology 使用之 Script 因人力有限已不再維護，有需求者請自行下載修改**

如果您是使用 Qnap / Synology 系統

1. 請確認已經安裝 Docker (Qnap 的 Container Station) (Synology 的 Docker 套件)
2. 使用最高權限登入 SSH 命令列畫面 (Qnap 使用 admin) (Synology 使用 root)
3. 執行底下指令

```shell
curl -kO https://raw.githubusercontent.com/liao-chianan/auto-drupal/master/autodrupal-qnap-synology.sh ;chmod +x autodrupal-qnap-synology.sh;./autodrupal-qnap-synology.sh
```
