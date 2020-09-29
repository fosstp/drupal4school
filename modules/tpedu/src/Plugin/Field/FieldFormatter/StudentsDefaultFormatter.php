<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "students_default",
 *   label = "學生",
 *   field_types = {
 *     "tpedu_students"
 *   },
 * )
 */
class StudentsDefaultFormatter extends FormatterBase
{
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示學生年班座號和姓名';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $student_name = '';
            $user = get_user($item->value);
            if ($user) {
                $student_name = $user->dept_name.$user->seat.'號'.$user->realname;
            } else {
                $student_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $student_name];
        }

        return $elements;
    }
}
