<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "teachers_default",
 *   label = "教師",
 *   field_types = {
 *     "tpedu_teachers"
 *   },
 * )
 */
class TeachersDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $teachers = explode(',', $item->uuid);
            foreach ($teachers as $s) {
                $user = get_user($s);
                $teacher_list .= $user->role_name.$user->realname.' ';
            }
            $source = [
                '#type' => 'inline_template',
                '#template' => '教師： {{name}}',
                '#context' => [
                    'name' => $teacher_list,
                ],
            ];
            $elements[$delta] = ['#markup' => \Drupal::service('renderer')->render($source)];
        }

        return $elements;
    }
}
