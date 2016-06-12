<?php
setlocale(LC_ALL, 'zh_TW');
setlocale(LC_ALL, 'zh_TW.UTF-8');
setlocale(LC_ALL, 'zh_TW.utf8');
date_default_timezone_set('Asia/Taipei');
$db2_conn = NULL;
$ldap_conn = NULL;
$ad_conn = NULL;

function get_current_seme() {
  if (date('m') > 7) {
    $year = date('Y') - 1911;
    $seme = 1;
  }
  elseif (date('m') < 2) {
    $year = date('Y') - 1912;
    $seme = 1;
  }
  else {
    $year = date('Y') - 1912;
    $seme = 2;
  }
  $seyear=$year - 5;
  if (strlen($seyear) == 2) {
    $seyear = '0' . $seyear;
  }
  return array('year' => $year, 'seme' => $seme, 'seyear' => $seyear);
}

function get_seme($myyear, $mymonth) {
  if ($mymonth > 7) {
    $year = $myyear - 1911;
    $seme = 1;
  }
  elseif ($mymonth < 2) {
    $year = $myyear - 1912;
    $seme = 1;
  }
  else {
    $year = $myyear - 1912;
    $seme = 2;
  }
  $seyear=$year - 5;
  if (strlen($seyear) == 2) {
    $seyear = '0' . $seyear;
  }
  return array('year' => $year, 'seme' => $seme, 'seyear' => $seyear);
}

function db2_test() {
  $config = \Drupal::config('ibmdb2.settings');
  $db2info = array(
    'driver' => 'ibmdb2',
    'database' => $config->get('ibmdb2.database'),
    'hostname' => $config->get('ibmdb2.db2_server'),
    'port' => $config->get('ibmdb2.db2_port'),
    'uid' => $config->get('ibmdb2.db2_admin'),
    'pwd' => $config->get('ibmdb2.db2_pass'),
	'currentschema' => $config->get('ibmdb2.schema'),
  );
  Database::addConnectionInfo('db2', 'sims', $db2info);
  Database::setActiveConnection('db2');
  return Database::isActiveConnection();
}

function db2_query($query, array $args = array(), array $options = array()) {
  global $db2_conn;
  if (!$db2_conn) {
    $conn_string = \Drupal::config('simsauth.settings')->get('simsauth.connect_string');
    $db2_conn = db2_pconnect($conn_string, '', '');
  }

  if (!empty($args)) {
    $stmt = db2_prepare($db2_conn, $query);
    if (!empty($options)) {
      $op_result = db2_set_option($stmt, $options, 2);
	  if (!$op_result) {
        debug(db2_stmt_errormsg(), 'simsauth');
      }
    }
    $result = db2_execute($stmt, $args);
    if (!$result) {
      debug(db2_stmt_errormsg(), 'simsauth');
    }
    else {
      return $stmt;
    }
  }
  else {
    if (!empty($options)) {
      $op_result = db2_set_option($db2_conn, $options, 1);
	  if (!$op_result) {
        debug(db2_conn_errormsg(), 'simsauth');
      }
    }
    $rs = db2_exec($db2_conn, $query);
    if (!$rs) {
      debug(db2_conn_errormsg(), 'simsauth');
    }
	else {
      return $rs;
    }
  }

  return NULL;
}

function db2_operate($query, array $args = array(), array $options = array()) {
  global $db2_conn;
  if (!$db2_conn) {
    $conn_string = \Drupal::config('simsauth.settings')->get('simsauth.connect_string');
    $db2_conn = db2_pconnect($conn_string, '', '');
  }

  if (!empty($args)) {
    $stmt = db2_prepare($db2_conn, $query);
    if (!empty($options)) {
      $op_result = db2_set_option($stmt, $options, 2);
      if (!$op_result) {
        debug(db2_stmt_errormsg(), 'simsauth');
      }
    }
    $result = db2_execute($stmt, $args);
    if (!$result) {
      debug(db2_stmt_errormsg(), 'simsauth');
    }
    else {
      return TRUE;
    }
  }
  else {
    if (!empty($options)) {
      $op_result = db2_set_option($db2_conn, $options, 1);
	  if (!$op_result) {
        debug(db2_conn_errormsg(), 'simsauth');
      }
    }
    $rs = db2_exec($db2_conn, $query);
    if (!$rs) {
      debug(db2_conn_errormsg(), 'simsauth');
    }
    else {
      return TRUE;
    }
  }

  return FALSE;
}

function ldapspecialchars($string) {
  $sanitized=array(
    '\\' => '\5c',
    '*' => '\2a',
	'(' => '\28',
	')' => '\29',
	"\x00" => '\00',
  );
  return str_replace(array_keys($sanitized), array_values($sanitized), $string);
}

function ldap_test() {
  global $ldap_conn;
  $config = \Drupal::config('simsauth.settings');
  if (!$ldap_conn) {
    $ldap_host = $config->get('simsauth.ldap_server');
    $ldap_port = $config->get('simsauth.ldap_port');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
  }
  if ($ldap_conn) {
    $ldap_user = $config->get('simsauth.ldap_admin');
    $ldap_pass = $config->get('simsauth.ldap_pass');
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_pass);
    if ($ldap_bind) {
      ldap_close($ldap_conn);
	  return 0;
    }
    else {
      return 2;
    }
  }
  else {
    return 1;
  }
}

function ldap_admin() {
  global $ldap_conn;
  $config = \Drupal::config('simsauth.settings');
  if (!$ldap_conn) {
    $ldap_host = $config->get('simsauth.ldap_server');
    $ldap_port = $config->get('simsauth.ldap_port');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
  }
  if ($ldap_conn) {
    $ldap_user = $config->get('simsauth.ldap_admin');
    $ldap_pass = $config->get('simsauth.ldap_pass');
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_pass);
    if ($ldap_bind) {
	  return $ldap_conn;
    }
  }
  return NULL;
}

function ldap_login($username, $password) {
  global $ldap_conn;
  $config = \Drupal::config('simsauth.settings');
  if (!$ldap_conn) {
    $ldap_host = $config->get('simsauth.ldap_server');
    $ldap_port = $config->get('simsauth.ldap_port');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
  }
  if ($ldap_conn) {
    $ldap_user = "cn=$username," . $config->get('simsauth.ldap_basedn');
    if (@ldap_bind($ldap_conn, $ldap_user, $password)) {
      return TRUE;
    }
  }
  return FALSE;
}

function ldap_change_pass($username, $password){
  global $ldap_conn;
  $ldap_conn = ldap_admin();
  if ($ldap_conn) {
    $dn = \Drupal::config('simsauth.settings')->get('simsauth.ldap_basedn');
    $filter = "(uid=$username)";
    $user_search = ldap_search($ldap_conn, $dn, $filter);
    $user_entry = ldap_first_entry($ldap_conn, $user_search);
    $user_dn = ldap_get_dn($ldap_conn, $user_entry);
    $entry = array();
    $hash = "{SHA}" . base64_encode(pack("H*", sha1($password)));
    $entry["userPassword"] = $hash;
    $result = ldap_mod_replace($ldap_conn, $user_dn, $entry);
    if (!$result) {
      return FALSE;
    }
  }
  return TRUE;
}

function ldap_change_uid($username, $newuid){
  global $ldap_conn;
  $ldap_conn = ldap_admin();
  if ($ldap_conn) {
    $dn = \Drupal::config('simsauth.settings')->get('simsauth.ldap_basedn');
    $filter = "(uid=$username)";
    $user_search = ldap_search($ldap_conn, $dn, $filter);
    $user_entry = ldap_first_entry($ldap_conn, $user_search);
    $user_dn = ldap_get_dn($ldap_conn, $user_entry);
    $entry = array();
    $entry["uid"] = $newuid;
    $result = ldap_mod_replace($ldap_conn, $user_dn, $entry);
    if (!$result) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Hooks provided by the simsauth module.
 */
function hook_simsauth_teacher_resetpw($teachers, $result) {
  foreach ($teachers as $teacher) {
    if (isset($result->success[$teacher->uid]) && $result->success[$teacher->uid]) {
      if (alt_change_pass($teacher->name, $teacher->org_pass)) {
        $result->success[$teacher->uid] = TRUE;
      }
      else {
        $result->success[$teacher->uid] = FALSE;
      }
    }
  }
}

/**
 * Hooks provided by the simsauth module.
 */
function hook_simsauth_student_resetpw($students, $result) {
  foreach ($students as $student) {
    if (isset($result->success[$student->uid]) && $result->success[$student->uid]) {
      if (alt_change_pass($student->name, $student->org_pass)) {
        $result->success[$student->uid] = TRUE;
      }
      else {
        $result->success[$student->uid] = FALSE;
      }
    }
  }
}

/**
 * Hooks provided by the simsauth module.
 */
function hook_simsauth_sync_username($old_account, $new_account) {
  if (ad_account_exist($old_account)) {
    $ret = ad_replace_account($account, $new_account);
	return array('module_name' => $ret);
  }
}

/**
 * Hooks provided by the simsauth module.
 */
function hook_simsauth_sync_password($account, $new_pass) {
  if (ad_account_exist($account)) {
    $ret = ad_replace_password($account, $new_pass);
	return array('module_name' => $ret);
  }
}
