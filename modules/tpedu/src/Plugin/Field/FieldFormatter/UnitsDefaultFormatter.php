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
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $unit_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $units = explode(',', $item->dept_id);
            foreach ($units as $o) {
                $unit = get_unit($o);
                if ($unit) {
                    $unit_list .= $unit->name.' ';
                }
            }
            $source = [
                '#type' => 'inline_template',
                '#template' => '{{name}}',
                '#context' => [
                    'name' => $unit_list,
                ],
            ];
            $elements[$delta] = ['#markup' => \Drupal::service('renderer')->render($source)];
        }

        return $elements;
    }
}
