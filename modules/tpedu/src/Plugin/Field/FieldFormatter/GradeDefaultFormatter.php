<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "classes_default",
 *   label = "年級",
 *   field_types = {
 *     "tpedu_grade"
 *   },
 * )
 */
class ClassesDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();
        foreach ($items as $delta => $item) {
            $grades = explode(',', $item->grade);
            foreach ($grades as $g) {
                $grade_list .= $g.' ';
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '年級： {{name}}',
                '#context' => [
                    'name' => $grade_list,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
