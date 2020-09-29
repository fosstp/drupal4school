<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "units_default",
 *   label = "行政單位",
 *   field_types = {
 *     "tpedu_units"
 *   },
 * )
 */
class UnitsDefaultFormatter extends FormatterBase
{
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示行政單位（處室）名稱';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $unit_name = '';
            $unit = get_unit($item->value);
            if ($unit) {
                $unit_name = $unit->name;
            } else {
                $unit_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $unit_name];
        }

        return $elements;
    }
}
