<?php

namespace Drupal\gevent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GeventConfigForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'gevent_settings_form';
    }

    protected function getEditableConfigNames()
    {
        return [
            'gevent.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('gevent.settings');
        $form['helper'] = [
            '#type' => 'markup',
            '#markup' => '<p>要使用 G Suite 帳號單一簽入功能，您必須建立 Google 開發專案並取得 Google 發給您的<em>網路應用程式憑證</em>，請依照以下步驟取得相關組態值：'.
            '<ol><li>請連結 <a href="https://console.cloud.google.com/apis/dashboard">Google apis 主控台</a>，如果還沒有介接專案，請先建立專案！</li>'.
            '<li>請進入「資料庫」管理頁面，為該專案啟用這兩個 API：Admin SDK、Google Calendar API。至少應包含這兩個 API，如果要自行開發模組的話當然也可以啟用更多 API。</li>'.
            '<li>請進入「憑證」管理頁面，然後建立「服務帳戶」憑證，並且需要從 G Suite 「管理主控台」->「安全性」->「API 權限」->「全網域委派」進行全域授權，請參考<a href="https://support.google.com/a/answer/162106?hl=zh-Hant">這篇文章</a>。</li>'.
            '<li>全域授權時，需輸入該專案的服務帳戶用戶端編號，授權範圍至少應包含：<ul>'.
            '<li>https://www.googleapis.com/auth/admin.directory.orgunit</li>'.
            '<li>https://www.googleapis.com/auth/admin.directory.user</li>'.
            '<li>https://www.googleapis.com/auth/admin.directory.group</li>'.
            '<li>https://www.googleapis.com/auth/admin.directory.group.member</li>'.
            '<li>https://www.googleapis.com/auth/calendar</li>'.
            '<li>https://www.googleapis.com/auth/calendar.events</li>'.
            '</ul></li>'.
            '<li>線上測試 OAuth 用戶端 API 資料存取，請連到 <a href="https://developers.google.com/oauthplayground/">OAuth playground</a>。</li>'.
            '</ol>',
        ];
        $validators = [
            'file_validate_extensions' => ['json'],
        ];
        $form['google_service_json'] = [
            '#type' => 'file',
            '#title' => 'Google 服務帳號授權驗證 JSON 檔',
            '#description' => $config->get('google_service_json') ? '授權驗證檔案已經上傳，如沒有要變更金鑰，請勿再上傳' : '請從 Google apis 主控台專案管理頁面下載上述「服務帳戶」所提供的 JSON 檔案並上傳到這裡。',
        ];
        $form['google_domain'] = [
            '#type' => 'textfield',
            '#title' => 'G Suite 網域',
            '#default_value' => $config->get('google_domain'),
            '#description' => '請設定 G Suite 網域名稱，通常是貴機構的 DNS 域名。',
        ];
        $form['google_domain_admin'] = [
            '#type' => 'textfield',
            '#title' => 'G Suite 管理員帳號',
            '#default_value' => $config->get('google_domain_admin'),
            '#description' => '請設定 G Suite 網域的管理員郵件地址，該管理員必須具備該網域的最高管理權限。Google 服務帳號將以該管理員的身份進行資料操作。',
        ];
        $form['teacher_orgunit'] = [
            '#type' => 'textfield',
            '#title' => '教師帳號所在的機構',
            '#default_value' => $config->get('teacher_orgunit'),
            '#description' => '如果您使用子機構來區分教師與學生帳號，請在這裡輸入教師帳號子機構的階層路徑，最高層級為 <strong>/</strong>，假如您輸入<strong>/小學部/教師帳號</strong>，意味著所有的教師帳號將會同步到第二層級機構<strong>小學部</strong>的子機構<strong>教師帳號</strong>中。由於機構僅用來套用 Google 的相關設定，無法如群組一般擁有郵寄清單和論壇主頁，機構為階層結構，而群組為巢狀結構，所以您不應該使用機構來對教師帳號或學生帳號做進一步的分類，而應該使用群組來進行分類。本模組將依循此法則進行帳號同步作業！',
        ];
        $form['student_orgunit'] = [
            '#type' => 'textfield',
            '#title' => '學生帳號所在的機構',
            '#default_value' => $config->get('student_orgunit'),
            '#description' => '如果您使用子機構來區分教師與學生帳號，請在這裡輸入學生帳號子機構的階層路徑，最高層級為 <strong>/</strong>，假如您輸入<strong>/小學部/學生帳號</strong>，意味著所有的學生帳號將會同步到第二層級機構<strong>小學部</strong>的子機構<strong>學生帳號</strong>中。',
        ];
        $form['student_account'] = [
            '#type' => 'select',
            '#title' => '學生帳號的樣式',
            '#multiple' => false,
            '#options' => [
                'id' => '學號',
                'account' => '臺北市校園單一身分驗證登入帳號',
            ],
            '#size' => 1,
            '#default_value' => $config->get('student_account') ?: 'id',
            '#description' => '建立學生帳號時，要以什麼資料當作預設帳號？',
        ];
        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => '儲存組態',
            ],
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $error = '';
        $message = '';
        $config = $this->config('gsync.settings');
        $values = $form_state->cleanValues()->getValues();
        foreach ($values as $key => $value) {
            if ($key == 'google_service_json') {
                $file = file_save_upload('google_service_json', ['file_validate_extensions' => ['json']], 'public://gsync', 0, FILE_EXISTS_REPLACE);
                if ($file) {
                    $file->setPermanent();
                    $file->save();
                    $config->set($key, $file->getFileUri());
                    $message = 'Google 服務帳戶的金鑰檔案已經更新。';
                }
            } else {
                $config->set($key, $value);
            }
        }
        $config->save();
        $ok = false;
        if ($config->get('google_service_json') && $config->get('google_domain') && $config->get('google_domain_admin')) {
            $directory = initGoogleDirectory();
            if ($directory && gs_getUser($config->get('google_domain_admin'))) {
                $ok = true;
            }
        }
        if ($ok) {
            $config->set('enabled', true);
            $config->save();
            $message .= '所有設定已經完成並通過 G Suite API 連線測試，模組已經啟用。';
            \Drupal::messenger()->addMessage($message, 'status');
        } else {
            $config->set('enabled', false);
            $config->save();
            $message .= 'G Suite API 連線測試失敗，模組無法啟用。';
            \Drupal::messenger()->addMessage($message, 'warning');
        }
    }
}
