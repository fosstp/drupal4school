<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'classes_default' widget.
 *
 * @FieldWidget(
 *   id = "domain_default",
 *   label = "選擇領域",
 *   field_types = {
 *     "tpedu_domain"
 *   }
 * )
 */
class DomainDefaultWidget extends TpeduWidgetBase
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        if (!$this->multiple) {
            $element['#ajax']['callback'] = [$this, 'reload_domain_ajax_callback'];
            $element['#ajax']['event'] = 'change';
        }

        return $element;
    }

    protected function getOptions(FormState $form_state)
    {
        $options = [];
        $domains = all_domains();
        if (!empty($domains)) {
            usort($domains, function ($a, $b) { return strcmp($a->domain, $b->domain); });
            foreach ($domains as $g) {
                $options[$g->domain] = $g->domain.'領域';
            }
        }
        $this->options = $options;

        return $options;
    }

    protected function getSubjectsOptions(array $settings, $domain)
    {
        $options = [];
        $subjects = [];
        if ($settings['filter_by_domain'] && $domain) {
            $subjects = get_subjects_of_domain($domain);
            usort($subjects, function ($a, $b) { return strcmp($a->id, $b->id); });
            foreach ($subjects as $c) {
                $options[$c->id] = $c->name;
            }
        }

        return $options;
    }

    protected function getTeachersOptions(array $settings, $domain)
    {
        $options = [];
        $teachers = [];
        if ($settings['filter_by_domain'] && $domain) {
            $teachers = get_teachers_of_domain($domain);
            usort($teachers, function ($a, $b) { return strcmp($a->realname, $b->realname); });
            foreach ($teachers as $t) {
                $options[$t->uuid] = $t->role_name.' '.$t->realname;
            }
        }

        return $options;
    }

    public function reload_domain_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $element = $form_state->getTriggeringElement();
        $current = $element['#value'];
        $fields = $form_state->getStorage()['field_storage']['#parents']['#fields'];
        foreach ($fields as $field_name => $my_field) {
            if (isset($my_field['field_type']) && ($my_field['field_type'] == 'tpedu_subjects' || $my_field['field_type'] == 'tpedu_teachers')) {
                $settings = $my_field['field_settings'];
                $filter = $settings['filter_by_domain'];
                if ($filter) {
                    $target = $form[$field_name]['widget'];
                    $element_id = 'edit-'.str_replace('_', '-', $field_name);
                    $target['#id'] = $element_id;
                    if ($target['#type'] == 'checkboxes') {
                        foreach ($target['#options'] as $k => $v) {
                            unset($target[$k]);
                        }
                    }
                    if ($my_field['field_type'] == 'tpedu_subjects') {
                        $target['#options'] = $this->getSubjectsOptions($settings, $current);
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
