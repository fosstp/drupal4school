<?php

use Drupal\field\FieldStorageConfigInterface;

function tpedu_views_data()
{
    $config = \Drupal::config('tpedu.settings');
    if ($config->get('enable')) {
        $data = array();
        $data['tpedu_people'] = array();
        $data['tpedu_people']['table'] = array();
        $data['tpedu_people']['table']['provider'] = 'tpedu';
        $data['tpedu_people']['table']['base'] = 'tpedu_people';
        $data['tpedu_people']['table']['base']['title'] = '臺北市校園';
        $data['tpedu_people']['table']['base']['help'] = '來自 ldap.tp.edu.tw 的人員資料';
        $data['tpedu_people']['table']['join']['users'] = array(
            'left_field' => 'uuid',
            'field' => 'uuid',
        );
        $data['tpedu_people']['idno'] = array(
            'group' => '使用者',
            'title' => '身分證字號',
            'help' => '教育人員的身分證字號',
            'field' => array(
                'id' => 'standard',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['id'] = array(
            'group' => '使用者',
            'title' => '系統代號',
            'help' => '校務行政系統的系統代號（教師編號或學號）',
            'field' => array(
                'id' => 'numeric',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'numeric',
            ),
            'argument' => array(
                'id' => 'numeric',
            ),
        );
        $data['tpedu_people']['student'] = array(
            'group' => '使用者',
            'title' => '學生',
            'help' => '教育人員是否為學生',
            'field' => array(
                'id' => 'boolean',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'boolean',
                'label' => 'is student',
                'type' => 'yser-no',
                'use_equal' => true,
            ),
        );
        $data['tpedu_people']['dept_name'] = array(
            'group' => '使用者',
            'title' => '行政部門',
            'help' => '教職員所屬部門或學生就讀班級',
            'field' => array(
                'id' => 'standard',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['role_name'] = array(
            'group' => '使用者',
            'title' => '職稱',
            'help' => '教職員的主要職稱',
            'field' => array(
                'id' => 'standard',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['birthdate'] = array(
            'group' => '使用者',
            'title' => '出生日期',
            'help' => '教育人員的出生日期',
            'field' => array(
                'id' => 'date',
            ),
            'sort' => array(
                'id' => 'date',
            ),
            'filter' => array(
                'id' => 'date',
            ),
        );
        $data['tpedu_people']['gender'] = array(
            'group' => '使用者',
            'title' => '性別',
            'help' => '教育人員的性別，0為未填、1為男生、2為女生、9為其它',
            'field' => array(
                'id' => 'numeric',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'numeric',
            ),
            'argument' => array(
                'id' => 'numeric',
            ),
        );
        $data['tpedu_people']['class'] = array(
            'group' => '使用者',
            'title' => '班級',
            'help' => '導師任教班級或學生就讀班級',
            'field' => array(
                'id' => 'numeric',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'numeric',
            ),
            'argument' => array(
                'id' => 'numeric',
            ),
        );
        $data['tpedu_people']['seat'] = array(
            'group' => '使用者',
            'title' => '座號',
            'help' => '學生的座號',
            'field' => array(
                'id' => 'numeric',
                'click sortable' => true,
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'numeric',
            ),
            'argument' => array(
                'id' => 'numeric',
            ),
        );
        $data['tpedu_people']['character'] = array(
            'group' => '使用者',
            'title' => '特殊身分',
            'help' => '教育人員的特殊身分註記',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['mobile'] = array(
            'group' => '使用者',
            'title' => '行動電話',
            'help' => '教育人員的手機號碼',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['telephone'] = array(
            'group' => '使用者',
            'title' => '有線電話',
            'help' => '教育人員的市話號碼',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['address'] = array(
            'group' => '使用者',
            'title' => '郵寄地址',
            'help' => '教育人員的實體信件聯絡地址',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['email'] = array(
            'group' => '使用者',
            'title' => '電子郵件',
            'help' => '教育人員的電子郵件信箱',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );
        $data['tpedu_people']['www'] = array(
            'group' => '使用者',
            'title' => '個人首頁',
            'help' => '教育人員的個人首頁網址',
            'field' => array(
                'id' => 'standard',
            ),
            'sort' => array(
                'id' => 'standard',
            ),
            'filter' => array(
                'id' => 'string',
            ),
            'argument' => array(
                'id' => 'string',
            ),
        );

        return $data;
    }
}

function tpedu_views_data_alter(&$data)
{
    $config = \Drupal::config('tpedu.settings');
    if ($config->get('enable')) {
        $data['users']['tpedu_people']['relationship'] = array(
            'title' => '臺北市校園',
            'help' => '來自 ldap.tp.edu.tw 的人員資料',
            'base' => 'tpedu_people',
            'base field' => 'uuid',
            'relationship field' => 'uuid',
            'id' => 'standard',
            'label' => '臺北市校園',
        );
    }
}

function tpedu_field_views_data(FieldStorageConfigInterface $field)
{
    $fields = array('tpedu_grade', 'tpedu_units', 'tpedu_roles', 'tpedu_domain', 'tpedu_subjects', 'tpedu_classes');
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
