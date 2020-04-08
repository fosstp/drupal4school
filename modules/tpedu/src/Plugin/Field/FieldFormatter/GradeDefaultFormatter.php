<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "grade_default",
 *   label = "選擇年級",
 *   field_types = {
 *     "tpedu_grade"
 *   }
 *   settings = {
 *     "inline_columns" = 5,
 *   },
 *   edit = {
 *      "editor" = "form"
 *   }
 * )
 */
class GradeDefaultFormatter extends FormatterBase {

    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form['inline_columns'] = array(
            '#type' => 'number',
            '#title' => '每行顯示數量',
            '#min' => 1,
            '#max' => 6,
            '#description' => '當使用核取框（複選）時，您可以指定每一行要顯示的欄位數量。',
            '#default_value' => $this->getSetting('inline_columns'),
        );      
        return $form;
    }

    public function settingsSummary() {
        $summary = array();
        $summary[] = '每行顯示數量: ' . $this->getSetting('inline_columns');
        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = array();
        foreach ($items as $delta => $item) {
            $grade = one_class($item->grade);
            $source = array(
                '#type' => 'inline_template',
                '#template' => "年級： {{name}}",
                '#context' => [
                    'name' => $grade . '年級',
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }
        return $elements;
    }

}