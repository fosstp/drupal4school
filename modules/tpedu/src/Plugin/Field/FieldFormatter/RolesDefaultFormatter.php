<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "roles_default",
 *   label = "職務",
 *   field_types = {
 *     "tpedu_roles"
 *   },
 * )
 */
class RolesDefaultFormatter extends FormatterBase
{
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = '顯示工作職稱';

        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];
        foreach ($items as $delta => $item) {
            $role_name = '';
            $role = get_role($item->value);
            if ($role) {
                $role_name = $role->name;
            } else {
                $role_name = $item->value;
            }
            $elements[$delta] = ['#markup' => $role_name];
        }

        return $elements;
    }
}
