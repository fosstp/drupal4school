<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_grade",
 *   label = "年級",
 *   description = "年級選單",
 *   category = "臺北市教育人員",
 *   default_widget = "grade_default",
 *   default_formatter = "list_default"
 * )
 */
class Grade extends FieldItemBase
{
    public static function schema(FieldStorageDefinitionInterface $field)
    {
        return array(
          'columns' => array(
            'grade' => array(
              'type' => 'varchar_ascii',
              'length' => 50,
              'not null' => false,
            ),
          ),
        );
    }

    public function isEmpty()
    {
        return empty($this->get('grade')->value);
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['grade'] = DataDefinition::create('string')->setLabel('年級');

        return $properties;
    }
}
