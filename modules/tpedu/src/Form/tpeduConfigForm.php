<?php

namespace Drupal\tpedu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TpeduConfigForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'tpedu_settings_form';
    }

    protected function getEditableConfigNames()
    {
        return [
            'tpedu.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        global $base_url;
        $config = $this->config('tpedu.settings');

        $form['helper'] = array(
            '#type' => 'markup',
            '#markup' => '<p>有關臺北市校園單一身分驗證服務，說明如下：<ol>'.
                '<li>該服務網址為 <a href="https://ldap.tp.edu.tw">https://ldap.tp.edu.tw</a></li>'.
                '<li>該服務採用開源模式開發，所有文件可以從 <a href="https://github.com/leejoneshane/tpeduSSO">Github 專案</a>取得。</li>'.
                '<li>請從右方連結<a href="https://ldap.tp.edu.tw/3party">線上申請介接專案</a>，申請表單中之「SSO 認證後重導向 URL」欄位請填寫 https://'.$_SERVER['HTTP_HOST'].'/retrieve。</li>'.
                '<li>介接專案相關問題，請洽聯絡人 電話：1999（外縣市請撥02-27208889）#1234 信箱：edu_ict.19@mail.taipei.gov.tw</li>'.
                '<li>請以學校管理員身分登入上述網站，並從「學校管理」介面左側之「新增授權金鑰」取得全校授權金鑰，授權範圍請務必勾選 profile、school 和 schoolAdmin。</li>'.
                '</ol></p>',
        );
        $form['client_id'] = array(
            '#type' => 'textfield',
            '#title' => '介接專案編號',
            '#default_value' => $config->get('client_id'),
            '#description' => '請向臺北市教育局申請臺北市校園單一身分驗證介接專案，並將申請通過後所核發之專案編號填寫在這個欄位。',
            '#required' => true,
        );
        $form['client_secret'] = array(
            '#type' => 'textfield',
            '#title' => '介接專案金鑰',
            '#default_value' => $config->get('client_secret'),
            '#description' => '請填寫核發給專案的金鑰，注意：金鑰牽涉到專案的追蹤反查須妥善保存切勿外流，以避免遭到停用！',
            '#required' => true,
        );
        $form['admin_token'] = array(
            '#type' => 'textarea',
            '#rows' => 6,
            '#cols' => 25,
            '#title' => '全校授權金鑰',
            '#default_value' => $config->get('admin_token'),
            '#description' => '請依上面說明之方法，填入全校授權金鑰',
            '#required' => true,
        );
        $form['refresh_days'] = array(
            '#type' => 'number',
            '#title' => '快取資料庫更新頻率',
            '#default_value' => $config->get('refresh_days'),
            '#description' => '透過 Data API 取得的資料將快取在資料庫中，若該筆資料保存超過更新頻率，則自動重新取得。預設為 30 天！',
        );
        $form['display_unit'] = array(
            '#type' => 'checkbox',
            '#title' => '顯示部門名稱',
            '#default_value' => $config->get('display_unit'),
            '#description' => '預設僅顯示使用者真實姓名，當開啟此功能時，教師會顯示部門名稱，學生會顯示班級名稱。預設為不顯示！',
        );
        $form['display_title'] = array(
            '#type' => 'checkbox',
            '#title' => '顯示職稱',
            '#default_value' => $config->get('display_title'),
            '#description' => '預設僅顯示使用者真實姓名，當開啟此功能時，教師會顯示職稱，學生會顯示班級名稱。預設為不顯示！',
        );
        $form['allow_default_login'] = array(
            '#type' => 'checkbox',
            '#title' => '允許使用本地端帳號登入',
            '#default_value' => $config->get('allow_default_login'),
            '#description' => '預設系統管理員帳號為本地端帳號，如果您已經將管理權限授予給單一身分驗證帳號，則可以將此功能關閉。預設為允許！',
        );
        $form['login_goto_url'] = array(
            '#type' => 'textfield',
            '#title' => '登入後跳轉網址（可不填）',
            '#default_value' => $config->get('login_goto_url'),
            '#description' => '請輸入想讓使用者登入時第一個看到的頁面，例如：node/news',
        );
        $form['logout_goto_url'] = array(
            '#type' => 'textfield',
            '#title' => '登出後跳轉網址（可不填）',
            '#default_value' => $config->get('logout_goto_url'),
            '#description' => '請輸入希望使用者登出後連結到哪個頁面，如果想要一併登出單一身分驗證服務，可設定為：https://ldap.tp.edu.tw/api/v2/logout?redirect=本站登入網址',
        );
        $form['personal_data_notice'] = array(
            '#type' => 'checkbox',
            '#title' => '個資政策連結',
            '#default_value' => $config->get('personal_data_notice'),
            '#description' => '是否要在登入區塊顯示個資政策連結',
        );
        $form['actions'] = array(
            '#type' => 'actions',
            'submit' => array(
                '#type' => 'submit',
                '#value' => '儲存組態',
            ),
        );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('tpedu.settings');
        $values = $form_state->cleanValues()->getValues();
        foreach ($values as $key => $value) {
            $config->set($key, $value);
        }
        $config->set('call_back', 'https://'.$_SERVER['HTTP_HOST'].'/retrieve');
        $config->save();

        $user = profile();
        if (!empty($user->o)) {
            if (is_array($user->o)) {
                $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
                $tempstore->set('organization', $user->organization);
                $form_state->setRedirect('tpedu.school_select');
            } else {
                $config->set('enable', true);
                $config->set('api.dc', $user->o);
                $config->save();
                fetch_units();
                fetch_roles();
                fetch_subjects();
                fetch_classes();
            }
        }
    }
}
