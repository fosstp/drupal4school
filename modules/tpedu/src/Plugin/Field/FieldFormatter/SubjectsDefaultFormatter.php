<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

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
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();
        foreach ($items as $delta => $item) {
            $subjects = explode(',', $item->subject_id);
            foreach ($subjects as $s) {
                $subj = get_subject($s);
                $subject_list .= $subj->name.' ';
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '科目： {{name}}',
                '#context' => [
                    'name' => $subject_list,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
