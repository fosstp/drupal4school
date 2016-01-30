<?php
$ad_conn = NULL;

function ad_test() {
  global $ad_conn;
  if (!$ad_conn) {
    $ad_host = variable_get('adsync_server_ad');
    $ad_conn = ldap_connect($ad_host, 389);
  }
  ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
  if ($ad_conn) {
    $ad_user = variable_get('adsync_server_ad_admin');
    $ad_pass = variable_get('adsync_server_ad_pass');
    $ad_bind = ldap_bind($ad_conn, $ad_user, $ad_pass);
    if ($ad_bind) {
      ldap_close($ad_conn);
	  $ad_conn = ad_admin();
      if (empty($ad_conn)) {
        return 3;
      }
      else {
        return 0;
      }
    }
    else {
      return 2;
    }
  }
  else {
    return 1;
  }
}

function ad_admin() {
  global $ad_conn;
  $ad_host = variable_get('adsync_server_ad');
  $ad_conn = ldap_connect('ldaps://' . $ad_host, 636);
  ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
  if ($ad_conn) {
    $ad_user = variable_get('adsync_server_ad_admin');
    $ad_pass = variable_get('adsync_server_ad_pass');
    $ad_bind = ldap_bind($ad_conn, $ad_user, $ad_pass);
    if ($ad_bind) {
	  return $ad_conn;
    }
  }
  return NULL;
}

function ad_login($username, $password) {
  global $ad_conn;
  if (!$ad_conn) {
    $ad_host = variable_get('adsync_server_ad');
    $ad_conn = ldap_connect($ad_host, 389);
  }
  ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
  if ($ad_conn) {
    $ad_user = "$username@" . variable_get('adsync_server_ad_domain');
    return ldap_bind($ad_conn, $ad_user, $password);
  }
  return FALSE;
}

function ad_change_uid($username, $new_account) {
  global $ad_conn;
  $ad_conn = ad_admin();
  if ($ad_conn) {
    $dn = variable_get('adsync_server_ad_domain_dn');
    $filter = "(sAMAccountName=$username)";
    $result = ldap_search($ad_conn, $dn, $filter);
    $infos = ldap_get_entries($ad_conn, $result);
    if ($infos["count"] > 0) {
      $userDn = $infos[0]["distinguishedname"][0];
      $userdata["sAMAccountName"] = $new_account;
      $result = ldap_mod_replace($ad_conn, $userDn, $userdata);
      if (!$result) {
        return FALSE;
      }
    }
  } else {
    return FALSE;
  }
  return TRUE;
}

function pwd_encryption($newPassword) {
  $newPassword = '"' . $newPassword . '"';
  $len = strlen($newPassword);
  $newPassw = '';
  for ( $i = 0; $i < $len; $i++ ) {
    $newPassw .= "{$newPassword {$i}}\000"; 
  }
  return $newPassw;
}

function ad_change_pass($username, $new_pass) {
  global $ad_conn;
  $ad_conn = ad_admin();
  if ($ad_conn) {
    $dn = variable_get('adsync_server_ad_domain_dn');
    $filter = "(sAMAccountName=$username)";
    $result = ldap_search($ad_conn, $dn, $filter);
    $infos = ldap_get_entries($ad_conn, $result);
    if ($infos["count"] > 0) {
      $userDn = $infos[0]["distinguishedname"][0];
      $userdata['userPassword'] = $new_pass;
      $userdata["unicodePwd"] = pwd_encryption($new_pass);
      $result = ldap_mod_replace($ad_conn, $userDn, $userdata);
      if (!$result) {
        return FALSE;
      }
    }
  } else {
    return FALSE;
  }
  return TRUE;
}
