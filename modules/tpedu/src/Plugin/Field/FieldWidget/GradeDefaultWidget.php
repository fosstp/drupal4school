<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "grade_default",
 *   label = "選擇年級",
 *   field_types = {
 *     "tpedu_grade"
 *   }
 * )
 */
class GradeDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax']['callback'] = [$this, 'reload_grade_ajax_callback'];
            $element['#ajax']['event'] = 'change';
        }

        return $element;
    }

    protected function getOptions(FieldableEntityInterface $entity = null)
    {
        if (!isset($this->options)) {
            $grades = all_grade();
            foreach ($grades as $g) {
                $options[$g->grade] = $g->grade.'年級';
            }
            $this->options = $options;
        }

        return $this->options;
    }

    protected function getClassesOptions(array $settings, $grade)
    {
        $options = array();
        $classes = array();
        if ($settings['filter_by_grade'] && $grade) {
            $grades = explode(',', $grade);
            foreach ($grades as $g) {
                foreach (get_classes_of_grade($g) as $c) {
                    $classes[] = $c;
                }
            }
            usort($classes, function ($a, $b) { return strcmp($a->id, $b->id); });
            foreach ($classes as $c) {
                $options[$c->id] = $c->name;
            }
        }

        return $options;
    }

    public function reload_grade_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $elements = $form_state->getCompleteForm();
        $langcode = $elements['langcode'];
        foreach ($elements as $field_name => $my_field) {
            if (isset($my_field['type']) && $my_field['type'] == 'tpedu_classes') {
                $my_instance = $my_field['instance'];
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
                        foreach ($my_element['#options'] as $key => $value) {
                            if ($key == $my_element['#value']) {
                                $my_element[$key]['#value'] = $key;
                            } else {
                                $my_element[$key]['#value'] = false;
                            }
                        }
                    } elseif ($my_element['#type'] == 'checkboxes') {
                        $inline = $my_instance['settings']['inline_columns'];
                        foreach ($my_element['#options'] as $key => $value) {
                            foreach (array_values((array) $my_element['#value']) as $default_value) {
                                if ($key == $default_value) {
                                    $my_element[$key]['#value'] = $key;
                                } else {
                                    $my_element[$key]['#value'] = false;
                                }
                            }
                        }
                        $my_element = $this->display_inline($my_element, $inline);
                    }
                    $element_id = '#edit-'.str_replace('_', '-', $my_field_name);
                    $response = new AjaxResponse();
                    $response->addCommand(new ReplaceCommand($element_id, \Drupal::service('renderer')->render($my_element)));

                    return $response;
                }
            }
        }
    }
}
