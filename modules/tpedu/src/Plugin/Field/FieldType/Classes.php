<?php

namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_classes",
 *   label = "班級",
 *   description = "班級列表",
 *   category = "臺北市教育人員",
 *   default_widget = "classes_default",
 *   default_formatter = "classes_default"
 * )
 */
class Classes extends FieldItemBase {

    public static function schema(FieldStorageDefinitionInterface $field) {
        return array(
          'columns' => array(
            'class_id' => array(
              'type' => 'varchar_ascii',
              'length' => 50,
              'not null' => true,
            ),
          ),
        );
    }

    public function isEmpty() {
        return empty($this->get('class_id')->value);
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['class_id'] = DataDefinition::create('string')->setLabel('班級代號');
        return $properties;
    }

}