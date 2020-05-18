<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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
class ClassesDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax']['callback'] = [$this, 'reload_class_ajax_callback'];
            $element['#ajax']['event'] = 'change';
        }

        return $element;
    }

    protected function getOptions()
    {
        $classes = array();
        if ($this->getFieldSetting('filter_by_subject') && $this->getFieldSetting('subject')) {
            $classes = get_classes_of_subject($this->getFieldSetting('subject'));
        }
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
            if ($this->getFieldSetting('filter_by_current_user')) {
                $classes = get_teach_classes($account->get('uuid')->value);
            }
        }
        if (empty($classes)) {
            $classes = all_classes();
        }
        usort($classes, function ($a, $b) { return strcmp($a->id, $b->id); });
        $options = array();
        foreach ($classes as $c) {
            $options[$c->id] = $c->name;
        }

        return $options;
    }

    protected function getStudentsOptions(array $settings, $myclass)
    {
        $values = array();
        $students = array();
        if ($settings['filter_by_class'] && $myclass) {
            $students = get_students_of_class($myclass);
            foreach ($students as $s) {
                $values[$s->id] = $s->seat.' '.$s->realname;
            }
        }

        return $values;
    }

    protected function getTeachersOptions(array $settings, $myclass)
    {
        $values = array();
        $teachers = array();
        if ($settings['filter_by_class'] && $myclass) {
            $teachers = get_teachers_of_class($myclass);
            foreach ($teachers as $t) {
                $values[$t->id] = $t->role_name.' '.$t->realname;
            }
        }

        return $values;
    }

    public function reload_class_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && ($my_field['field_type'] == 'tpedu_students' || $my_field['field_type'] == 'tpedu_teachers')) {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_class'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    if ($my_field['field_type'] == 'tpedu_students') {
                        $target['#options'] = $this->getStudentsOptions($settings, $current);
                    } else {
                        $target['#options'] = $this->getTeachersOptions($settings, $current);
                    }
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
