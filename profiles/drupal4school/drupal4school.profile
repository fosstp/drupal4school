<?php
function drupal4school_install_tasks($install_state) {
  $mytask['enable_custom_modules_form'] = array(
    'display_name' => st('enable custom modules'),
    'display' => TRUE,
    'type' => 'form',
    'run' => INSTALL_TASK_RUN_IF_REACHED,
    'function' => 'drupal4school_enable_custom_modules_form',
  );
  return $mytask;
}

function drupal4school_enable_custom_modules_form($form, &$form_state) {
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
    '#submit' => array('drupal4school_enable_custom_modules'),
  );
  return $form;
}

function drupal4school_enable_custom_modules($form, &$form_state) {
   if ($form_state['values']['enable_modules'] == 'sims') {
     module_enable(array("thumbnail_link", "simsauth", "sims_field", "sims_views", "adsync", "gapps", "gevent", "db2health"), FALSE);
     l10n_update_system_update(array ("module" => array("thumbnail_link", "simsauth", "sims_field", "sims_views", "adsync", "gapps", "gevent", "db2health")));
   }
   else {
     module_enable(array("thumbnail_link"), FALSE);
     l10n_update_system_update(array ("module" => array("thumbnail_link")));
   }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function system_form_install_settings_form_alter(&$form, $form_state) {
  global $databases;
  $database['default']['default']['charset'] = 'utf8mb4';
  $database['default']['default']['collation'] = 'utf8mb4_general_ci';
  foreach ($form['settings'] as $key => $driver) {
    $form['settings'][$key]['username']['#default_value'] = 'root';
    $form['settings'][$key]['password']['#default_value'] = getenv('DATABASE_PASSWORD');
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
