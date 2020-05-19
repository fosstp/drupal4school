<?php

/**
 * @file
 * Contains \Drupal\tpedu\Form\tpeduConfigForm.
 */

namespace Drupal\tpedu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormHelper;

class tpeduConfigForm extends ConfirmFormBase
{
    public function getFormId()
    {
        return 'gsync_settings_form';
    }

    public function getQuestion()
    {
        return 'G Suite 帳號同步模組設定';
    }

    public function getCancelUrl()
    {
        return ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
    }

    protected function getEditableConfigNames()
    {
        return [
            'gsync.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        global $base_url;
        $config = \Drupal::config('gsync.settings');

        $form['helper'] = array(
            '#type' => 'markup',
            '#markup' => '<p>要使用 G Suite 帳號單一簽入功能，您必須建立 Google 開發專案並取得 Google 發給您的<em>網路應用程式憑證</em>，請依照以下步驟取得相關組態值：'.
            '<ol><li>請連結 <a href="https://console.cloud.google.com/apis/dashboard">Google apis 主控台</a>，如果還沒有介接專案，請先建立專案！</li>'.
            '<li>請進入「憑證」管理頁面，然後建立「服務帳戶」憑證，並且需要從 G Suite 管理主控台進行全域授權，請參考<a href="https://support.google.com/a/answer/162106?hl=zh-Hant">這篇文章</a>。</li>'.
            '<li>線上測試 OAuth 用戶端 API 資料存取，請連到 <a href="https://developers.google.com/oauthplayground/">OAuth playground</a>。</li>'.
            '</ol>',
        );
        $form['google_serivce_json'] = array(
            '#type' => 'file',
            '#title' => 'Google 服務帳號授權驗證 JSON 檔',
            '#default_value' => $config->get('google_service_json'),
            '#description' => '請從 Google apis 主控台專案管理頁面下載上述「服務帳戶」所提供的 JSON 檔案並上傳到這裡。',
        );
        $form['google_domain_admin'] = array(
            '#type' => 'textfield',
            '#title' => 'G Suite 管理員帳號',
            '#default_value' => $config->get('google_domain_admin'),
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
        global $base_url;
        $error = '';
        $config = \Drupal::configFactory()->getEditable('gsync.settings');
        $form_state->cleanValues();
        foreach ($form_state->getValues() as $key => $value) {
            if ($key == 'google_service_json') {
                $file = file_save_upload('google_service_json', array('file_validate_extensions' => array('json')), '/var/www/html/modules/gsync', FILE_EXISTS_REPLACE);
                if ($file) {
                    $file->status = FILE_STATUS_PERMANENT;
                    file_save($file);
                    $config->set($key, $file->filename);
                }
            } else {
                $config->set($key, $value);
            }
        }
        $config->set('google_call_back', 'https://'.$_SERVER['HTTP_HOST'].'/gsync');
        $config->save();
        //test
    }
}
