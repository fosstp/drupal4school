<?php

namespace Drupal\tpedu\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

class TpeduWidgetBase extends WidgetBase
{
    protected $column;

    public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings)
    {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
        $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();
        $this->column = $property_names[0];
    }

    public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = null)
    {
        $field_name = $this->fieldDefinition->getName();
        $parents = $form['#parents'];

        // Store field information in $form_state.
        $field_state = [
            'items_count' => count($items),
            'array_parents' => [],
            'field_type' => $this->fieldDefinition->getType(),
            'field_settings' => $this->fieldDefinition->getSettings(),
        ];
        static::setWidgetState($parents, $field_name, $form_state, $field_state);

        // Collect widget elements.
        $elements = [];
        $delta = isset($get_delta) ? $get_delta : 0;
        $element = [
            '#title' => $this->fieldDefinition->getLabel(),
            '#description' => FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription())),
        ];
        $element = $this->formElement($items, $delta, $element, $form, $form_state);
        if ($element) {
            if (isset($get_delta)) {
                $elements[$delta] = $element;
            } else {
                $elements = $element;
            }
        }
        $elements['#after_build'][] = [
            get_class($this),
            'afterBuild',
        ];
        $elements['#field_name'] = $field_name;
        $elements['#field_parents'] = $parents;
        $elements['#parents'] = array_merge($parents, [
            $field_name,
        ]);

        // Most widgets need their internal structure preserved in submitted values.
        $elements += [
            '#tree' => true,
        ];

        return [
            '#type' => 'container',
            '#parents' => array_merge($parents, [
                $field_name.'_wrapper',
            ]),
            '#attributes' => [
                'class' => [
                    'field--type-'.Html::getClass($this->fieldDefinition->getType()),
                    'field--name-'.Html::getClass($field_name),
                    'field--widget-'.Html::getClass($this->getPluginId()),
                ],
            ],
            'widget' => $elements,
        ];
    }

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element['#delta'] = $delta;
        $element['#weight'] = $delta;
        $element['#key_column'] = $this->column;
        $options = $this->getOptions();
        $element['#options'] = $options;
        $this->required = $this->fieldDefinition->isRequired();
        $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
        $this->has_value = isset($items[0]->{$this->column});
        if ($this->multiple) {
            $element['#type'] = 'checkboxes';
            $inline = $this->getFieldSetting('inline_columns');
            $this->display_inline($element, $inline);
            $element['#default_value'] = $this->getSelectedOptions($items);
        } else {
            if (!$this->required) {
                $element['#empty_option'] = '- 選取 -';
                $element['#empty_value'] = '_none';
            }
            $element['#type'] = 'select';
            $value = isset($items[$delta]->{$this->column}) ? $items[$delta]->{$this->column} : '';
            if ($value) {
                $element['#default_value'] = $value;
            }
        }
        $element['#required'] = $this->required;
        // Allow modules to alter the field widget form element.
        $context = [
              'form' => $form,
              'widget' => $this,
              'items' => $items,
              'delta' => $delta,
              'default' => $this->isDefaultValueWidget($form_state),
            ];
        \Drupal::moduleHandler()->alter(['field_widget_form', 'field_widget_'.$this->getPluginId().'_form'], $element, $form_state, $context);

        return $element;
    }

    public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state)
    {
        $field_name = $this->fieldDefinition->getName();

        // Extract the values from $form_state->getValues().
        $path = array_merge($form['#parents'], [$field_name]);
        $key_exists = null;
        $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
        if (!is_array($values)) {
            $values = [$values];
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

    protected function getSelectedOptions(FieldItemListInterface $items)
    {
        $flat_options = OptGroup::flattenOptions($this->getOptions());

        $selected_options = [];
        foreach ($items as $item) {
            $value = $item->{$this->column};
            if (isset($flat_options[$value])) {
                $selected_options[] = $value;
            }
        }

        return $selected_options;
    }

    public function display_inline(array &$element, $inline = null)
    {
        if (empty($inline)) {
            $inline = count($element['#options']);
        }
        if ($inline > 0) {
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
}
