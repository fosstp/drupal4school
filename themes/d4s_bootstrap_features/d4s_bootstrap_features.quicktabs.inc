<?php
/**
 * @file
 * d4s_bootstrap_features.quicktabs.inc
 */

/**
 * Implements hook_quicktabs_default_quicktabs().
 */
function d4s_bootstrap_features_quicktabs_default_quicktabs() {
  $export = array();

  $quicktabs = new stdClass();
  $quicktabs->disabled = FALSE; /* Edit this to true to make a default quicktabs disabled initially */
  $quicktabs->api_version = 1;
  $quicktabs->machine_name = 'tabs_test';
  $quicktabs->ajax = 0;
  $quicktabs->hide_empty_tabs = FALSE;
  $quicktabs->default_tab = 0;
  $quicktabs->title = '校園公告';
  $quicktabs->tabs = array(
    0 => array(
      'vid' => 'news',
      'display' => 'default',
      'args' => '',
      'title' => '最新消息',
      'weight' => '-100',
      'type' => 'view',
    ),
    1 => array(
      'vid' => 'news',
      'display' => 'default',
      'args' => '',
      'title' => '研習資訊',
      'weight' => '-99',
      'type' => 'view',
    ),
    2 => array(
      'vid' => 'news',
      'display' => 'default',
      'args' => '',
      'title' => '榮譽榜',
      'weight' => '-98',
      'type' => 'view',
    ),
  );
  $quicktabs->renderer = 'bootstrap_tabs';
  $quicktabs->style = 'default';
  $quicktabs->options = array(
    'tabstyle' => 'tabs',
    'tabposition' => 'basic',
    'tabeffects' => array(
      'fade' => 'fade',
    ),
  );

  // Translatables
  // Included for use with string extractors like potx.
  t('最新消息');
  t('校園公告');
  t('榮譽榜');
  t('研習資訊');

  $export['tabs_test'] = $quicktabs;

  return $export;
}
