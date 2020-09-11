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
 *   id = "subjects_default",
 *   label = "選擇科目",
 *   field_types = {
 *     "tpedu_subjects"
 *   }
 * )
 */
class SubjectsDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax']['callback'] = [$this, 'reload_subject_ajax_callback'];
            $element['#ajax']['event'] = 'change';
        }

        return $element;
    }

    protected function getOptions()
    {
        $subjects = [];
        if ($this->getFieldSetting('filter_by_domain') && $this->getFieldSetting('domain')) {
            $subjects = get_subjects_of_domain($this->getFieldSetting('domain'));
        }
        if ($this->getFieldSetting('filter_by_class') && $this->getFieldSetting('class')) {
            $subjects = get_subjects_of_class($this->getFieldSetting('class'));
        }
        $account = User::load(\Drupal::currentUser()->id());
        if ($account->get('init')->value == 'tpedu') {
            if ($this->getFieldSetting('filter_by_current_user')) {
                $subjects = get_subjects_of_assignment($account->get('uuid')->value);
            }
        }
        if (empty($subjects)) {
            $subjects = all_subjects();
        }
        usort($subjects, function ($a, $b) { return strcmp($a->id, $b->id); });
        $options = [];
        foreach ($subjects as $r) {
            $options[$r->id] = $r->name;
        }

        return $options;
    }

    protected function getClassesOptions(array $settings, $subject)
    {
        $values = [];
        $classes = [];
        if ($settings['filter_by_subject'] && $subject) {
            $classes = get_classes_of_subject($subject);
            usort($classes, function ($a, $b) { return strcmp($a->id, $b->id); });
            foreach ($classes as $t) {
                $values[$t->id] = $t->name;
            }
        }

        return $values;
    }

    protected function getTeachersOptions(array $settings, $subject)
    {
        $values = [];
        $teachers = [];
        if ($settings['filter_by_subject'] && $subject) {
            $teachers = get_teachers_of_subject($subject);
            usort($teachers, function ($a, $b) { return strcmp($a->realname, $b->realname); });
            foreach ($teachers as $t) {
                $values[$t->uuid] = $t->role_name.' '.$t->realname;
            }
        }

        return $values;
    }

    public function reload_subject_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && ($my_field['field_type'] == 'tpedu_classes' || $my_field['field_type'] == 'tpedu_teachers')) {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_subject'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    if ($my_field['field_type'] == 'tpedu_classes') {
                        $target['#options'] = $this->getClassesOptions($settings, $current);
                    } else {
                        $target['#options'] = $this->getTeachersOptions($settings, $current);
                    }
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            $target[$k] = [
                                '#type' => 'checkbox',
                                '#id' => $target['#id'].'-'.$k,
                                '#name' => $field_name.'['.$k.']',
                                '#title' => $v,
                                '#return_value' => $k,
                                '#default_value' => null,
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
