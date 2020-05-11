<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
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
class ClassesDefaultWidget extends WidgetBase
{
    public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = null)
    {
        $field_name = $this->fieldDefinition->getName();
        $parents = $form['#parents'];

        // Store field information in $form_state.
        if (!static::getWidgetState($parents, $field_name, $form_state)) {
            $field_state = array(
            'items_count' => count($items),
            'array_parents' => array(),
          );
            static::setWidgetState($parents, $field_name, $form_state, $field_state);
        }

        // Collect widget elements.
        $elements = array();
        $delta = isset($get_delta) ? $get_delta : 0;
        $element = array(
            '#title' => $this->fieldDefinition->getLabel(),
            '#description' => FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription())),
        );
        $element = $this->formElement($items, $delta, $element, $form, $form_state);
        if ($element) {
            if (isset($get_delta)) {
                $elements[$delta] = $element;
            } else {
                $elements = $element;
            }
        }
        $elements['#after_build'][] = array(
            get_class($this),
            'afterBuild',
        );
        $elements['#field_name'] = $field_name;
        $elements['#field_parents'] = $parents;
        $elements['#parents'] = array_merge($parents, array(
            $field_name,
        ));

        // Most widgets need their internal structure preserved in submitted values.
        $elements += array(
            '#tree' => true,
        );

        return array(
            '#type' => 'container',
            '#parents' => array_merge($parents, array(
                $field_name.'_wrapper',
            )),
            '#attributes' => array(
                'class' => array(
                    'field--type-'.Html::getClass($this->fieldDefinition->getType()),
                    'field--name-'.Html::getClass($field_name),
                    'field--widget-'.Html::getClass($this->getPluginId()),
                ),
            ),
            'widget' => $elements,
        );
    }

    public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state)
    {
        $field_name = $this->fieldDefinition->getName();

        // Extract the values from $form_state->getValues().
        $path = array_merge($form['#parents'], [$field_name]);
        $key_exists = null;
        $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
        if (!is_array($values)) {
            $values = array($values);
        }
        if ($key_exists) {
            $values = $this->massageFormValues($values, $form, $form_state);
            $items->setValue($values);
            $items->filterEmptyItems();

            // Put delta mapping in $form_state, so that flagErrors() can use it.
            $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
            foreach ($items as $delta => $item) {
                $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
                unset($item->_original_delta, $item->_weight);
            }
            static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
        }
    }

    public static function validateElement(array $element, FormStateInterface $form_state)
    {
        if ($element['#required'] && $element['#value'] == '_none') {
            $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
        }

        if (is_array($element['#value'])) {
            $values = array_values($element['#value']);
        } else {
            $values = [$element['#value']];
        }

        $index = array_search('_none', $values, true);
        if ($index !== false) {
            unset($values[$index]);
        }

        $items = [];
        foreach ($values as $value) {
            $items[] = [$element['#key_column'] => $value];
        }
        $form_state->setValueForElement($element, $items);
    }

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element['#delta'] = $delta;
        $element['#weight'] = $delta;
        $element['#key_column'] = 'class_id';
        $options = $this->getOptions();
        $element['#options'] = $options;
        $this->required = $this->fieldDefinition->isRequired();
        $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
        $this->has_value = isset($items[0]->class_id);
        if (!$this->multiple && !$this->required) {
            $element['#empty_option'] = '--';
            $element['#empty_value'] = '';
            $element['#required'] = false;
        } else {
            $element['#required'] = true;
        }
        if ($this->multiple) {
            $element['#type'] = 'checkboxes';
            $this->display_inline($element);
            $element['#default_value'] = $this->getSelectedOptions($items);
        } else {
            $element['#type'] = 'select';
            $value = isset($items[$delta]->class_id) ? $items[$delta]->class_id : '';
            if ($value) {
                $element['#default_value'] = $value;
            }
        }
        if (!$this->multiple && $this->required) {
            $element['#ajax']['callback'][] = 'reload_class_ajax_callback';
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

    protected function getSelectedOptions(FieldItemListInterface $items)
    {
        $flat_options = OptGroup::flattenOptions($this->getOptions());

        $selected_options = [];
        foreach ($items as $item) {
            $value = $item->class_id;
            if (isset($flat_options[$value])) {
                $selected_options[] = $value;
            }
        }

        return $selected_options;
    }

    public function display_inline(array &$element)
    {
        $inline = $this->getFieldSetting('inline_columns');
        if (empty($inline) || $inline < 2) {
            return $element;
        }
        if (count($element['#options']) > 0) {
            $element['#attached']['library'][] = 'tpedu/tpedu_fields';
            $column = 0;
            foreach ($element['#options'] as $key => $choice) {
                if ($key === 0) {
                    $key = '0';
                }
                $style = ($column % $inline) ? 'button-columns' : 'button-columns-clear';
                $element[$key]['#prefix'] = '<div class="'.$style.'">';
                $element[$key]['#suffix'] = '</div>';
                ++$column;
            }
        }

        return $element;
    }

    protected function getStudentOptions(array $settings, $myclass)
    {
        $values = array();
        $students = array();
        if ($settings['filter_by_class']) {
            $students = get_students_of_class($myclass);
            foreach ($students as $s) {
                $values[$s->id] = $s->seat.' '.$s->realname;
            }
        }

        return $values;
    }

    protected function getTeacherOptions(array $settings, $myclass)
    {
        $values = array();
        $teachers = array();
        if ($settings['filter_by_class']) {
            $teachers = get_teachers_of_class($myclass);
            foreach ($teachers as $t) {
                $values[$t->id] = $t->role_name.' '.$t->realname;
            }
        }

        return $values;
    }

    public function reload_class_ajax_callback(array &$form, FormStateInterface $form_state)
    {
        $commands = array();
        $element = $form_state['triggering_element'];
        $field_name = $element['#field_name'];
        $langcode = $element['#language'];
        $delta = $element['#delta'];
        $class = $element['#value'];
        foreach ($form_state['field'] as $my_field_name => $parent_field) {
            $my_field = $parent_field[$langcode]['field'];
            if ($my_field['type'] == 'tpedu_classes') {
                $current = $form_state['values'][$my_field_name][$langcode];
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
                    if ($my_field['type'] == 'tpedu_students') {
                        $options = $this->getStudentOptions($my_instance['settings'], $current);
                    } else {
                        $options = $this->getTeacherOptions($my_instance['settings'], $current);
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
                        $my_element = display_inline($my_element);
                    }
                    $element_id = 'edit-'.str_replace('_', '-', $my_field_name);
                    $commands[] = ajax_command_replace("#$element_id div", drupal_render($my_element));
                }
            }
        }

        return array('#type' => 'ajax', '#commands' => $commands);
    }
}
