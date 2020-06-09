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
 *   description = "職務選單",
 *   category = "臺北市教育人員",
 *   default_widget = "roles_default",
 *   default_formatter = "roles_default",
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
        $element['extra_info'] = array(
            '#markup' => '<p>此欄位可以單獨使用或結合教師欄位使用！請選擇是否使用過濾機制，若使用行政單位進行過濾，除了可以預先選擇行政單位外，您也可以使用行政單位欄位進行動態過濾。結合教師欄位時，可用於選取不同職務的行政人員！</p>',
        );
        $element['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾職務',
            '#description' => '若勾選，僅顯示目前使用者擔任的職務。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $element['filter_by_unit'] = array(
            '#type' => 'checkbox',
            '#title' => '依行政單位欄位過濾職務（注意：若行政單位欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定處室的所有職務。',
            '#default_value' => $this->getSetting('filter_by_unit'),
        );
        $values = array('' => '--');
        $units = all_units();
        foreach ($units as $r) {
            $values[$r->id] = $r->name;
        }
        $element['unit'] = array(
            '#type' => 'select',
            '#title' => '行政單位',
            '#description' => '請選擇預設行政單位',
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
