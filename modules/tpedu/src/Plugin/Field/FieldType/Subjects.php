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
 *   id = "tpedu_subjects",
 *   label = "科目",
 *   module ="tpedu",
 *   description = "科目選單",
 *   category = "臺北市校園",
 *   default_widget = "subjects_default",
 *   default_formatter = "subjects_default",
 * )
 */
class Subjects extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return [
          'columns' => [
            'value' => [
                'type' => 'varchar_ascii',
                'length' => 50,
                'not null' => true,
            ],
          ],
        ];
    }

    public function isEmpty()
    {
        $value = $this->get('value')->getValue();

        return $value === null || $value === '';
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['value'] = DataDefinition::create('string')->setLabel('科目代號');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_current_user' => false,
            'filter_by_domain' => false,
            'domain' => '',
            'inline_columns' => 5,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];
        $element['extra_info'] = [
            '#markup' => '<p>此欄位可以單獨使用或結合班級、教師欄位使用！請選擇是否使用過濾機制，若使用領域進行過濾，除了可以預先選擇教學領域外，您也可以使用領域欄位進行動態過濾。結合班級欄位時，可用於選取不同科目的授課班級；結合教師欄位時，可用於選取不同科目的任教老師！</p>',
        ];
        $element['filter_by_current_user'] = [
            '#type' => 'checkbox',
            '#title' => '依使用者過濾科目',
            '#description' => '若勾選，僅顯示目前使用者任教的科目。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        ];
        $element['filter_by_domain'] = [
            '#type' => 'checkbox',
            '#title' => '依領域欄位過濾科目（注意：若領域欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定領域的所有科目。',
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
            '#title' => '領域',
            '#description' => '請選擇預設教學領域',
            '#default_value' => $this->getSetting('domain'),
            '#options' => $values,
        ];
        $element['filter_by_class'] = [
            '#type' => 'checkbox',
            '#title' => '依班級欄位過濾科目（注意：若班級欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定班級的所有授課科目。',
            '#default_value' => $this->getSetting('filter_by_class'),
        ];
        $values = ['' => '--'];
        $classes = all_classes();
        if ($classes) {
            foreach ($classes as $c) {
                $values[$c->id] = $c->name;
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
