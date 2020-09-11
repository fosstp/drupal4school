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
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $student_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $students = explode(',', $item->uuid);
            foreach ($students as $s) {
                $user = get_user($s);
                $student_list .= $user->class.$user->seat.$user->realname.' ';
            }
            $source = [
                '#type' => 'inline_template',
                '#template' => '學生： {{name}}',
                '#context' => [
                    'name' => $student_list,
                ],
            ];
            $elements[$delta] = ['#markup' => \Drupal::service('renderer')->render($source)];
        }

        return $elements;
    }
}
