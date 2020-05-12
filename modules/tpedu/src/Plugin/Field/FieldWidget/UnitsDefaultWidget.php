<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
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
        if (!$this->multiple && $this->required) {
            $element['#ajax']['callback'][] = 'reload_unit_ajax_callback';
        }

        return $element;
    }

    protected function getOptions(FieldableEntityInterface $entity = null)
    {
        $account = User::load(\Drupal::currentUser()->id());
        if ($account->get('init')->value == 'tpedu') {
            if ($this->getFieldSetting('filter_by_current_user')) {
                $units = get_units_of_job($account->get('uuid')->value);
            }
        }
        if (empty($units)) {
            $units = all_units();
        }
        usort($units, function ($a, $b) { return strcmp($a->id, $b->id); });
        $options = array();
        foreach ($units as $o) {
            $options[$o->id] = $o->name;
        }
        $this->options = $options;

        return $this->options;
    }

    protected function getRolesOptions(array $settings, $unit)
    {
        $options = array();
        $roles = array();
        if ($settings['filter_by_units'] && $unit) {
            $roles = get_roles_of_unit($unit);
            foreach ($roles as $r) {
                $options[$r->id] = $r->name;
            }
        }

        return $options;
    }

    protected function getTeachersOptions(array $settings, $unit)
    {
        $options = array();
        $roles = array();
        if ($settings['filter_by_unit'] && $unit) {
            $teachers = get_teachers_of_unit($unit);
            foreach ($teachers as $t) {
                $options[$t->id] = $t->role_name.' '.$t->realname;
            }
        }

        return $options;
    }

    public function reload_unit_ajax_callback($form, $form_state)
    {
        $commands = array();
        $element = $form_state['triggering_element'];
        $field_name = $element['#field_name'];
        $langcode = $element['#language'];
        $delta = $element['#delta'];
        $class = $element['#value'];
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            if ($my_field['type'] == 'tpedu_units') {
                $current = $form_state['values'][$my_field_name][$langcode];
            }
        }
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            $my_instance = $parent_field[$langcode]['instance'];
            if ($my_field['type'] == 'tpedu_roles' || $my_field['type'] == 'tpedu_teachers') {
                $filter = $my_instance['settings']['filter_by_unit'];
                if ($filter) {
                    $my_field_name = $my_field['field_name'];
                    $my_element = $form[$my_field_name][$langcode];
                    foreach ($my_element['#options'] as $key => $value) {
                        unset($my_element[$key]);
                    }
                    if ($my_field['type'] == 'tpedu_roles') {
                        $options = $this->getRolesOptions($my_instance['settings'], $current);
                    } else {
                        $options = $this->getTeachersOptions($my_instance['settings'], $current);
                    }
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

                        $my_element = display_inline($my_element, $my_element['#inline']);
                    }
                    $element_id = 'edit-'.str_replace('_', '-', $my_field_name);
                    $commands[] = ajax_command_replace("#$element_id div", drupal_render($my_element));
                }
            }
        }

        return array('#type' => 'ajax', '#commands' => $commands);
    }
}
