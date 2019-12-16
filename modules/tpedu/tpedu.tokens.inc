<?php

/**
 * @file
 * Builds placeholder replacement tokens for realname.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function tpedu_token_info_alter(&$info) {
  $info['tokens']['user']['realname'] = array(
    'name' => t('Real Name'),
    'description' => t('The real name of the user.'),
  );
  $info['tokens']['user']['gender'] = array(
    'name' => t('Gender'),
    'description' => t('The gender of the user.'),
  );
  $info['tokens']['user']['userclass'] = array(
    'name' => t('User Class'),
    'description' => t('The user is a teacher or a student, identify by the tpedu module.'),
  );
  $info['tokens']['user']['idno'] = array(
    'name' => t('Security Id'),
    'description' => t('The taiwan security id of the user account.'),
  );
  $info['tokens']['user']['birthdate'] = array(
    'name' => t('Date of Birth'),
    'description' => t('The date of birth.'),
    'type' => 'date',
  );
  $info['tokens']['user']['birthyear'] = array(
    'name' => t('Year of Birth'),
    'description' => t('The year of birth.'),
  );
  $info['tokens']['user']['birthmonth'] = array(
    'name' => t('Month of Birth'),
    'description' => t('The month of birth.'),
  );
  $info['tokens']['user']['birthday'] = array(
    'name' => t('Month Day of Birth'),
    'description' => t('The month day of birth.'),
  );
  $info['tokens']['user']['proclass'] = array(
    'name' => t('Adoption Class'),
    'description' => t('The adoption class of the teacher.'),
  );
  $info['tokens']['user']['depname'] = array(
    'name' => t('Department Name'),
    'description' => t('The department name of the teacher.'),
  );
  $info['tokens']['user']['titlename'] = array(
    'name' => t('Job Title'),
    'description' => t('The job title of the teacher.'),
  );
  $info['tokens']['user']['stdno'] = array(
    'name' => t('Student Id'),
    'description' => t('The id of the student.'),
  );
  $info['tokens']['user']['class'] = array(
    'name' => t('Attending Class'),
    'description' => t('The class of the student.'),
  );
  $info['tokens']['user']['seat'] = array(
    'name' => t('Seat Number'),
    'description' => t('The seat number of the student.'),
  );
}

/**
 * Implements hook_tokens().
 */
function tpedu_tokens($type, $tokens, array $data, array $options, $bubbleable_metadata = NULL) {
  $replacements = array();
  $bubbleable_metadata = $bubbleable_metadata ? : new BubbleableMetadata();

  if ($type == 'user' && !empty($data['user'])) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $data['user'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'realname':
          $replacements[$original] = $account->realname;
	  $bubbleable_metadata->addCacheableDependency($account->realname);
          break;

        case 'gender':
          $replacements[$original] = $account->gender;
	  $bubbleable_metadata->addCacheableDependency($account->gender);
          break;

        case 'userclass':
          $replacements[$original] = $account->userclass;
	  $bubbleable_metadata->addCacheableDependency($account->userclass);
          break;

        case 'idno':
          $replacements[$original] = $account->idno;
	  $bubbleable_metadata->addCacheableDependency($account->idno);
          break;

        case 'birthdate':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
	  $bubbleable_metadata->addCacheableDependency($account->birthdate);
          $replacements[$original] = $account->birthdate ? format_date($account->birthdate, 'medium', '', NULL, $langcode) : t('unknow');
          break;

        case 'birthyear':
          $replacements[$original] = $account->birthyear;
	  $bubbleable_metadata->addCacheableDependency($account->birthyear);
          break;

        case 'birthmonth':
          $replacements[$original] = $account->birthmonth;
	  $bubbleable_metadata->addCacheableDependency($account->birthmonth);
          break;

        case 'birthday':
          $replacements[$original] = $account->birthday;
	  $bubbleable_metadata->addCacheableDependency($account->birthday);
          break;

        case 'proclass':
          $replacements[$original] = $account->proclass;
	  $bubbleable_metadata->addCacheableDependency($account->proclass);
          break;

        case 'depname':
          $replacements[$original] = $account->depname;
	  $bubbleable_metadata->addCacheableDependency($account->depname);
          break;

        case 'titlename':
          $replacements[$original] = $account->titlename;
	  $bubbleable_metadata->addCacheableDependency($account->titlename);
          break;

        case 'stdno':
          $replacements[$original] = $account->stdno;
	  $bubbleable_metadata->addCacheableDependency($account->stdno);
          break;

        case 'class':
          $replacements[$original] = $account->class;
	  $bubbleable_metadata->addCacheableDependency($account->class);
          break;

        case 'seat':
          $replacements[$original] = $account->seat;
	  $bubbleable_metadata->addCacheableDependency($account->seat);
          break;
      }
    }
  }

  return $replacements;
}
