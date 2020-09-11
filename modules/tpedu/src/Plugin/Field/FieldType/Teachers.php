<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_teachers",
 *   label = "教師",
 *   module ="tpedu",
 *   description = "教師選單",
 *   category = "臺北市校園",
 *   default_widget = "teachers_default",
 *   default_formatter = "teachers_default",
 * )
 */
class Teachers extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return [
          'columns' => [
            'uuid' => [
                'type' => 'varchar_ascii',
                'length' => 36,
                'not null' => true,
            ],
          ],
        ];
    }

    public function isEmpty()
    {
        $value = $this->get('uuid')->getValue();

        return $value === null || $value === '';
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['uuid'] = DataDefinition::create('string')->setLabel('人員代號');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_unit' => false,
            'unit' => '',
            'filter_by_role' => false,
            'role' => '',
            'filter_by_domain' => false,
            'domain' => '',
            'filter_by_subject' => false,
            'subject' => '',
            'filter_by_grade' => false,
            'grade' => '',
            'filter_by_class' => false,
            'class' => '',
            'inline_columns' => 10,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];
        $element['extra_info'] = [
            '#markup' => '<p>此欄位可以單獨使用，用於選取全校同仁。也可以選擇多種過濾機制，除了指定預設值外，您也可以配合其它欄位進行動態過濾。</p>',
        ];
        $element['filter_by_unit'] = [
            '#type' => 'checkbox',
            '#title' => '依行政單位欄位過濾教師（注意：若行政單位欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定行政單位的所有行政人員。',
            '#default_value' => $this->getSetting('filter_by_unit'),
        ];
        $values = ['' => '--'];
        $units = all_units();
        if ($units) {
            foreach ($units as $r) {
                $values[$r->id] = $r->name;
            }
        }
        $element['unit'] = [
            '#type' => 'select',
            '#title' => '隸屬行政單位',
            '#description' => '請選擇預設行政單位',
            '#default_value' => $this->getSetting('unit'),
            '#options' => $values,
        ];
        $element['filter_by_role'] = [
            '#type' => 'checkbox',
            '#title' => '依職務欄位過濾教師（注意：若職務欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定職務的所有行政人員。',
            '#default_value' => $this->getSetting('filter_by_role'),
        ];
        $values = ['' => '--'];
        $roles = all_roles();
        if ($roles) {
            foreach ($roles as $r) {
                $values[$r->id] = $r->name;
            }
        }
        $element['role'] = [
            '#type' => 'select',
            '#title' => '擔任職務',
            '#description' => '請選擇預設職務',
            '#default_value' => $this->getSetting('role'),
            '#options' => $values,
        ];
        $element['filter_by_domain'] = [
            '#type' => 'checkbox',
            '#title' => '依領域欄位過濾教師（注意：若領域欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定領域的所有任教老師。',
            '#default_value' => $this->getSetting('filter_by_domain'),
        ];
        $values = ['' => '--'];
        $domains = all_domains();
        if ($domains) {
            foreach ($domains as $r) {
                $values[$r->domain] = $r->domain;
            }
        }
        $element['domain'] = [
            '#type' => 'select',
            '#title' => '所屬領域',
            '#description' => '請選擇預設教學領域',
            '#default_value' => $this->getSetting('domain'),
            '#options' => $values,
        ];
        $element['filter_by_subject'] = [
            '#type' => 'checkbox',
            '#title' => '依配課科目過濾教師',
            '#description' => '若勾選，僅顯示指定科目的所有任教老師。',
            '#default_value' => $this->getSetting('filter_by_subject'),
        ];
        $values = ['' => '--'];
        $subjects = all_subjects();
        if ($subjects) {
            foreach ($subjects as $s) {
                $values[$s->id] = $s->name;
            }
        }
        $element['subject'] = [
            '#type' => 'select',
            '#title' => '任教科目',
            '#description' => '預設的任教科目',
            '#default_value' => $this->getSetting('subject'),
            '#options' => $values,
        ];
        $element['filter_by_grade'] = [
            '#type' => 'checkbox',
            '#title' => '依年級欄位過濾教師（注意：若年級欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定年級的所有導師。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        ];
        $element['grade'] = [
            '#type' => 'textfield',
            '#title' => '預設年級',
            '#description' => '預設要顯示哪些年級的導師？',
            '#default_value' => $this->getSetting('grade'),
        ];
        $element['filter_by_class'] = [
            '#type' => 'checkbox',
            '#title' => '依班級欄位過濾教師（注意：若班級欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定班級的所有導師及科任教師。',
            '#default_value' => $this->getSetting('filter_by_class'),
        ];
        $values = ['' => '--'];
        $classes = all_classes();
        if ($classes) {
            foreach ($classes as $r) {
                $values[$r->id] = $r->name;
            }
        }
        $element['class'] = [
            '#type' => 'select',
            '#title' => '班級',
            '#description' => '請選擇預設班級',
            '#default_value' => $this->getSetting('class'),
            '#options' => $values,
        ];
        $element['inline_columns'] = [
            '#type' => 'number',
            '#title' => '每行顯示數量',
            '#min' => 1,
            '#max' => 12,
            '#description' => '當使用核取框（複選）時，您可以指定每一行要顯示的欄位數量。',
            '#default_value' => $this->getSetting('inline_columns'),
        ];

        return $element;
    }
}
