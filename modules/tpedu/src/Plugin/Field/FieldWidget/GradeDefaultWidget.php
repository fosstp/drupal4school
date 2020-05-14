<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
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
        $langcode = $form['langcode'];
        $element = $form_state->getTriggeringElement();
        $current = $form_state->getValue($element->id());
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_classes') {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_grade'];
                if ($filter) {
                    $target = $form[$field_name];
                    foreach ($target['#options'] as $key => $value) {
                        unset($target[$key]);
                    }
                    $options = $this->getClassesOptions($settings, $current);
                    $target['#options'] = $options;
                    if ($target['#type'] == 'checkboxes') {
                        $inline = $settings['inline_columns'];
                        $target = $this->display_inline($target, $inline);
                    }
                    $element_id = '#edit-'.str_replace('_', '-', $field_name);
                    $response->addCommand(new ReplaceCommand($element_id, \Drupal::service('renderer')->render($target)));
                }
            }
        }

        return $response;
    }
}
