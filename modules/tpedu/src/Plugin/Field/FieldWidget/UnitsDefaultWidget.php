<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "units_default",
 *   label = "選擇行政單位",
 *   field_types = {
 *     "tpedu_units"
 *   }
 * )
 */
class UnitsDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax']['callback'] = [$this, 'reload_unit_ajax_callback'];
            $element['#ajax']['event'] = 'change';
        }

        return $element;
    }

    protected function getOptions(FormState $form_state)
    {
        $options = [];
        $units = [];
        if ($this->getFieldSetting('filter_by_current_user')) {
            $account = User::load(\Drupal::currentUser()->id());
            if ($account->get('init')->value == 'tpedu') {
                $units = get_units_of_job($account->get('uuid')->value);
            }
        } else {
            $units = all_units();
        }
        if (!empty($units)) {
            usort($units, function ($a, $b) { return strcmp($a->id, $b->id); });
            foreach ($units as $o) {
                $options[$o->id] = $o->name;
            }
        }
        $this->options = $options;

        return $options;
    }

    protected function getRolesOptions(array $settings, $unit)
    {
        $options = [];
        $roles = [];
        if ($settings['filter_by_units'] && $unit) {
            $roles = get_roles_of_unit($unit);
            usort($roles, function ($a, $b) { return strcmp($a->id, $b->id); });
            foreach ($roles as $r) {
                $options[$r->id] = $r->name;
            }
        }

        return $options;
    }

    protected function getTeachersOptions(array $settings, $unit)
    {
        $options = [];
        $roles = [];
        if ($settings['filter_by_unit'] && $unit) {
            $teachers = get_teachers_of_unit($unit);
            usort($teachers, function ($a, $b) { return strcmp($a->realname, $b->realname); });
            foreach ($teachers as $t) {
                $options[$t->uuid] = $t->role_name.' '.$t->realname;
            }
        }

        return $options;
    }

    public function reload_unit_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && ($my_field['field_type'] == 'tpedu_roles' || $my_field['field_type'] == 'tpedu_teachers')) {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_unit'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    if ($my_field['field_type'] == 'tpedu_roles') {
                        $target['#options'] = $this->getRolesOptions($settings, $current);
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
