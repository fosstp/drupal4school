<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\user\Entity\User;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "classes_default",
 *   label = "選擇班級",
 *   field_types = {
 *     "tpedu_classes"
 *   }
 * )
 */
class ClassesDefaultWidget extends WidgetBase { 

    protected function handlesMultipleValues() {
        return $this->getFieldSetting('multiple');
    }

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $this->required = $element['#required'];
        $this->multiple = $this->getFieldSetting('multiple');
        $element['#key_column'] = 'class_id';
        $options = $this->getOptions();
        $element['#options'] = $options;
        if (!$this->multiple && !$this->required) {
            $element['#empty_option'] = '--';
            $element['#empty_value'] = '';
        }
        if ($this->multiple) {
            $element['#type'] = 'checkboxes';
            $element['#attached']['library'] = array(
                'tpedu/tpedu_fields',
            );
            $this->display_inline($element, $value);
        } else {
            $element['#type'] = 'select';
            $value = isset($items[$delta]->class_id) ? $items[$delta]->class_id : '';
            if ($value) $element['#default_value'] = $value;
        }
        $element['#ajax'] = array(
            'callback' => 'reload_class_ajax_callback',
        );
        return ['class_id' => $element];
    }

    protected function getOptions() {
        $classes = array();
        if ($this->getFieldSetting('filter_by_subject') && $this->getFieldSetting('subject'))
            $classes = get_classes_of_subject($this->getFieldSetting('subject'));
        if ($this->getFieldSetting('filter_by_grade') && $this->getFieldSetting('grade')) {
            $grades = explode(',', $this->getFieldSetting('grade'));
            foreach ($grades as $g) {
                foreach (get_classes_of_grade($g) as $c) {
                    $classes[] = $c;
                }
            }
        }
        $account = User::load(\Drupal::currentUser()->id());
        if ($account->get('init')->value == 'tpedu') {
            if ($this->getFieldSetting('filter_by_current_user')) $classes = get_teach_classes($account->get('uuid')->value);
        }
        if (empty($classes)) $classes = all_classes();
        usort($classes, function($a, $b) { return strcmp($a->id, $b->id); });
        $options = array();
        foreach ($classes as $c) {
            $options[$c->id] = $c->name;
        }
        return $options;
    }

    protected function getStudentOptions(array $settings, $myclass) {
        $values = array();
        $students = array();
        if ($settings['filter_by_class']) {
            $students = get_students_of_class($myclass);
            foreach ($students as $s) {
                $values[$s->id] = $s->seat . ' ' . $s->realname;
            }
        }
        return $values;
    }

    protected function getTeacherOptions(array $settings, $myclass) {
        $values = array();
        $teachers = array();
        if ($settings['filter_by_class']) {
            $teachers = get_teachers_of_class($myclass);
            foreach ($teachers as $t) {
                $values[$t->id] = $t->role_name . ' ' . $t->realname;
            }
        }
        return $values;
    }

    function display_inline($element) {
        $inline = $this->getFieldSetting('inline_columns');
        if (empty($inline) || $inline<2) return $element;
        if (count($element['#options']) > 0) {
            $column = 0;
            foreach ($element['#options'] as $key => $choice) {
                if ($key === 0) $key = '0';
                $style = ($column % $inline) ? 'button-columns' : 'button-columns-clear';
                if (isset($element[$key])) {
                    $element[$key]['#prefix'] = '<div class="' . $style . '">';
                    $element[$key]['#suffix'] = '</div>';
                }
                $column++;
            }
        }
        return $element;
    }

    function reload_class_ajax_callback(array &$form, FormStateInterface $form_state) {
        $commands = array();
        $element = $form_state['triggering_element'];
        $field_name = $element['#field_name'];
        $langcode = $element['#language'];
        $delta = $element['#delta'];
        $class = $element['#value'];
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            if ($my_field['type'] == 'tpedu_classes') {
                $current = $form_state['values'][$my_field_name][$langcode];
            }
        }
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            $my_instance = $parent_field[$langcode]['instance'];
            if ($my_field['type'] == 'tpedu_students' || $my_field['type'] == 'tpedu_teachers') {
                $filter = $my_instance['settings']['filter_by_class_field'];
                if ($filter) {
                    $my_field_name = $my_field['field_name'];
                    $my_element = $form[$my_field_name][$langcode];
                    foreach ($my_element['#options'] as $key => $value) {
                        unset($my_element[$key]);
                    }
                    if ($my_field['type'] == 'tpedu_students')
                        $options = $this->getStudentOptions($my_instance['settings'], $current);
                    else
                        $options = $this->getTeacherOptions($my_instance['settings'], $current);
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
                        $my_element['#inline'] = $my_instance['settings']['inline_columns'];
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