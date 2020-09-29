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
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示年級';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $grade_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            switch ($item->value) {
                case 1:
                    $name = '一年級';
                    break;
                case 2:
                    $name = '二年級';
                    break;
                case 3:
                    $name = '三年級';
                    break;
                case 4:
                    $name = '四年級';
                    break;
                case 5:
                    $name = '五年級';
                    break;
                case 6:
                    $name = '六年級';
                    break;
                default:
                    $name = $item->value;
            }
            $elements[$delta] = ['#markup' => $name];
        }

        return $elements;
    }
}
