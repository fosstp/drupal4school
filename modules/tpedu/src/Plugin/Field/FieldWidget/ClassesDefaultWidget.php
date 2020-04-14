<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

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

    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form['filter_by_current_user'] = array(
            '#type' => 'checkbox',
            '#title' => '依使用者過濾班級',
            '#description' => '若勾選，僅顯示目前使用者的任教班級。',
            '#default_value' => $this->getSetting('filter_by_current_user'),
        );
        $form['filter_by_grade'] = array(
            '#type' => 'checkbox',
            '#title' => '依年級欄位過濾班級',
            '#description' => '若勾選，僅顯示指定年級的所有班級。',
            '#default_value' => $this->getSetting('filter_by_grade'),
        );
        $form['grade'] = array(
            '#type' => 'textfield',
            '#title' => '年級(初始值)',
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
        $summary[] = '依年級欄位過濾班級: ' . $this->getSetting('filter_by_grade_field') . '年級： ' . ($this->getSetting('grade') ?: '無');
        $subject = null;
        if (!empty(getSetting('subject'))) $subject = get_subject($this->getSetting('subject'));
        $summary[] = '依配課科目過濾班級: ' . $this->getSetting('filter_by_subject') . '科目: ' . (isset($subject->name) ? $subject->name : '無');
        $summary[] = '依使用者過濾班級: ' . $this->getSetting('filter_by_current_user');
        $summary[] = '每行顯示數量: ' . $this->getSetting('inline_columns');
        return $summary;
    }

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $this->required = $element['#required'];
        $this->multiple = $this->fieldDefinition
            ->getFieldStorageDefinition()
            ->isMultiple();
        $this->has_value = isset($items[0]->class_id);

        $element['#key_column'] = 'class_id';
        $element['#title'] = '年級';
        if ($this->multiple) {
            $element['#type'] = 'checkboxes';
        } else {
            $element['#type'] = 'select';
        }
        if (!isset($this->options)) $this->getOptions();
        if (! $this->required) {
            $this->options = array( '' => '--' ) + $this->options;
        }
        $element['#options'] = $this->options;
        $element['#attached']['library'] = array(
            'tpedu/tpedu_fields',
        );
        $element['#ajax'] = array(
            'callback' => 'reload_class_ajax_callback',
        );
        return $element;
    }

    protected function getOptions() {
        if (!isset($this->options)) {
            $classes = array();
            $account = \Drupal::currentUser();
            if ($account->get('init')->value == 'tpedu') {
                if ($this->getSetting('filter_by_subject') && $this->getSetting('subject'))
                    $classes = get_classes_of_subject($this->getSetting('subject'));
                if ($this->getSetting('filter_by_grade') && $this->getSetting('grade')) {
                    $grades = explode(',', $this->getSetting('grade'));
                    foreach ($grades as $g) {
                        $classes = $classes + get_classes_of_grade($g);
                    }
                }
                if ($this->getSetting('filter_by_current_user')) $classes = get_teach_classes($account->get('uuid')->value);
            }
            if (empty($classes)) $classes = all_classes();
            foreach ($classes as $c) {
                $options[$c->id] = $c->name;
            }
            $this->options = $options;
        }
        return $this->options;
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
        if (!isset($element['#inline']) || $element['#inline']<2) return $element;
        if (count($element ['#options']) > 0) {
            $column = 0;
            foreach ($element ['#options'] as $key => $choice) {
                if ($key === 0) $key = '0';
                $style = ($column % $element['#inline']) ? 'button-columns' : 'button-columns-clear';
                if (isset($element[$key])) {
                    $element[$key]['#prefix'] = '<div class="' . $style . '">';
                    $element[$key]['#suffix'] = '</div>';
                }
                $column++;
            }
        }
        return $element;
    }

    function reload_class_ajax_callback($form, $form_state) {
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