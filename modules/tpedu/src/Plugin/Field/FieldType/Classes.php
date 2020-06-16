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
 *   id = "tpedu_classes",
 *   label = "班級",
 *   description = "班級選單",
 *   category = "臺北市教育人員",
 *   default_widget = "classes_default",
 *   default_formatter = "classes_default",
 * )
 */
class Classes extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
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

    public function isEmpty()
    {
        return empty($this->get('class_id')->getValue());
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['class_id'] = DataDefinition::create('string')->setLabel('班級代號');

        return $properties;
    }

    public static function defaultFieldSettings()
    {
        return [
            'filter_by_current_user' => false,
            'filter_by_grade' => false,
            'grade' => '',
            'filter_by_subject' => false,
            'subject' => '',
            'inline_columns' => 10,
        ] + parent::defaultFieldSettings();
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = array();
        $element['extra_info'] = array(
            '#markup' => '<p>此欄位可以單獨使用或結合科目、教師、學生欄位使用！請選擇是否使用過濾機制，若使用年級或科目進行過濾，除了可以設定預設值外，您也可以使用年級或科目欄位進行動態過濾。結合科目欄位時，可用於選取不同班級的授課科目；結合教師欄位時，可用於選取不同班級的任教老師；結合學生欄位時，可用於選取不同班級的學生！</p>',
        );
        $element['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾班級',
            '#description' => '若勾選，僅顯示目前使用者的任教班級。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $element['filter_by_grade'] = array(
            '#type' => 'checkbox',
            '#title' => '依年級欄位過濾班級（注意：若年級欄位為可複選，將不會有作用）',
            '#description' => '若勾選，僅顯示指定年級的所有班級。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        );
        $element['grade'] = array(
            '#type' => 'textfield',
            '#title' => '預設年級',
            '#description' => '預設要顯示哪些年級的班級？',
            '#default_value' => $this->getSetting('grade'),
        );
        $element['filter_by_subject'] = array(
            '#type' => 'checkbox',
            '#title' => '依配課科目過濾班級',
            '#description' => '若勾選，僅顯示指定科目的所有已配課班級。',
            '#default_value' => $this->getSetting('filter_by_subject'),
        );
        $values = array('' => '--');
        $subjects = all_subjects();
        if ($subjects) {
            foreach ($subjects as $s) {
                $values[$s->id] = $s->name;
            }
        }
        $element['subject'] = array(
            '#type' => 'select',
            '#title' => '配課科目',
            '#description' => '預設的配課科目',
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
