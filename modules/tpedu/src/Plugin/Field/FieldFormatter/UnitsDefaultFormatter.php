<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "units_default",
 *   label = "選擇行政單位",
 *   field_types = {
 *     "tpedu_units"
 *   },
 * )
 */
class UnitsDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();
        foreach ($items as $delta => $item) {
            $units = explode(',', $item->dept_id);
            foreach ($units as $o) {
                $unit = get_unit($o);
                if ($unit) {
                    $title .= $unit->name.' ';
                }
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '單位： {{name}}',
                '#context' => [
                    'name' => $title,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
