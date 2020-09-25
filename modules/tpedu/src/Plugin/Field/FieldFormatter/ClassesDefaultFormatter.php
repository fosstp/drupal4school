<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "classes_default",
 *   label = "班級",
 *   field_types = {
 *     "tpedu_classes"
 *   },
 * )
 */
class ClassesDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $class_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $classes = explode(',', $item->class_id);
            foreach ($classes as $c) {
                $myclass = one_class($c);
                if ($myclass) {
                    $class_list .= $myclass->name.' ';
                }
            }
            $elements[$delta] = ['#markup' => $class_list];
        }

        return $elements;
    }
}
