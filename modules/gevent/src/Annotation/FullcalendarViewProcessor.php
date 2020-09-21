<?php

namespace Drupal\gevent\Annotation;

use Drupal\Component\Annotation\Plugin;

class FullcalendarViewProcessor extends Plugin
{
    /**
     * The plugin ID.
     *
     * @var string
     */
    public $id;

    /**
     * The label of the plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $label;

    /**
     * supported field types.
     *
     * @var array
     */
    public $field_types;
}
