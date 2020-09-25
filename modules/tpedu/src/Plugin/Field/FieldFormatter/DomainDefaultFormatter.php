<?php

namespace Drupal\tpedu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

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
        $domain_list = '';
        $elements = [];
        foreach ($items as $delta => $item) {
            $domains = explode(',', $item->domain);
            foreach ($domains as $g) {
                $domain_list .= $g.' ';
            }
            $elements[$delta] = ['#markup' => $domain_list];
        }

        return $elements;
    }
}
