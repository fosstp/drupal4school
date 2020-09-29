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
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示班級名稱';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $class_name = '';
            $myclass = one_class($item->value);
            if ($myclass) {
                $class_name = $myclass->name;
            } else {
                $class_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $class_name];
        }

        return $elements;
    }
}
