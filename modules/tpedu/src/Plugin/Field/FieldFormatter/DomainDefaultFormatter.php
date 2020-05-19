<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "domain_default",
 *   label = "領域",
 *   field_types = {
 *     "tpedu_domain"
 *   },
 * )
 */
class DomainDefaultFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = array();
        foreach ($items as $delta => $item) {
            $domains = explode(',', $item->domain);
            foreach ($domains as $g) {
                $domain_list .= $g.' ';
            }
            $source = array(
                '#type' => 'inline_template',
                '#template' => '領域： {{name}}',
                '#context' => [
                    'name' => $domain_list,
                ],
            );
            $elements[$delta] = array('#markup' => drupal_render($source));
        }

        return $elements;
    }
}
