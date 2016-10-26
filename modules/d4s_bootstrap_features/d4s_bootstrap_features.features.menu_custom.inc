<?php
/**
 * @file
 * d4s_bootstrap_features.features.menu_custom.inc
 */

/**
 * Implements hook_menu_default_menu_custom().
 */
function d4s_bootstrap_features_menu_default_menu_custom() {
  $menus = array();

  // Exported menu: main-menu.
  $menus['main-menu'] = array(
    'menu_name' => 'main-menu',
    'title' => '主選單',
    'description' => '<em>主</em>選單在多數網站用於顯示網站的主要部分，往往位於頂部導航欄。',
  );
  // Translatables
  // Included for use with string extractors like potx.
  t('<em>主</em>選單在多數網站用於顯示網站的主要部分，往往位於頂部導航欄。');
  t('主選單');

  return $menus;
}
