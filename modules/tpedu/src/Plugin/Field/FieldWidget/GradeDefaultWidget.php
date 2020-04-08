<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\options\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "grade_default",
 *   label = "選擇班級",
 *   field_types = {
 *     "tpedu_grade"
 *   }
 * )
 */
class GradeDefaultWidget extends OptionsWidgetBase { 

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $this->required = $element['#required'];
        $this->multiple = $this->fieldDefinition
            ->getFieldStorageDefinition()
            ->isMultiple();
        $this->has_value = isset($items[0]->class_id);

        $element['#key_column'] = 'grade';
        if ($this->multiple) {
            $element['class_id'] = array(
                '#title' => '年級',
                '#type' => 'checkboxes',
                '#options' => $this->getOptions(),
            );
        } else {
            $element['class_id'] = array(
                '#title' => '年級',
                '#type' => 'select',
                '#options' => $this->getOptions(),
            );
        }
        $element['#attached']['library'][] = 'tpedu/tpedu_fields';
        return $element;
    }

    protected function getOptions() {
        $grades = all_grade();
        foreach ($grades as $g) {
            $values[$g] = $g . '年級';
        }
        return $values;
    }

    function display_inline($element) {
        if (!isset($element['#inline']) || $element['#inline']<2) return $element;
        if (count($element ['#options']) > 0) {
            $column = 0;
            foreach ($element ['#options'] as $key => $choice) {
                if ($key === 0) $key = '0';
                $class = ($column % $element['#inline']) ? 'button-columns' : 'button-columns-clear';
                if (isset($element[$key])) {
                    $element[$key]['#prefix'] = '<div class="' . $class . '">';
                    $element[$key]['#suffix'] = '</div>';
                }
                $column++;
            }
        }
        return $element;
    }

}