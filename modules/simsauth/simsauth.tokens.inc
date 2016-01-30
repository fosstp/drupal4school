<?php

/**
 * @file
 * Builds placeholder replacement tokens for realname.
 */

/**
 * Implements hook_token_info().
 */
function simsauth_token_info() {
  $info['tokens']['user']['username'] = array(
    'name' => t('Account name'),
    'description' => t('The raw login name of the user account, unprocessed by the simsauth module.'),
  );
  $info['tokens']['user']['gender'] = array(
    'name' => t('gender'),
    'description' => t('The gender of the user.'),
  );
  $info['tokens']['user']['userclass'] = array(
    'name' => t('User class'),
    'description' => t('The user is a teacher or a student, identify by the simsauth module.'),
  );
  $info['tokens']['user']['idno'] = array(
    'name' => t('Security id'),
    'description' => t('The taiwan security id of the user account.'),
  );
  $info['tokens']['user']['birthyear'] = array(
    'name' => t('Year of birth'),
    'description' => t('The year of birth.'),
  );
  $info['tokens']['user']['birthmonth'] = array(
    'name' => t('Month of birth'),
    'description' => t('The month of birth.'),
  );
  $info['tokens']['user']['birthday'] = array(
    'name' => t('Month day of birth'),
    'description' => t('The month day of birth.'),
  );
  $info['tokens']['user']['proclass'] = array(
    'name' => t('Adoption class'),
    'description' => t('The adoption class of the teacher.'),
  );
  $info['tokens']['user']['depname'] = array(
    'name' => t('Department name'),
    'description' => t('The department name of the teacher.'),
  );
  $info['tokens']['user']['titlename'] = array(
    'name' => t('Job title'),
    'description' => t('The job title of the teacher.'),
  );
  $info['tokens']['user']['stdno'] = array(
    'name' => t('Student id'),
    'description' => t('The id of the student.'),
  );
  $info['tokens']['user']['class'] = array(
    'name' => t('Attending class'),
    'description' => t('The class of the student.'),
  );
  $info['tokens']['user']['seat'] = array(
    'name' => t('Seat number'),
    'description' => t('The seat number of the student.'),
  );

  return $info;
}

/**
 * Implements hook_token_info_alter().
 */
function simsauth_token_info_alter(&$info) {
  $info['tokens']['user']['name']['name'] = t('Display name');
  $info['tokens']['user']['name']['description'] = t('The display name of the user account.');
}

/**
 * Implements hook_tokens().
 */
function simsauth_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  $sanitize = !empty($options['sanitize']);

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'username':
          $replacements[$original] = $sanitize ? check_plain($account->name) : $account->name;
          break;

        case 'realname':
          $replacements[$original] = $sanitize ? check_plain($account->realname) : $account->realname;
          break;

        case 'gender':
          $replacements[$original] = $sanitize ? check_plain($account->gender) : $account->gender;
          break;

        case 'userclass':
          $replacements[$original] = $sanitize ? check_plain($account->userclass) : $account->userclass;
          break;

        case 'idno':
          $replacements[$original] = $sanitize ? check_plain($account->idno) : $account->idno;
          break;

        case 'birthyear':
          $replacements[$original] = $sanitize ? check_plain($account->birthyear) : $account->birthyear;
          break;

        case 'birthmonth':
          $replacements[$original] = $sanitize ? check_plain($account->birthmonth) : $account->birthmonth;
          break;

        case 'birthday':
          $replacements[$original] = $sanitize ? check_plain($account->birthday) : $account->birthday;
          break;

        case 'proclass':
          $replacements[$original] = $sanitize ? check_plain($account->proclass) : $account->proclass;
          break;

        case 'depname':
          $replacements[$original] = $sanitize ? check_plain($account->depname) : $account->depname;
          break;

        case 'titlename':
          $replacements[$original] = $sanitize ? check_plain($account->titlename) : $account->titlename;
          break;

        case 'stdno':
          $replacements[$original] = $sanitize ? check_plain($account->stdno) : $account->stdno;
          break;

        case 'class':
          $replacements[$original] = $sanitize ? check_plain($account->class) : $account->class;
          break;

        case 'seat':
          $replacements[$original] = $sanitize ? check_plain($account->seat) : $account->seat;
          break;
      }
    }
  }

  return $replacements;
}
