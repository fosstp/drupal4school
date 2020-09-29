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
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示教師姓名（從模組設定可以修改顯示方式）';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $config = \Drupal::config('tpedu.settings');
        $elements = [];
        foreach ($items as $delta => $item) {
            $teacher_name = '';
            $user = get_user($s);
            if ($user) {
                $prefix = '';
                if ($config->get('display_unit')) {
                    $prefix .= $user->dept_name;
                }
                if ($config->get('display_title')) {
                    $prefix .= $user->role_name;
                }
                $teacher_name = $prefix.$user->realname;
            } else {
                $teacher_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $teacher_name];
        }

        return $elements;
    }
}
