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
 *   id = "tpedu_units",
 *   label = "行政單位",
 *   module ="tpedu",
 *   description = "學校組織部門選單",
 *   category = "臺北市校園",
 *   default_widget = "units_default",
 *   default_formatter = "units_default",
 * )
 */
class Units extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return [
          'columns' => [
            'dept_id' => [
               'type' => 'varchar_ascii',
               'length' => 50,
               'not null' => false,
            ],
          ],
        ];
    }

    public function isEmpty()
    {
        $value = $this->get('dept_id')->getValue();

        return $value === null || $value === '';
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['dept_id'] = DataDefinition::create('string')->setLabel('行政單位');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_current_user' => false,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];
        $element['extra_info'] = [
            '#markup' => '<p>此欄位可以單獨使用或結合職務、教師欄位使用，請選擇是否使用過濾機制。結合職務欄位時，可用於選取不同行政單位的職務；結合教師欄位時，可用於選取不同行政單位的教師！</p>',
        ];
        $element['filter_by_current_user'] = [
            '#type' => 'checkbox',
            '#title' => '依使用者過濾行政單位',
            '#description' => '若勾選，僅顯示登入使用者隸屬的行政單位。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        ];

        return $element;
    }
}
