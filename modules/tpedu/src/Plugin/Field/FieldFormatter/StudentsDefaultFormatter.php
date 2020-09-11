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
            $source = [
                '#type' => 'inline_template',
                '#template' => '{{name}}',
                '#context' => [
                    'name' => $student_list,
                ],
            ];
            $elements[$delta] = ['#markup' => \Drupal::service('renderer')->render($source)];
        }

        return $elements;
    }
}
