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
        $config = \Drupal::config('tpedu.settings');
        $student_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $students = explode(',', $item->uuid);
            foreach ($students as $s) {
                $user = get_user($s);
                $prefix = '';
                if ($config->get('display_unit') || $config->get('display_title')) {
                    $prefix = $user->dept_name.' ';
                }
                $student_list .= $prefix.$user->seat.'號'.$user->realname.' ';
            }
            $elements[$delta] = ['#markup' => $student_list];
        }

        return $elements;
    }
}
