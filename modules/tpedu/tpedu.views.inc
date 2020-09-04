<?php

use Drupal\field\FieldStorageConfigInterface;

function tpedu_views_data()
{
    $data = [];
    $config = \Drupal::config('tpedu.settings');
    if ($config->get('enable')) {
        $data['tpedu_people'] = [];
        $data['tpedu_people']['table'] = [];
        $data['tpedu_people']['table']['group'] = 'tpedu';
        $data['tpedu_people']['table']['provider'] = 'tpedu';
        $data['tpedu_people']['table']['base']['field'] = 'uuid';
        $data['tpedu_people']['table']['base']['title'] = '臺北市校園';
        $data['tpedu_people']['table']['base']['help'] = '來自 ldap.tp.edu.tw 的人員資料';
        $data['tpedu_people']['table']['join']['users'] = [
            'left_field' => 'uuid',
            'field' => 'uuid',
        ];
        $data['tpedu_people']['uuid'] = [
            'group' => '使用者',
            'title' => 'UUID',
            'help' => '教育人員的唯一編號對應到系統使用者帳號，若該教育人員未登入過系統，則對應到空值',
            'relationship' => [
                'base' => 'users',
                'base_field' => 'uuid',
                'id' => 'standard',
                'label' => '教育人員對應的使用者帳號',
            ],
        ];
        $data['tpedu_people']['idno'] = [
            'group' => '使用者',
            'title' => '身分證字號',
            'help' => '教育人員的身分證字號',
            'field' => [
                'id' => 'standard',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['id'] = [
            'group' => '使用者',
            'title' => '系統代號',
            'help' => '校務行政系統的系統代號（教師編號或學號）',
            'field' => [
                'id' => 'numeric',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'numeric',
            ],
            'argument' => [
                'id' => 'numeric',
            ],
        ];
        $data['tpedu_people']['student'] = [
            'group' => '使用者',
            'title' => '學生',
            'help' => '教育人員是否為學生',
            'field' => [
                'id' => 'boolean',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'boolean',
                'label' => 'is student',
                'type' => 'yser-no',
                'use_equal' => true,
            ],
        ];
        $data['tpedu_people']['dept_name'] = [
            'group' => '使用者',
            'title' => '行政部門',
            'help' => '教職員所屬部門或學生就讀班級',
            'field' => [
                'id' => 'standard',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['role_name'] = [
            'group' => '使用者',
            'title' => '職稱',
            'help' => '教職員的主要職稱',
            'field' => [
                'id' => 'standard',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['birthdate'] = [
            'group' => '使用者',
            'title' => '出生日期',
            'help' => '教育人員的出生日期',
            'field' => [
                'id' => 'date',
            ],
            'sort' => [
                'id' => 'date',
            ],
            'filter' => [
                'id' => 'date',
            ],
        ];
        $data['tpedu_people']['gender'] = [
            'group' => '使用者',
            'title' => '性別',
            'help' => '教育人員的性別，0為未填、1為男生、2為女生、9為其它',
            'field' => [
                'id' => 'numeric',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'numeric',
            ],
            'argument' => [
                'id' => 'numeric',
            ],
        ];
        $data['tpedu_people']['class'] = [
            'group' => '使用者',
            'title' => '班級',
            'help' => '導師任教班級或學生就讀班級',
            'field' => [
                'id' => 'numeric',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'numeric',
            ],
            'argument' => [
                'id' => 'numeric',
            ],
        ];
        $data['tpedu_people']['seat'] = [
            'group' => '使用者',
            'title' => '座號',
            'help' => '學生的座號',
            'field' => [
                'id' => 'numeric',
                'click sortable' => true,
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'numeric',
            ],
            'argument' => [
                'id' => 'numeric',
            ],
        ];
        $data['tpedu_people']['character'] = [
            'group' => '使用者',
            'title' => '特殊身分',
            'help' => '教育人員的特殊身分註記',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['mobile'] = [
            'group' => '使用者',
            'title' => '行動電話',
            'help' => '教育人員的手機號碼',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['telephone'] = [
            'group' => '使用者',
            'title' => '有線電話',
            'help' => '教育人員的市話號碼',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['address'] = [
            'group' => '使用者',
            'title' => '郵寄地址',
            'help' => '教育人員的實體信件聯絡地址',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['email'] = [
            'group' => '使用者',
            'title' => '電子郵件',
            'help' => '教育人員的電子郵件信箱',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
        $data['tpedu_people']['www'] = [
            'group' => '使用者',
            'title' => '個人首頁',
            'help' => '教育人員的個人首頁網址',
            'field' => [
                'id' => 'standard',
            ],
            'sort' => [
                'id' => 'standard',
            ],
            'filter' => [
                'id' => 'string',
            ],
            'argument' => [
                'id' => 'string',
            ],
        ];
    }

    return $data;
}

function tpedu_views_data_alter(&$data)
{
    $config = \Drupal::config('tpedu.settings');
    if ($config->get('enable')) {
        $data['users']['tpedu_people']['relationship'] = [
            'title' => '臺北市校園',
            'help' => '來自 ldap.tp.edu.tw 的人員資料',
            'base' => 'tpedu_people',
            'base field' => 'uuid',
            'relationship field' => 'uuid',
            'id' => 'standard',
            'label' => '臺北市校園',
        ];
    }
}

function tpedu_field_views_data(FieldStorageConfigInterface $field)
{
    $data = [];
    $fields = ['tpedu_grade', 'tpedu_units', 'tpedu_roles', 'tpedu_domain', 'tpedu_subjects', 'tpedu_classes'];
    $type = $field->getType();
    if (in_array($type, $fields)) {
        $data = views_field_default_views_data($field);
        foreach ($data as $table_name => $table_data) {
            foreach ($table_data as $field_name => $field_data) {
                if (isset($field_data['filter']) && $field_name != 'delta') {
                    $data[$table_name][$field_name]['filter']['id'] = 'tpedu_field';
                }
            }
        }
    }

    return $data;
}
