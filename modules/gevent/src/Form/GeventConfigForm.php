<?php

namespace Drupal\gevent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

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
        $enable = \Drupal::config('gsync.settings')->get('enabled');
        if (!$enable) {
            $form['helper'] = [
                '#type' => 'markup',
                '#markup' => '<p>尚未完成 G Suite 帳號同步模組設定，因此無法存取 Google Api 服務，請先完成 G Suite 帳號同步模組設定！</p>',
            ];
        } else {
            $config = $this->config('gevent.settings');
            $options = ['none' => '-請選擇-'];
            $boundles = NodeType::loadMultiple();
            foreach (keys($boundles) as $node_type) {
                $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node_type);
                $bundles[$node_type]['field_defintions'] = $fields;
                foreach ($fields as $field_name => $field_definition) {
                    if ($field_definition->getType() == 'datetime') {
                        $options[$node_type] = $all_types[$node_type]->name;
                        continue;
                    }
                }
            }
            $form['content_type'] = [
                '#type' => 'select',
                '#title' => '要把哪種內容類型同步到 Google 行事曆',
                '#options' => $options,
                '#default_value' => $config->get('content_type'),
                '#description' => '日期時間欄位為行事曆事件的必要欄位，因此這裡僅列出含有日期時間欄位的內容類型',
                '#required' => true,
                '#ajax' => [
                    'callback' => [$this, 'reload_node_fields_ajax_callback'],
                ],
            ];

            $my_bundle = $config->get('content_type');
            $my_fields = ['none' => '-請選擇-'];
            $teacher_field = ['none' => '-請選擇-'];
            if (!empty($my_bundle)) {
                foreach ($bundles[$my_bundle]['field_defintions'] as $field_name => $field_defintion) {
                    $type = $field_defintion->getType();
                    if ($type == 'entity_reference') {
                        $target_type = $field_defintion->getSetting('target_type');
                        if ($target_type == 'taxonomy_term') {
                            $config->set('field_taxonomy', $field_name);
                            $vocabularys = $field_defintion->getSetting('handler_settings')['target_bundles'];
                            $my_terms = [];
                            foreach ($vocabularys as $v) {
                                $terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree($v);
                                foreach ($terms as $t) {
                                    $my_terms[] = $t->getName();
                                }
                            }
                        }
                    } elseif ($type == 'datetime_range') {
                        $config->set('field_date', $field_name);
                    } elseif ($type == 'tpedu_teachers') {
                        $teacher_field[$field_name] = $field_defintion->getLabel();
                    } elseif ($type == 'string' || $type == 'string_long') {
                        $my_fields[$field_name] = $field_defintion->getLabel();
                    }
                }
            }

            $form['field_title'] = [
                '#type' => 'select',
                '#title' => '行事曆事件標題對應欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_title'),
                '#description' => '這裡僅列出類型為字串或長字串的欄位',
                '#required' => true,
            ];
            $form['field_department'] = [
                '#type' => 'select',
                '#title' => '行事曆事件主辦單位對應欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_department'),
                '#description' => '這裡僅列出類型為字串或長字串的欄位',
                '#required' => true,
            ];
            $form['field_memo'] = [
                '#type' => 'select',
                '#title' => '行事曆事件說明對應欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_memo'),
                '#description' => '這裡僅列出類型為字串或長字串的欄位',
            ];
            $form['field_place'] = [
                '#type' => 'select',
                '#title' => '行事曆事件位置對應欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_place'),
                '#description' => '這裡僅列出類型為字串或長字串的欄位',
            ];
            $form['field_attendee'] = [
                '#type' => 'select',
                '#title' => '行事曆事件邀請對象對應欄位',
                '#options' => $teacher_field,
                '#default_value' => $config->get('field_attendee'),
                '#description' => '這裡僅列出類型為（台北市校園）教師的欄位',
            ];
            $form['field_calendar_id'] = [
                '#type' => 'select',
                '#title' => '用來儲存 Google 行事曆代號的欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_calendar_id'),
                '#description' => '此欄位類型必須為字串或長字串，僅用於同步時檢索行事曆，請勿顯示於輸入表單中讓使用者編輯！',
                '#required' => true,
            ];
            $form['field_event_id'] = [
                '#type' => 'select',
                '#title' => '用來儲存 Google 行事曆事件代號的欄位',
                '#options' => $my_fields,
                '#default_value' => $config->get('field_event_id'),
                '#description' => '此欄位類型必須為字串或長字串，僅用於同步時檢索事件，請勿顯示於輸入表單中讓使用者編輯！',
                '#required' => true,
            ];
            $form['calendar_owner'] = [
                '#type' => 'textfield',
                '#title' => '行事曆擁有者',
                '#default_value' => $config->get('calendar_owner'),
                '#description' => '請輸入用來儲存學校行事曆的專屬帳號，應包含 G Suite 網域，例如：schedule@xxps.tp.edu.tw',
                '#required' => true,
            ];
            if ($config->get('enabled')) {
                $my_calendars = [];
                $calendar = initGoogleCalendar();
                $calendars = gs_listCalendars();
                foreach ($calendars->getItems() as $calendarListEntry) {
                    $my_calendars[$calendarListEntry->getId()] = $calendarListEntry->getSummary();
                }
                $form['calendar_taxonomy'] = [
                    '#type' => 'checkbox',
                    '#title' => '行事曆事件分類',
                    '#default_value' => $config->get('calendar_taxonomy'),
                    '#description' => '是否要利用分類（Taxonomy）對行事曆事件進行區分，以便對應到不同的行事曆。',
                ];
                $form['calendar_id'] = [
                    '#type' => 'select',
                    '#title' => '要同步到哪一個行事曆？',
                    '#options' => $my_calendars,
                    '#default_value' => $config->get('calendar_id'),
                    '#states' => [
                        'invisible' => [
                            ':input[name="calendar_taxonomy"]' => ['checked' => true],
                        ],
                    ],
                ];
                if (count($my_terms) > 0) {
                    foreach ($my_terms as $term) {
                        $form['calendar_term_'.$term] = [
                            '#type' => 'select',
                            '#title' => "要將類別 $term 同步到哪一個行事曆？",
                            '#options' => $my_calendars,
                            '#default_value' => $config->get('calendar_term_'.$term) ?: '',
                            '#states' => [
                                'invisible' => [
                                    ':input[name="calendar_taxonomy"]' => ['checked' => false],
                                ],
                            ],
                        ];
                    }
                }
            } else {
                $form['helper'] = [
                    '#type' => 'markup',
                    '#markup' => '<p>由於尚未取得 Google 行事曆清單，因此無法進行事件分類設定，請先按「儲存組態」然後繼續設定！</p>',
                ];
            }
            $form['actions'] = [
                '#type' => 'actions',
                'submit' => [
                    '#type' => 'submit',
                    '#value' => '儲存組態',
                ],
            ];
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $error = '';
        $message = '';
        $config = $this->config('gevent.settings');
        $values = $form_state->cleanValues()->getValues();
        foreach ($values as $key => $value) {
            $config->set($key, $value);
        }
        $config->save();
        $ok = false;
        $calendar = initGoogleCalendar();
        if ($calendar && gs_listCalendars()) {
            $ok = true;
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

    public function reload_node_fields_ajax_callback($form, $form_state)
    {
        $config = $this->config('gevent.settings');
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $my_bundle = $element['#value'];
        $my_fields = ['none' => '-請選擇-'];
        $teacher_field = ['none' => '-請選擇-'];
        $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $my_bundle);
        foreach ($fields as $field_name => $field_defintion) {
            $type = $field_defintion->getType();
            if ($type == 'entity_reference') {
                $target_type = $field_defintion->getSetting('target_type');
                if ($target_type == 'taxonomy_term') {
                    $config->set('field_taxonomy', $field_name);
                }
            } elseif ($type == 'datetime') {
                $config->set('field_date', $field_name);
            } elseif ($type == 'tpedu_teachers') {
                $teacher_field[$field_name] = $field_defintion->getLabel();
            } elseif ($type == 'string' || $type == 'string_long') {
                $my_fields[$field_name] = $field_defintion->getLabel();
            }
        }
        $form['field_title'] = [
            '#type' => 'select',
            '#title' => '行事曆事件標題對應欄位',
            '#options' => $my_fields,
            '#default_value' => $config->get('field_title'),
            '#description' => '這裡僅列出類型為字串或長字串的欄位',
            '#required' => true,
        ];
        $form['field_memo'] = [
            '#type' => 'select',
            '#title' => '行事曆事件說明對應欄位',
            '#options' => $my_fields,
            '#default_value' => $config->get('field_memo'),
            '#description' => '這裡僅列出類型為字串或長字串的欄位',
        ];
        $form['field_place'] = [
            '#type' => 'select',
            '#title' => '行事曆事件位置對應欄位',
            '#options' => $my_fields,
            '#default_value' => $config->get('field_place'),
            '#description' => '這裡僅列出類型為字串或長字串的欄位',
        ];
        $form['field_attendee'] = [
            '#type' => 'select',
            '#title' => '行事曆事件邀請對象對應欄位',
            '#options' => $teacher_field,
            '#default_value' => $config->get('field_attendee'),
            '#description' => '這裡僅列出類型為（台北市校園）教師的欄位',
        ];
        $form['field_calendar_id'] = [
            '#type' => 'select',
            '#title' => '用來儲存 Google 行事曆代號的欄位',
            '#options' => $my_fields,
            '#default_value' => $config->get('field_calendar_id'),
            '#description' => '此欄位類型必須為字串或長字串，僅用於同步時檢索行事曆，請勿顯示於輸入表單中讓使用者編輯！',
            '#required' => true,
        ];
        $form['field_event_id'] = [
            '#type' => 'select',
            '#title' => '用來儲存 Google 行事曆事件代號的欄位',
            '#options' => $my_fields,
            '#default_value' => $config->get('field_event_id'),
            '#description' => '此欄位類型必須為字串或長字串，僅用於同步時檢索事件，請勿顯示於輸入表單中讓使用者編輯！',
            '#required' => true,
        ];
        $response->addCommand(new ReplaceCommand('name="field_title"', \Drupal::service('renderer')->render($form['field_title'])));
        $response->addCommand(new ReplaceCommand('name="field_memo"', \Drupal::service('renderer')->render($form['field_memo'])));
        $response->addCommand(new ReplaceCommand('name="field_place"', \Drupal::service('renderer')->render($form['field_place'])));
        $response->addCommand(new ReplaceCommand('name="field_attendee"', \Drupal::service('renderer')->render($form['field_attendee'])));
        $response->addCommand(new ReplaceCommand('name="field_calendar_id"', \Drupal::service('renderer')->render($form['field_calendar_id'])));
        $response->addCommand(new ReplaceCommand('name="field_event_id"', \Drupal::service('renderer')->render($form['field_event_id'])));

        return $response;
    }
}
