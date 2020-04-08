<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "classes_default",
 *   label = "選擇班級",
 *   field_types = {
 *     "tpedu_classes"
 *   }
 *   settings = {
 *     "filter_by_current_user" = false,
 *     "filter_by_grade_field" = false,
 *     "filter_by_grade" = false,
 *     "grade" = "",
 *     "filter_by_subject" = false,
 *     "subject" = "",
 *     "inline_columns" = 5,
 *   },
 *   edit = {
 *      "editor" = "form"
 *   }
 * )
 */
class ClassesDefaultFormatter extends FormatterBase {

    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾班級',
            '#description' => '若勾選，僅顯示目前使用者的任教班級。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $form['filter_by_grade_field'] = array(
            '#type' => 'checkbox',
            '#title' => '依年級欄位過濾班級',
            '#description' => '若勾選，僅顯示指定年級的所有班級。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        );
        $form['filter_by_grade'] = array(
            '#type' => 'checkbox',
            '#title' => '依指定年級過濾班級',
            '#description' => '若勾選，僅顯示指定年級的所有班級。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        );
        $form['grade'] = array(
            '#type' => 'textfield',
            '#title' => '年級',
            '#description' => '要顯示哪些年級的班級？請使用 , 區隔不同年級。',
            '#default_value' => $this->getSetting('grade'),
        );
        $form['filter_by_subject'] = array(
            '#type' => 'checkbox',
            '#title' => '依配課科目過濾班級',
            '#description' => '若勾選，僅顯示指定科目的所有已配課班級。',
            '#default_value' => $this->getSetting('filter_by_subject'),
        );
        $values = array();
        $subjects = all_subjects();
        foreach ($subjects as $s) {
            $values[$s->id] = $s->name;
        }
        $form['subject'] = array(
            '#type' => 'select',
            '#title' => '配課科目',
            '#description' => '請選擇已配課的科目',
            '#default_value' => $this->getSetting('subject'),
            '#options' => $values,
        );
        $form['inline_columns'] = array(
            '#type' => 'number',
            '#title' => '每行顯示數量',
            '#min' => 1,
            '#max' => 12,
            '#description' => '當使用核取框（複選）時，您可以指定每一行要顯示的欄位數量。',
            '#default_value' => $this->getSetting('inline_columns'),
        );      
        return $form;
    }

    public function settingsSummary() {
        $summary = array();
        $summary[] = '依年級欄位過濾班級: ' . $this->getSetting('filter_by_grade_field');
        $summary[] = '依指定年級過濾班級: ' . $this->getSetting('filter_by_grade_field') . '年級： ' . ($this->getSetting('grade') ?: '無');
        $subject = null;
        if (!empty(getSetting('subject'))) $subject = get_subject($this->getSetting('subject'));
        $summary[] = '依配課科目過濾班級: ' . $this->getSetting('filter_by_subject') . '科目: ' . (isset($subject->name) ? $subject->name : '無');
        $summary[] = '依使用者過濾班級: ' . $this->getSetting('filter_by_current_user');
        $summary[] = '每行顯示數量: ' . $this->getSetting('inline_columns');
        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = array();
        foreach ($items as $delta => $item) {
            $myclass = one_class($item->class_id);
            $source = array(
                '#type' => 'inline_template',
                '#template' => "班級： {{name}}",
                '#context' => [
                    'name' => $myclass->name,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }
        return $elements;
    }

}