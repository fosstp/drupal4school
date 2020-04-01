<?php

/**
 * @file
 * Contains \Drupal\tpedu\Plugin\Field\FieldWidget\ClassesDefaultWidget.
 */

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\options\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

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
class ClassesDefaultWidget extends OptionsWidgetBase { 

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $this->required = $element['#required'];
        $this->multiple = $this->fieldDefinition
            ->getFieldStorageDefinition()
            ->isMultiple();
        $this->has_value = isset($items[0]->{$this->column});

        // Add our custom validator.
        $element['#element_validate'][] = [
            get_class($this),
            'validateElement',
        ];
        $element['#key_column'] = $this->column;
        $element['#ajax'] = array(
            'callback' => 'reload_class_ajax_callback',
        );
        return $element;
    }

    function reload_class_ajax_callback($form, $form_state) {
        $commands = array();
        $period = current_seme();
        $element = $form_state['triggering_element'];
        $field_name = $element['#field_name'];
        $langcode = $element['#language'];
        $delta = $element['#delta'];
        $class = $element['#value'];
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            if ($my_field['type'] == 'tpedu_students') {
                $students = $form_state['values'][$my_field_name][$langcode];
            }
            if ($my_field['type'] == 'tpedu_teachers') {
                $teachers = $form_state['values'][$my_field_name][$langcode];
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
                    $options = tpedu_options_list($my_field, $my_instance, $my_element['#entity_type'], $my_element['#entity'], $period['year'], $period['seme'], $class);
                    if ($my_element['#properties']['empty_option']) {
                        $label = theme('options_none', array('instance' => $my_instance, 'option' => $my_element['#properties']['empty_option']));
                        $options = array('_none' => $label) + $options;
                    }
                    $my_element['#options'] = $options;
                    if ($my_element['#type'] == 'radios') {
                        $my_element = form_process_radios($my_element);
                        foreach ($my_element['#options'] as $key => $value) {
                            if ($key == $my_element['#value']) {
                                $my_element[$key]['#value'] = TRUE;
                            } else {
                                $my_element[$key]['#value'] = FALSE;
                            }
                        }
                        $my_element = display_inline($my_element);
                    } elseif ($my_element['#type'] == 'checkboxes') {
                        $my_element = form_process_checkboxes($my_element);
                        foreach ($my_element['#options'] as $key => $value) {
                            foreach (array_values((array) $my_element['#value']) as $default_value) {
                                if ($key == $default_value) {
                                    $my_element[$key]['#value'] = $key;
                                    $my_element[$key] = form_process_checkbox($my_element[$key], $form_state);
                                } else {
                                    $my_element[$key]['#value'] = FALSE;
                                    $my_element[$key] = form_process_checkbox($my_element[$key], $form_state);
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