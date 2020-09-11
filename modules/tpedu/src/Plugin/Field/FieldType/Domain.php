<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_domain",
 *   label = "領域",
 *   module ="tpedu",
 *   description = "領域選單",
 *   category = "臺北市校園",
 *   default_widget = "domain_default",
 *   default_formatter = "domain_default",
 * )
 */
class Domain extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return [
          'columns' => [
            'domain' => [
                'type' => 'varchar_ascii',
                'length' => 50,
                'not null' => false,
            ],
          ],
        ];
    }

    public function isEmpty()
    {
        $value = $this->get('domain')->getValue();

        return $value === null || $value === '';
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['domain'] = DataDefinition::create('string')->setLabel('領域');

        return $properties;
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = [];
        $element['extra_info'] = [
            '#markup' => '<p>此欄位可以單獨使用或結合科目、教師欄位使用！結合科目欄位時，可用於選取不同領域的科目；結合教師欄位時，可用於選取不同領域的教師！</p>',
        ];

        return $element;
    }
}
