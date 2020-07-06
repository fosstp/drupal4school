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
 * Provides an 'Aggregator feed' block with the latest items from the feed.
 *
 * @Block(
 *   id = "aggregator_feed_block",
 *   admin_label = @Translation("Aggregator feed"),
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

    public function build()
    {
        $feeds = $this->feedStorage->loadMultiple();
        $options = [];
        foreach ($feeds as $feed) {
            if (mb_substr($feed->label(), 0, 3) == '教育局') {
                $options[$feed->id()] = $feed->label();
            }
        }
        $build['feed'] = array(
            '#type' => 'select',
            '#title' => '消息分類',
            '#multiple' => false,
            '#options' => $options,
            '#size' => 1,
        );
        if ($options) {
            foreach (array_keys($options) as $fid) {
                $result = $this->itemStorage->getQuery()
                    ->condition('fid', $fid)
                    ->range(0, $this->configuration['block_count'])
                    ->sort('timestamp', 'DESC')
                    ->sort('iid', 'DESC')
                    ->execute();
                if ($result) {
                    $items = $this->itemStorage->loadMultiple($result);
                    $build['feed'.$fid] = array(
                        '#theme' => 'tpedunews_block',
                        '#items' => $items,
                        '#states' => array(
                            'visible' => array(
                                ':input[name="feed"]' => array('value' => $fid),
                            ),
                        ),
                    );
                }
                $build['more_link'] = [
                    '#type' => 'more_link',
                    '#url' => $feed->toUrl(),
                    '#attributes' => ['title' => $this->t("View this feed's recent news.")],
                ];

                return $build;
            }
        }
    }

    public function getCacheTags()
    {
        $cache_tags = parent::getCacheTags();
        foreach ($this->configuration['feeds'] as $fid) {
            if ($feed = $this->feedStorage->load($fid)) {
                $cache_tags = Cache::mergeTags($cache_tags, $feed->getCacheTags());
            }
        }

        return $cache_tags;
    }
}
