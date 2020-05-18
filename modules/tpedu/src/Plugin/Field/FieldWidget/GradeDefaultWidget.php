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
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_classes') {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_grade'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    $target['#options'] = $this->getClassesOptions($settings, $current);
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            $target[$k] = array(
                                '#type' => 'checkbox',
                                '#id' => $target['#id'].'-'.$k,
                                '#name' => $field_name.'['.$k.']',
                                '#title' => $v,
                                '#return_value' => $k,
                                '#default_value' => null,
                                '#attributes' => $target['#attributes'],
                            );
                        }
                        $inline = $settings['inline_columns'];
                        $target = $this->display_inline($target, $inline);
                        $origin = '#edit-'.str_replace('_', '-', $field_name).'--wrapper';
                    } elseif (isset($target['#empty_value'])) {
                        $target['#options'] = [$target['#empty_value'] => $target['#empty_option']] + $target['#options'];
                        $origin = '.form-item-'.str_replace('_', '-', $field_name);
                    }
                    $response->addCommand(new ReplaceCommand($origin, \Drupal::service('renderer')->render($target)));
                }
            }
        }

        return $response;
    }
}
