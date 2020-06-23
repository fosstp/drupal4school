<?php

namespace Drupal\adsync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class adsyncConfigForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'adsync_settings_form';
    }

    protected function getEditableConfigNames()
    {
        return [
            'adsync.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('adsync.settings');
        $form['helper'] = array(
            '#type' => 'markup',
            '#markup' => '<p>微軟網域主控站僅允許透過 LDAPS 通訊協定來變更密碼或增刪帳號，但預設不會開啟此項功能。'.
            '要開啟此項功能，您必須在網域主控站上安裝憑證伺服器，並將該憑證伺服器於安裝階段設定為<em>企業</em>憑證，而非<em>獨立</em>伺服器，'.
            '安裝完成後，您必須將網域主控站的根憑證檔案匯出後，透過底下表單上傳到 Drupal 網站中。</p>',
        );
        $form['ad_server'] = array(
            '#type' => 'textfield',
            '#title' => '網域主控站',
            '#default_value' => $config->get('ad_server'),
            '#description' => '請輸入微軟網域主控站的 DNS 名稱，不建議使用 IP。',
        );
        $validators = array(
            'file_validate_extensions' => array('cer'),
        );
        $form['ca_cert'] = array(
            '#type' => 'file',
            '#title' => '根憑證檔案',
            '#description' => $config->get('ca_cert') ? '根憑證檔案已經上傳，如沒有要變更金鑰，請勿再上傳！' : '請從網域主控站將根憑證檔案匯出後，上傳到這裡。',
        );
        $form['ad_admin'] = array(
            '#type' => 'textfield',
            '#title' => '網域管理員帳號',
            '#default_value' => $config->get('ad_admin') ?: 'administrator',
            '#description' => '請輸入網域管理員帳號，該管理員必須具備 Windows 網域最高管理權限。',
        );
        $form['ad_password'] = array(
            '#type' => 'textfield',
            '#title' => '網域管理員密碼',
            '#default_value' => $config->get('ad_password'),
            '#description' => '請輸入網域管理員密碼。',
        );
        $form['users_dn'] = array(
            '#type' => 'textfield',
            '#title' => '使用者命名空間',
            '#default_value' => $config->get('users_dn'),
            '#description' => '請輸入儲存使用者的命名空間，如果貴校未使用組織（OU）的話，預設是 CN=Users,DC=貴校的網域 DN，例如：CN=Users,DC=xxps,DC=tp,DC=edu,DC=tw。',
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
        $error = '';
        $message = '';
        $config = $this->config('adsync.settings');
        $values = $form_state->cleanValues()->getValues();
        foreach ($values as $key => $value) {
            if ($key == 'ca_cert') {
                $file = file_save_upload('ca_cert', array('file_validate_extensions' => array('cer')), 'public://adsync', 0, FILE_EXISTS_REPLACE);
                if ($file) {
                    $file->setPermanent();
                    $file->save();
                    $config->set($key, $file->getFileUri());
                    $message = '網域主控站的根憑證檔案已經更新。';
                }
            } else {
                $config->set($key, $value);
            }
        }
        $config->save();
        $ok = false;
        if ($config->get('ca_cert') && $config->get('ad_admin') && $config->get('ad_password')) {
            $result = ad_test();
            if ($result == 0) {
                $ok = true;
            }
        }
        if ($ok) {
            $config->set('enabled', true);
            $config->save();
            $message .= '所有設定已經完成並通過 AD 連線測試，模組已經啟用。';
            \Drupal::messenger()->addMessage($message, 'status');
        } else {
            $config->set('enabled', false);
            $config->save();
            switch ($result) {
                case 1:
                    $message .= '連線網域主控站失敗。請檢查伺服器 DNS 名稱或 IP 是否正確！';
                    break;
                case 2:
                    $message .= '已經連線到網域主控站，但是無法成功登入。請檢查管理員帳號密碼是否正確！';
                    break;
                case 3:
                    $message .= '無法使用 LDAPS 通訊協定連接網域主控站，請在網域主控站上安裝企業級憑證服務，以便提供 LDAPS 連線功能。';
                    break;
            }
            \Drupal::messenger()->addMessage($message, 'warning');
        }
    }
}
