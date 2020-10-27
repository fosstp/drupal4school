<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
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
class ClassesDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax'] = [
                'callback' => [$this, 'reload_class_ajax_callback'],
                'event' => 'change',
            ];
        }

        return $element;
    }

    protected function getOptions(FormStateInterface $form_state)
    {
        $classes = [];
        if ($this->getFieldSetting('filter_by_subject')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_subjects') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (empty($current)) {
                $current = $this->getFieldSetting('subject');
            }
            if (!empty($current)) {
                $classes = get_classes_of_subject($current);
            }
        }
        if ($this->getFieldSetting('filter_by_grade')) {
            $current = '';
            $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
            foreach ($fields as $field_name => $my_field) {
                if (isset($my_field['field_type']) && $my_field['field_type'] == 'tpedu_grade') {
                    $current = $form_state->getValue($field_name);
                }
            }
            if (!empty($current)) {
                $classes = get_classes_of_grade($current);
            } else {
                $current = $this->getFieldSetting('grade');
                $grades = explode(',', $this->getFieldSetting('grade'));
                foreach ($grades as $g) {
                    $classes = get_classes_of_grade($g);
                    if ($classes) {
                        foreach ($classes as $c) {
                            $classes[] = $c;
                        }
                    }
                }
            }
        }
        if ($this->getFieldSetting('filter_by_current_user')) {
            $account = User::load(\Drupal::currentUser()->id());
            if ($account->get('init')->value == 'tpedu') {
                $classes = get_teach_classes($account->get('uuid')->value);
            }
        }
        if (empty($classes)) {
            $classes = all_classes();
        }
        usort($classes, function ($a, $b) { return strcmp($a->id, $b->id); });
        $options = [];
        foreach ($classes as $c) {
            $options[$c->id] = $c->name;
        }

        return $options;
    }

    protected function getSubjectsOptions(array $settings, $myclass)
    {
        $values = [];
        $subjects = [];
        if ($settings['filter_by_class'] && $myclass) {
            $subjects = get_subjects_of_class($myclass);
            foreach ($subjects as $s) {
                $values[$s->id] = $s->name;
            }
        }

        return $values;
    }

    protected function getStudentsOptions(array $settings, $myclass)
    {
        $values = [];
        $students = [];
        if ($settings['filter_by_class'] && $myclass) {
            $students = get_students_of_class($myclass);
            usort($students, function ($a, $b) { return intval($a->seat) < intval($b->seat) ? -1 : 1; });
            foreach ($students as $s) {
                $values[$s->uuid] = $s->seat.' '.$s->realname;
            }
        }

        return $values;
    }

    protected function getTeachersOptions(array $settings, $myclass)
    {
        $values = [];
        $teachers = [];
        if ($settings['filter_by_class'] && $myclass) {
            $teachers = get_teachers_of_class($myclass);
            usort($teachers, function ($a, $b) { return strcmp($a->realname, $b->realname); });
            foreach ($teachers as $t) {
                $values[$t->uuid] = $t->role_name.' '.$t->realname;
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
            if (isset($my_field['field_type']) && ($my_field['field_type'] == 'tpedu_students' || $my_field['field_type'] == 'tpedu_teachers' || $my_field['field_type'] == 'tpedu_subjects')) {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_class'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $values = array_filter(array_values($form_state->getValue($field_name)));
                    $target['#default_value'] = $values;
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    if ($my_field['field_type'] == 'tpedu_students') {
                        $target['#options'] = $this->getStudentsOptions($settings, $current);
                    } elseif ($my_field['field_type'] == 'tpedu_teachers') {
                        $target['#options'] = $this->getTeachersOptions($settings, $current);
                    } else {
                        $target['#options'] = $this->getSubjectsOptions($settings, $current);
                    }
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            $target[$k] = [
                                '#type' => 'checkbox',
                                '#id' => $target['#id'].'-'.$k,
                                '#name' => $field_name.'['.$k.']',
                                '#title' => $v,
                                '#return_value' => $k,
                                '#default_value' => in_array($k, $values) ? $k : null,
                                '#attributes' => $target['#attributes'],
                            ];
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
