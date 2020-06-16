<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_students",
 *   label = "學生",
 *   description = "學生選單",
 *   category = "臺北市教育人員",
 *   default_widget = "students_default",
 *   default_formatter = "students_default",
 * )
 */
class Students extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return array(
          'columns' => array(
            'uuid' => array(
                'type' => 'varchar_ascii',
                'length' => 36,
                'not null' => true,
            ),
          ),
        );
    }

    public function isEmpty()
    {
        return empty($this->get('uuid')->getValue());
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['uuid'] = DataDefinition::create('string')->setLabel('人員代號');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_current_user' => true,
            'filter_by_class' => false,
            'class' => '',
            'inline_columns' => 10,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = array();
        $element['extra_info'] = array(
            '#markup' => '<p>此欄位必須選擇使用以下兩種過濾機制的其中一種才會正常運作，若使用班級進行過濾，除了可以指定班級外，您也可以使用班級欄位進行動態過濾。</p>',
        );
        $element['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾學生',
            '#description' => '若勾選，僅顯示目前使用者擔任導師（或就讀）班級的學生。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $element['filter_by_class'] = array(
            '#type' => 'checkbox',
            '#title' => '依班級欄位過濾學生（注意：若班級欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定班級的所有學生。',
            '#default_value' => $this->getSetting('filter_by_class'),
        );
        $values = array('' => '--');
        $classes = all_classes();
        if ($classes) {
            foreach ($classes as $r) {
                $values[$r->id] = $r->name;
            }
        }
        $element['class'] = array(
            '#type' => 'select',
            '#title' => '班級',
            '#description' => '請選擇預設班級',
            '#default_value' => $this->getSetting('class'),
            '#options' => $values,
        );
        $element['inline_columns'] = array(
            '#type' => 'number',
            '#title' => '每行顯示數量',
            '#min' => 1,
            '#max' => 12,
            '#description' => '當使用核取框（複選）時，您可以指定每一行要顯示的欄位數量。',
            '#default_value' => $this->getSetting('inline_columns'),
        );

        return $element;
    }
}
