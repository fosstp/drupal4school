<?php

namespace Drupal\gevent\Plugin;

use Drupal\Component\Plugin\PluginBase;

abstract class GeventViewProcessorBase extends PluginBase implements GeventViewProcessorInterface
{
    abstract public function process(array &$variables);
}
