<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "subjects_default",
 *   label = "科目",
 *   field_types = {
 *     "tpedu_subjects"
 *   },
 * )
 */
class SubjectsDefaultFormatter extends FormatterBase
{
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示科目名稱';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $subject_name = '';
            $subj = get_subject($item->value);
            if ($subj) {
                $subject_name = $subj->name;
            } else {
                $subject_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $subject_name];
        }

        return $elements;
    }
}
