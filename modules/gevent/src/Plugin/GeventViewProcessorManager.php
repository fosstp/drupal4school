<?php

namespace Drupal\gevent\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class GeventViewProcessorManager extends DefaultPluginManager
{
    /**
     * @param Traversable            $namespaces
     *                                               An object that implements \Traversable which contains the root paths
     *                                               keyed by the corresponding namespace to look for plugin implementations
     * @param CacheBackendInterface  $cache_backend
     *                                               Cache backend instance to use
     * @param ModuleHandlerInterface $module_handler
     *                                               The module handler to invoke the alter hook with
     */
    public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler)
    {
        parent::__construct('Plugin/GeventViewProcessor', $namespaces, $module_handler, 'Drupal\gevent\Plugin\GeventViewProcessorInterface', 'Drupal\gevent\Annotation\GeventViewProcessor');

        $this->alterInfo('gevent_view_processor_info');
        $this->setCacheBackend($cache_backend, 'gevent_view_processor_plugins');
    }
}
