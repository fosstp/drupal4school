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
        $config = \Drupal::config('tpedu.settings');
        $teacher_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $teachers = explode(',', $item->uuid);
            foreach ($teachers as $s) {
                $user = get_user($s);
                $prefix = '';
                if ($config->get('display_unit')) {
                    $prefix = $user->dept_name;
                }
                if ($config->get('display_title')) {
                    $prefix .= $user->role_name;
                }
                $teacher_list .= $prefix.$user->realname.' ';
            }
            $elements[$delta] = ['#markup' => $teacher_list];
        }

        return $elements;
    }
}
