<?php

function drupal4school_profile_details()
{
    return array(
    'name' => '臺北市Drupal校園網站架站包計畫',
    'description' => '提供臺北市學校官方網站架站所需模組。',
    'language' => 'zh-hant',
  );
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function system_form_install_settings_form_alter(&$form, $form_state)
{
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
function drupal4school_form_install_configure_form_alter(&$form, $form_state)
{
    $form['site_infomation']['site_name']['#default_value'] = $_ENV['SITE_NAME'];
    $form['site_infomation']['site_mail']['#default_value'] = $_ENV['SITE_MAIL'];
    $form['admin_account']['account_name']['#default_value'] = $_ENV['SITE_ADMIN'];
    $form['admin_account']['account_pass']['account_pass_pass1']['#default_value'] = $_ENV['SITE_PASSWORD'];
    $form['admin_account']['account_pass']['account_pass_pass2']['#default_value'] = $_ENV['SITE_PASSWORD'];
    $form['admin_account']['account_mail']['#default_value'] = $_ENV['SITE_ADMIN_MAIL'];
    $form['regional_settings']['site_default_country']['#default_value'] = 'TW';
    $form['regional_settings']['date_default_timezone']['#default_value'] = 'Asia/Taipei';
}
