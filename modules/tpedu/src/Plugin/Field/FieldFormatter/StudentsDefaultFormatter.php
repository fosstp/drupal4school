<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

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
        $elements = array();
        foreach ($items as $delta => $item) {
            $students = explode(',', $item->uuid);
            foreach ($students as $s) {
                $user = get_user($s);
                $student_list .= $user->class.$user->seat.$user->realname.' ';
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '學生： {{name}}',
                '#context' => [
                    'name' => $student_list,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
