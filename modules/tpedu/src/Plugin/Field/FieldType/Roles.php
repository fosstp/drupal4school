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
 *   id = "tpedu_roles",
 *   label = "職務",
 *   description = "職務列表",
 *   category = "臺北市教育人員",
 *   default_widget = "roles_default",
 *   default_formatter = "roles_default"
 * )
 */
class Roles extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return array(
          'columns' => array(
            'role_id' => array(
                'type' => 'varchar_ascii',
                'length' => 50,
                'not null' => true,
            ),
          ),
        );
    }

    public function isEmpty()
    {
        return empty($this->get('role_id')->getValue());
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['role_id'] = DataDefinition::create('string')->setLabel('職務代號');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_current_user' => false,
            'filter_by_unit' => false,
            'unit' => '',
            'inline_columns' => 5,
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
        $element['filter_by_unit'] = array(
            '#type' => 'checkbox',
            '#title' => '依行政單位欄位過濾職務',
            '#description' => '若勾選，僅顯示指定處室的所有職務。',
            '#default_value' => $this->getSetting('filter_by_unit'),
        );
        $values = array('' => '--');
        $roles = all_roles();
        foreach ($roles as $r) {
            $values[$r->id] = $r->name;
        }
        $element['unit'] = array(
            '#type' => 'select',
            '#title' => '行政單位',
            '#description' => '請選擇行政單位',
            '#default_value' => $this->getSetting('unit'),
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
