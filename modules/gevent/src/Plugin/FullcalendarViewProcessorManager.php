<?php

namespace Drupal\gevent\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Fullcalendar view processor plugin manager.
 */
class FullcalendarViewProcessorManager extends DefaultPluginManager
{
    /**
     * Constructs a new FullcalendarViewProcessorManager object.
     *
     * @param \Traversable                                  $namespaces
     *                                                                      An object that implements \Traversable which contains the root paths
     *                                                                      keyed by the corresponding namespace to look for plugin implementations
     * @param \Drupal\Core\Cache\CacheBackendInterface      $cache_backend
     *                                                                      Cache backend instance to use
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *                                                                      The module handler to invoke the alter hook with
     */
    public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/FullcalendarViewProcessor', $namespaces, $module_handler, 'Drupal\gevent\Plugin\FullcalendarViewProcessorInterface', 'Drupal\gevent\Annotation\FullcalendarViewProcessor');

        $this->alterInfo('gevent_fullcalendar_view_processor_info');
        $this->setCacheBackend($cache_backend, 'gevent_fullcalendar_view_processor_plugins');
    }
}
