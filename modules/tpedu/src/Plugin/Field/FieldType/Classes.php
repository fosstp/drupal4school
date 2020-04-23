<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_classes",
 *   label = "班級",
 *   description = "班級列表",
 *   category = "臺北市教育人員",
 *   default_widget = "classes_default",
 *   default_formatter = "classes_default"
 * )
 */
class Classes extends FieldItemBase {

    public static function schema(FieldStorageDefinitionInterface $field) {
        return array(
          'columns' => array(
            'class_id' => array(
                'type' => 'varchar_ascii',
                'length' => 50,
                'not null' => true,
            ),
          ),
        );
    }

    public function isEmpty() {
        return empty($this->get('class_id')->getValue());
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['class_id'] = DataDefinition::create('string')->setLabel('班級代號');
        return $properties;
    }

    public static function defaultFieldSettings() {
        return [
            'filter_by_current_user' => false,
            'filter_by_grade' => false,
            'grade' => '',
            'filter_by_subject' => false,
            'subject' => '',
            'inline_columns' => 10,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
        $element = array();
        $element['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾班級',
            '#description' => '若勾選，僅顯示目前使用者的任教班級。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $element['filter_by_grade'] = array(
            '#type' => 'checkbox',
            '#title' => '依年級欄位過濾班級',
            '#description' => '若勾選，僅顯示指定年級的所有班級。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        );
        $element['grade'] = array(
            '#type' => 'textfield',
            '#title' => '年級(初始值)',
            '#description' => '要顯示哪些年級的班級？請使用 , 區隔不同年級。',
            '#default_value' => $this->getSetting('grade'),
        );
        $element['filter_by_subject'] = array(
            '#type' => 'checkbox',
            '#title' => '依配課科目過濾班級',
            '#description' => '若勾選，僅顯示指定科目的所有已配課班級。',
            '#default_value' => $this->getSetting('filter_by_subject'),
        );
        $values = array( '' => '--' );
        $subjects = all_subjects();
        foreach ($subjects as $s) {
            $values[$s->id] = $s->name;
        }
        $element['subject'] = array(
            '#type' => 'select',
            '#title' => '配課科目',
            '#description' => '請選擇已配課的科目',
            '#default_value' => $this->getSetting('subject'),
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