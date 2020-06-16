<?php

namespace Drupal\tpedu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class tpeduSchoolSelectForm extends ConfigFormBase
{
    public function getFormId()
    {
        return 'tpedu_school_select_form';
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
        $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
        $form['helper'] = array(
            '#type' => 'markup',
            '#markup' => '本模組僅能管理單一學校或機構，如果您具備多重機構管理員身分，請從下面選單選取您要管理的機構。如果您要切換不同機構，請先移除重裝模組，然後再變更設定。',
        );
        if ($tempstore->get('organization')) {
            $form['dc'] = array(
                '#type' => 'select',
                '#title' => '請選擇要管理的學校或機構：',
                '#options' => $tempstore->get('organization'),
                '#required' => true,
            );
        }
        $form['actions'] = array(
            '#type' => 'actions',
            'submit' => array(
                '#type' => 'submit',
                '#value' => '選好了！',
            ),
        );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        global $base_url;
        $error = '';
        $config = $this->config('tpedu.settings');
        $form_state->cleanValues();
        $dc = $form_state->getValue('dc');
        if ($dc) {
            $config->set('api.dc', $dc);
            $config->set('enable', true);
            $config->save();
            fetch_units();
            fetch_roles();
            fetch_subjects();
            fetch_classes();
        }
        $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
        $tempstore->delete('organization');
        $form_state->setRedirect('tpedu.config');
    }
}
