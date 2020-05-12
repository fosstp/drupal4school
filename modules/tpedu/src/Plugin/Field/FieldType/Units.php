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
 *   id = "tpedu_units",
 *   label = "行政單位",
 *   description = "學校組織部門選單",
 *   category = "臺北市教育人員",
 *   default_widget = "units_default",
 *   default_formatter = "units_default"
 * )
 */
class Units extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return array(
          'columns' => array(
            'dept_id' => array(
               'type' => 'varchar_ascii',
               'length' => 50,
               'not null' => false,
            ),
          ),
        );
    }

    public function isEmpty()
    {
        return empty($this->get('dept_id')->value);
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
        $element = array();
        $element['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾職務',
            '#description' => '若勾選，僅顯示目前使用者擔任的職務。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );

        return $element;
    }
}
