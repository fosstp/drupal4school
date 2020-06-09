<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

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
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();
        foreach ($items as $delta => $item) {
            $roles = explode(',', $item->role_id);
            foreach ($roles as $r) {
                $role = get_role($r);
                if ($role) {
                    $title .= $role->name.' ';
                }
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '職務： {{name}}',
                '#context' => [
                    'name' => $title,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
