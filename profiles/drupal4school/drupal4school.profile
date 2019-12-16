<?php
function drupal4school_profile_details() {
  return array(
    'name' => '臺北市Drupal校園網站架站包計畫',
    'description' => '提供臺北市學校官方網站架站所需模組。',
    'language' => 'zh-hant',
  );
}

function drupal4school_install_tasks($install_state) {
  $mytask = array(
    'select_custom_modules' => array(
      'display_name' => '選擇自訂模組',
      'display' => TRUE,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'enable_custom_modules' => array(
      'display_name' => '啟用自訂模組',
      'display' => TRUE,
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'final_step' => array("final_step"),
  );

  return $mytask;
}

function select_custom_modules($form, &$form_state, &$install_state) {
  drupal_set_title(st('選擇自訂模組'));
  $form['enable_modules']['sims'] = array(
    '#type' => 'radio',
    '#return_value' => 'sims',
    '#default_value' =>  'sims',
    '#title' => '包含單一身份驗證相關模組',
    '#parents' => array('enable_modules'),
  );
  $form['enable_modules']['not_sims'] = array(
    '#type' => 'radio',
    '#return_value' => 'not_sims',
    '#default_value' =>  '',
    '#title' => '不要包含單一身份驗證相關模組',
    '#parents' => array('enable_modules'),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save and continue'),
    '#submit' => array("select_custom_modules_submit"),
  );
  
  return $form;
}

function select_custom_modules_submit($form, &$form_state) {
  variable_set("drupal4school_enable_modules", $form_state['values']['enable_modules']);
}

function enable_custom_modules(&$install_state) {
  $flag = variable_get("drupal4school_enable_modules");
  if ($flag == 'sims') {
    $modules = array("thumbnail_link", "tpedu");
  }
  else {
    $modules = array("thumbnail_link");
  }
  $files = system_rebuild_module_data();

  $operations = array();
  $theme = array(
    'theme_default' => 'bootstrap4',
//    'admin_theme' => 'eight',
  );
  $operations[] = array('theme_enable', array($theme));
  foreach ($modules as $module) {
    $operations[] = array('_module_enable', array($module));
  }
  $batch = array(
    'operations' => $operations,
    'title' => '啟用自訂模組',
    'error_message' => t('The installation has encountered an error.'),
  );
  return $batch;
}

function _module_enable($module, &$content) {
//    $content['message'] = t('已經成功啟用 @module 模組。', array('@module' => $module));
//    module_enable(array($module), FALSE);
}

function final_step() {
  variable_del("drupal4school_enable_modules");
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function system_form_install_settings_form_alter(&$form, $form_state) {
  foreach ($form['settings'] as $key => $driver) {
    $form['settings'][$key]['database']['#default_value'] = 'drupal';
    $form['settings'][$key]['username']['#default_value'] = $_ENV['DB_USER'];
    $form['settings'][$key]['password']['#type'] = 'textfield';
    $form['settings'][$key]['password']['#default_value'] = (isset($_ENV['DB_PASSWORD'])) ? $_ENV['DB_PASSWORD'] : '';
    if ($key == 'mysql') {
      $form['settings'][$key]['charset']['#type'] = 'hidden';
      $form['settings'][$key]['charset']['#default_value'] = 'utf8mb4';
    }
    $form['settings'][$key]['advanced_options']['host']['#default_value'] = $_ENV['DB_HOST'];
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function drupal4school_form_install_configure_form_alter(&$form, $form_state) {
  $form['admin_account']['account']['name']['#default_value'] = 'admin';
  $form['server_settings']['site_default_country']['#default_value'] = 'TW';
  $javascript = &drupal_static('drupal_add_js', array());
  unset($javascript['misc/timezone.js']);
  $form['server_settings']['date_default_timezone']['#default_value'] = 'Asia/Taipei';
}
