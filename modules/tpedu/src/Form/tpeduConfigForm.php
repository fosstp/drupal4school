<?php

/**
 * @file
 * Contains \Drupal\tpedu\Form\tpeduConfigForm.
 */

namespace Drupal\tpedu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;

class tpeduConfigForm extends ConfirmFormBase {

  public function getFormId() {
    return 'tpedu_settings_form';
  }

  public function getQuestion() {
    return '臺北市教育人員單一身分驗證模組設定';
  }

  public function getCancelUrl() {
    return ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
  }

  protected function getEditableConfigNames() {
    return [
      'tpedu.settings',
    ];
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = \Drupal::config('tpedu.settings');

	  $form['helper'] = array(
      '#type' => 'markup',
      '#markup' => '有關臺北市教育人員單一身分驗證服務，說明如下：<ol><li>該服務網址為 <a href="https://ldap.tp.edu.tw">https://ldap.tp.edu.tw</a></li><li>該服務採用開源模式開發，所有文件可以從 <a href="https://github.com/leejoneshane/tpeduSSO">Github 專案</a>取得。</li><li>請下載填寫「<a href="https://github.com/leejoneshane/tpeduSSO/blob/master/%E8%87%BA%E5%8C%97%E5%B8%82%E6%95%99%E8%82%B2%E5%B1%80%E5%96%AE%E4%B8%80%E8%BA%AB%E5%88%86%E9%A9%97%E8%AD%89%E4%BB%8B%E6%8E%A5%E7%94%B3%E8%AB%8B%E8%A1%A8.pdf">臺北市教育局單一身分驗證介接申請表.pdf</a>，「SSO 認證後重導向 URL」欄位請填寫 https://'.$_SERVER['HTTP_HOST'].'/retrieve。</li><li>申請介接專案請洽聯絡人 電話：1999（外縣市請撥02-27208889）#1234 信箱：edu_ict.19@mail.taipei.gov.tw</li><li>請詳細閱讀<a href="https://github.com/leejoneshane/tpeduSSO/blob/master/%E8%87%BA%E5%8C%97%E5%B8%82%E6%95%99%E8%82%B2%E4%BA%BA%E5%93%A1%E5%96%AE%E4%B8%80%E8%BA%AB%E5%88%86%E9%A9%97%E8%AD%89%E8%B3%87%E6%96%99%E4%BB%8B%E6%8E%A5%E6%89%8B%E5%86%8AV2.0.docx">臺北市教育人員單一身分驗證資料介接手冊V2.0</a>有關代理授權與管理員個人存取金鑰的取得方式，授權範圍務必勾選 profile、school 和 schoolAdmin。</li></ol>',
	  );
    $form['client_id'] = array(
      '#type' => 'textfield',
      '#title' => '介接專案編號',
      '#default_value' => $config->get('client_id'),
      '#description' => '請向臺北市教育局申請臺北市教育人員單一身分驗證介接專案，並將申請通過後所核發之專案編號填寫在這個欄位。',
      '#required' => TRUE,
    );
    $form['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => '介接專案金鑰',
      '#default_value' => $config->get('client_secret'),
      '#description' => '請填寫核發給專案的金鑰，注意：金鑰牽涉到專案的追蹤反查須妥善保存切勿外流，以避免遭到停用！',
      '#required' => TRUE,
    );
    $form['admin_token'] = array(
      '#type' => 'textarea',
      '#rows' => 6,
      '#cols' => 25,
      '#title' => '學校管理員個人存取金鑰',
      '#default_value' => $config->get('admin_token'),
      '#description' => '請依上面說明之方法，填入學校管理員個人存取金鑰',
      '#required' => TRUE,
    );
    $form['refresh_days'] = array(
      '#type' => 'number',
      '#title' => '快取資料庫更新頻率',
      '#default_value' => $config->get('refresh_days'),
      '#description' => '透過 Data API 取得的資料將快取在資料庫中，若該筆資料保存超過更新頻率，則自動重新取得。預設為 30 天！',
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $error = '';
    $config = \Drupal::configFactory()->getEditable('tpedu.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->set('call_back', 'https://'.$_SERVER['HTTP_HOST'].'/retrieve');
    $config->save();

    $user = profile();
    \Drupal::logger('tpedu')->debug('oauth2 profile:'.print_r($user));
    if (!empty($user->o)) {
      if (is_array($user->o)) {
        $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
        $tempstore->set('organization', $user->organization);
        $form_state->setRedirect('tpedu.school_select');
      } else {
        $config->set('enable', true);
        $config->set('api.dc', $user->o);
        $config->save();
      }
    }
  }

}
