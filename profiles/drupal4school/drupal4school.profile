<?php
function drupal4school_profile_details() {
  return array(
    'name' => 'drupal for school',
    'description' => 'supply additional modules for taipei school.',
    'language' => 'zh-hant',
  );
}

function drupal4school_install_tasks($install_state) {
  $mytask = array(
    'select_custom_modules' => array(
      'display_name' => st('select custom modules'),
      'display' => TRUE,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'enable_custom_modules' => array(
      'display_name' => st('enable custom modules'),
      'display' => TRUE,
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'import_custom_modules_locales' => array(
      'display_name' => st('import custom modules translations'),
      'display' => TRUE,
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'final_step' => array(),
  );

  return $mytask;
}

function select_custom_modules($form, &$form_state, &$install_state) {
  drupal_set_title(st('select custom modules'));
  $form['enable_modules']['sims'] = array(
    '#type' => 'radio',
    '#return_value' => 'sims',
    '#default_value' =>  'sims',
    '#title' => st('include primary school sims modules.'),
    '#parents' => array('enable_modules')
  );
  $form['enable_modules']['not_sims'] = array(
    '#type' => 'radio',
    '#return_value' => 'not_sims',
    '#default_value' =>  '',
    '#title' => st('do not include primary school sims modules.'),
    '#parents' => array('enable_modules')
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
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
    $modules = array("thumbnail_link", "simsauth", "sims_field", "sims_views", "adsync", "gapps", "gevent", "db2health");
  }
  else {
    $modules = array("thumbnail_link");
  }
  module_enable($modules, FALSE);
  
  $files = system_rebuild_module_data();
  $operations = array();
  foreach ($modules as $module) {
    $operations[] = array('module_enable', array($module), FALSE);
  }
  $batch = array(
    'operations' => $operations,
    'title' => st('enable custom modules'),
    'error_message' => st('The installation has encountered an error.'),
  );
  return $batch;
}

function import_custom_modules_locales(&$install_state) {
  module_load_include('compare.inc', 'l10n_update');
//  l10n_update_flush_projects();
  l10n_update_check_projects();
  module_load_include('fetch.inc', 'l10n_update');
  $options['overwrite_options']['not_customized'] = TRUE;
  $options['overwrite_options']['customized'] = TRUE;
  $batch = l10n_update_batch_update_build(array(), array('zh-hant'), $options);
  return $batch;
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
    $form['settings'][$key]['username']['#default_value'] = 'root';
    $form['settings'][$key]['password']['#type'] = 'textfield';
    $form['settings'][$key]['password']['#default_value'] = (isset($_ENV['DATABASE_PASSWORD'])) ? $_ENV['DATABASE_PASSWORD'] : '';
    if ($key == 'mysql') {
      $form['settings'][$key]['charset']['#type'] = 'hidden';
      $form['settings'][$key]['charset']['#default_value'] = 'utf8mb4';
    }
    $form['settings'][$key]['advanced_options']['host']['#default_value'] = 'db';
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
