<?php

/**
 * @file
 * Definition of simsauth_handler_field_user_realname.
 */

/**
 * Field handler to provide simple renderer that allows using a themed user link.
 */
class sims_views_handler_field_user_userclass extends views_handler_field_user {

  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
  }

  function render($values) {
    $realname = '';
    if (module_exists('simsauth')) {
      if ($uid = $this->get_value($values)) {
        $account = user_load($uid);
	if ($account->userclass == 'teacher') {
          $realname = t('teacher');
	}
	else {
          $realname = t('student');
	}
      }
    }

    return $realname;
  }
}
