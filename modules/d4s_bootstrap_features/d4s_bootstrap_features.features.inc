<?php
/**
 * @file
 * d4s_bootstrap_features.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function d4s_bootstrap_features_ctools_plugin_api($module = NULL, $api = NULL) {
  if ($module == "quicktabs" && $api == "quicktabs") {
    return array("version" => "1");
  }
}

/**
 * Implements hook_views_api().
 */
function d4s_bootstrap_features_views_api($module = NULL, $api = NULL) {
  return array("api" => "3.0");
}

/**
 * Implements hook_node_info().
 */
function d4s_bootstrap_features_node_info() {
  $items = array(
    'article' => array(
      'name' => t('文章'),
      'base' => 'node_content',
      'description' => t('使用<em>文章</em>於對時間敏感的內容如新聞、新聞稿或部落格文章。'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'booking_system_content_type' => array(
      'name' => t('booking_system_content_type'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'footer_calendar_content_type' => array(
      'name' => t('footer_calendar_content_type'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'footer_gallery_content_type' => array(
      'name' => t('footer_gallery_content_type'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'footer_other_web_content_type' => array(
      'name' => t('footer_other_web_content_type'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'owl_carousel' => array(
      'name' => t('owl_carousel'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
    'page' => array(
      'name' => t('基本頁面'),
      'base' => 'node_content',
      'description' => t('使用<em>基本頁面</em>於您的靜態內容，如  \'關於我們\' 頁面。'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  drupal_alter('node_info', $items);
  return $items;
}
