<?php

namespace Drupal\tpedunews\Plugin\Block;

use Drupal\aggregator\FeedStorageInterface;
use Drupal\aggregator\ItemStorageInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "tpedunews_block",
 *   admin_label = "教育局最新消息",
 *   category = @Translation("Lists (Views)")
 * )
 */
class TpedunewsBlock extends BlockBase implements ContainerFactoryPluginInterface
{
    protected $feedStorage;
    protected $itemStorage;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, FeedStorageInterface $feed_storage, ItemStorageInterface $item_storage)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->feedStorage = $feed_storage;
        $this->itemStorage = $item_storage;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager')->getStorage('aggregator_feed'),
            $container->get('entity_type.manager')->getStorage('aggregator_item')
        );
    }

    public function defaultConfiguration()
    {
        return [
            'block_count' => 10,
        ];
    }

    protected function blockAccess(AccountInterface $account)
    {
        return AccessResult::allowedIfHasPermission($account, 'access news feeds');
    }

    public function blockForm($form, FormStateInterface $form_state)
    {
        $range = range(2, 20);
        $form['block_count'] = [
            '#type' => 'select',
            '#title' => $this->t('Number of news items in block'),
            '#default_value' => $this->configuration['block_count'],
            '#options' => array_combine($range, $range),
        ];

        return $form;
    }

    public function blockSubmit($form, FormStateInterface $form_state)
    {
        $this->configuration['block_count'] = $form_state->getValue('block_count');
    }

    public function feeds()
    {
        return $this->feedStorage->getQuery()
            ->condition('title', '教育局%', 'like')
            ->execute();
    }

    public function build()
    {
        $feeds = $this->feeds();
        $build['feed'] = array(
            '#type' => 'horizontal_tabs',
            '#default_tab' => 'edit-feed'.$feeds[0]->id(),
        );
        foreach ($feeds as $feed) {
            $build['feed']['feed'.$feed->id()] = array(
                '#type' => 'details',
                '#title' => $feed->label(),
                '#group' => 'feed',
            );
            $result = $this->itemStorage->getQuery()
                    ->condition('fid', $feed->id())
                    ->range(0, $this->configuration['block_count'])
                    ->sort('timestamp', 'DESC')
                    ->sort('iid', 'DESC')
                    ->execute();
            if ($result) {
                $items = $this->itemStorage->loadMultiple($result);
                $build['feed']['feed'.$feed->id()]['items'] = array(
                    '#theme' => 'tpedunews_block',
                    '#items' => $items,
                    '#more' => $feed->toUrl(),
                );
            }
        }

        return $build;
    }

    public function getCacheTags()
    {
        $cache_tags = parent::getCacheTags();
        $feeds = $this->feeds();
        foreach ($feeds as $feed) {
            $cache_tags = Cache::mergeTags($cache_tags, $feed->getCacheTags());
        }

        return $cache_tags;
    }
}
