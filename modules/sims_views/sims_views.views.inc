<?php

/**
 * @file
 * Provide views data and handlers for user.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 */
function sims_views_views_data_alter(&$data) {
  // realname
  $data['users']['realname'] = array(
    'real field' => 'uid',
    'title' => t('Real name'),
    'help' => t('The user or author real name.'),
    'field' => array(
      'handler' => 'sims_views_handler_field_user_realname',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'title' => t('Real name'),
      'help' => t('The user or author real name. This filter does not check if the user exists and allows partial matching. Does not utilize autocomplete.')
    ),
  );

  // userclass
  $data['users']['userclass'] = array(
    'real field' => 'uid',
    'title' => t('User class'),
    'help' => t('The user is a teacher or a student, identify by the simsauth module.'),
    'field' => array(
      'handler' => 'sims_views_handler_field_user_userclass',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'title' => t('User class'),
      'help' => t('The user is a teacher or a student, identify by the simsauth module. This filter does not check if the user exists and allows partial matching. Does not utilize autocomplete.')
    ),
  );

  // depname
  $data['users']['depname'] = array(
    'real field' => 'uid',
    'title' => t('Department name'),
    'help' => t('The department name of the teacher or The class of the student.'),
    'field' => array(
      'handler' => 'sims_views_handler_field_user_depname',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'title' => t('Department name'),
      'help' => t('The department name of the teacher or The class of the student. This filter does not check if the user exists and allows partial matching. Does not utilize autocomplete.')
    ),
  );

  // titlename
  $data['users']['titlename'] = array(
    'real field' => 'uid',
    'title' => t('Job title'),
    'help' => t('The job title of the teacher or the seat number of the student.'),
    'field' => array(
      'handler' => 'sims_views_handler_field_user_titlename',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'title' => t('Job title'),
      'help' => t('The job title of the teacher or the seat number of the student. This filter does not check if the user exists and allows partial matching. Does not utilize autocomplete.')
    ),
  );

  // birthdate
  $data['users']['birthdate'] = array(
    'real field' => 'uid',
    'title' => t('Birth Date'),
    'help' => t('The date the user was borned.'),
    'field' => array(
      'handler' => 'sims_views_handler_field_user_birthdate',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  return $data;
}
