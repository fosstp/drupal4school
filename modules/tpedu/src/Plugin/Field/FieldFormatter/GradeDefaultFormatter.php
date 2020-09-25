<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "grade_default",
 *   label = "年級",
 *   field_types = {
 *     "tpedu_grade"
 *   },
 * )
 */
class GradeDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $grade_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $grades = explode(',', $item->grade);
            foreach ($grades as $g) {
                $grade_list .= $g.' ';
            }
            $elements[$delta] = ['#markup' => $grade_list];
        }

        return $elements;
    }
}
