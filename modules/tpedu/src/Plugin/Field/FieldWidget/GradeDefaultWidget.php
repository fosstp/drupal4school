<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

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
        $element['#ajax'] = array(
            'callback' => 'reload_grade_ajax_callback',
        );
        if ($this->multiple) {
            $element['grade'] = array(
                '#title' => '年級',
                '#type' => 'checkboxes',
                '#options' => $this->getOptions(),
            );
        } else {
            $element['grade'] = array(
                '#title' => '年級',
                '#type' => 'select',
                '#options' => $this->getOptions(),
            );
        }
        $element['#attached']['library'][] = 'tpedu/tpedu_fields';
        return $element;
    }

    protected function getOptions(FieldableEntityInterface $entity) {
        if (!isset($this->options)) {
            $grades = all_grade();
            foreach ($grades as $g) {
                $options[$g] = $g . '年級';
            }
            $this->options = $options;
        }
        return $this->options;
    }

    protected function getClassesOptions(array $settings = [], $grade = null) {
        $classes = array();
        $account = \Drupal::currentUser;
        if ($account->init == 'tpedu') {
            if ($settings['filter_by_subject'] && $settings['subject']) $classes = get_classes_of_subject($settings['subject']);
            if ($settings['filter_by_grade'] && $grade) {
                $grades = explode(',', $grade);
                foreach ($grades as $g) {
                    $classes = $classes + get_classes_of_grade($g);
                }
            }
            if ($settings['filter_by_current_user']) $classes = get_teach_classes($account->uuid);
        }
        if (empty($classes)) $classes = all_classes();
        foreach ($classes as $c) {
            $values[$c->id] = $c->name;
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

    function reload_grade_ajax_callback($form, $form_state) {
        $commands = array();
        $element = $form_state['triggering_element'];
        $field_name = $element['#field_name'];
        $langcode = $element['#language'];
        $delta = $element['#delta'];
        $class = $element['#value'];
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            if ($my_field['type'] == 'tpedu_grade') {
                $current = $form_state['values'][$my_field_name][$langcode];
            }
        }
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            $my_instance = $parent_field[$langcode]['instance'];
            if ($my_field['type'] == 'tpedu_classes') {
                $filter = $my_instance['settings']['filter_by_grade'];
                if ($filter) {
                    $my_field_name = $my_field['field_name'];
                    $my_element = $form[$my_field_name][$langcode];
                    foreach ($my_element['#options'] as $key => $value) {
                        unset($my_element[$key]);
                    }
                    $options = $this->getClassesOptions($my_instance['settings'], $current);
                    if ($my_element['#properties']['empty_option']) {
                        $label = theme('options_none', array('instance' => $my_instance, 'option' => $my_element['#properties']['empty_option']));
                        $options = array('_none' => $label) + $options;
                    }
                    $my_element['#options'] = $options;
                    if ($my_element['#type'] == 'select') {
                        $my_element = drupal_render($my_element);
                        foreach ($my_element['#options'] as $key => $value) {
                            if ($key == $my_element['#value']) {
                                $my_element[$key]['#value'] = $key;
                            } else {
                                $my_element[$key]['#value'] = false;
                            }
                        }
                    } elseif ($my_element['#type'] == 'checkboxes') {
                        $my_element = drupal_render($my_element);
                        foreach ($my_element['#options'] as $key => $value) {
                            foreach (array_values((array) $my_element['#value']) as $default_value) {
                                if ($key == $default_value) {
                                    $my_element[$key]['#value'] = $key;
                                    $my_element[$key] = drupal_render($my_element[$key]);
                                } else {
                                    $my_element[$key]['#value'] = false;
                                    $my_element[$key] = drupal_render($my_element[$key]);
                                }
                            }
                        }
                        $my_element = display_inline($my_element);
                    }
                    $element_id = 'edit-' . str_replace('_', '-', $my_field_name);
                    $commands[] = ajax_command_replace("#$element_id div", drupal_render($my_element));
                }
            }
        }
        return array('#type' => 'ajax', '#commands' => $commands);
    }

}