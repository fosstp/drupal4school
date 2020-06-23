<?php

$ad_conn = null;

function ad_test()
{
    global $ad_conn;
    $config = \Drupal::config('adsync.settings');
    $ad_host = $config->get('ad_server');
    $ad_conn = @ldap_connect($ad_host, 389);
    ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
    if ($ad_conn) {
        $ad_user = $config->get('ad_admin');
        $ad_pass = $config->get('ad_password');
        $ad_bind = @ldap_bind($ad_conn, $ad_user, $ad_pass);
        if ($ad_bind) {
            @ldap_close($ad_conn);
            $ad_conn = @ldap_connect('ldaps://'.$ad_host, 636);
            if (empty($ad_conn)) {
                \Drupal::logger('adsync')->notice('無法使用 LDAPS 通訊協定連接 AD 伺服器，請在 AD 伺服器上安裝企業級憑證服務，以便提供 LDAPS 連線功能。');

                return 3;
            } else {
                return 0;
            }
        } else {
            \Drupal::logger('adsync')->notice('已經連線到 AD 伺服器，但是無法成功登入。請檢查管理員帳號密碼是否正確！');

            return 2;
        }
    } else {
        \Drupal::logger('adsync')->notice('連線 AD 伺服器失敗。請檢查伺服器名稱或 IP 是否正確！');

        return 1;
    }
}

function ad_admin()
{
    global $ad_conn;
    $config = \Drupal::config('adsync.settings');
    if (!$ad_conn) {
        $ad_host = $config->get('ad_server');
        $ad_conn = @ldap_connect('ldaps://'.$ad_host, 636);
        @ldap_set_option($ad_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ad_conn, LDAP_OPT_REFERRALS, 0);
    }
    if ($ad_conn) {
        $ad_user = $config->get('ad_admin');
        $ad_pass = $config->get('ad_password');
        $ad_bind = @ldap_bind($ad_conn, $ad_user, $ad_pass);
        if ($ad_bind) {
            return $ad_conn;
        }
    }

    return null;
}

function ad_error()
{
    global $ad_conn;

    return ldap_error($ad_conn);
}

function ad_findGroup($desc)
{
    $ad_conn = ad_admin();
    $config = \Drupal::config('gsync.settings');
    $base_dn = $config->get('users_dn');
    $filter = "(&(objectClass=group)(description=$desc))";
    $result = @ldap_search($ad_conn, $base_dn, $filter);
    if ($result) {
        $infos = @ldap_get_entries($ad_conn, $result);
        if ($infos['count'] > 0) {
            $data = $infos[0];
        }

        return $data;
    } else {
        return false;
    }
}

function ad_getGroup($group)
{
    $ad_conn = ad_admin();
    $config = \Drupal::config('gsync.settings');
    $base_dn = $config->get('users_dn');
    $filter = "(&(objectClass=group)(cn=$group))";
    $result = @ldap_search($ad_conn, $base_dn, $filter);
    $data = array();
    if ($result) {
        $infos = @ldap_get_entries($ad_conn, $result);
        if ($infos['count'] > 0) {
            $data = $infos[0];
        }

        return $data;
    } else {
        return false;
    }
}

function ad_getUserGroups($dn)
{
    $ad_conn = ad_admin();
    $config = \Drupal::config('gsync.settings');
    $base_dn = $config->get('users_dn');
    $filter = '(objectClass=group)';
    $result = @ldap_search($ad_conn, $base_dn, $filter);
    $groups = @ldap_get_entries($ad_conn, $result);
    $data = array();
    if ($groups['count'] > 0) {
        unset($groups['count']);
        foreach ($groups as $g) {
            if ($g['member']['count'] > 0 && in_array($dn, $g['member'])) {
                $data[] = $g;
            }
        }
    }

    return $data;
}

function ad_createGroup($group, $dn, $group_name)
{
    $ad_conn = ad_admin();
    $groupinfo = array();
    $groupinfo['objectClass'] = 'group';
    $groupinfo['sAMAccountName'] = $group;
    $groupinfo['displayName'] = $group_name;
    $groupinfo['description'] = $group_name;
    $result = ldap_add($ad_conn, $dn, $groupinfo);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_addMember($dn, $userDn)
{
    $ad_conn = ad_admin();
    $result = ldap_mod_add($ad_conn, $dn, array('member' => $userDn));
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_removeMember($dn, $userDn)
{
    $ad_conn = ad_admin();
    $result = ldap_mod_del($ad_conn, $dn, array('member' => $userDn));
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_getUser($account)
{
    $ad_conn = ad_admin();
    $config = \Drupal::config('gsync.settings');
    $base_dn = $config->get('users_dn');
    $filter = "(sAMAccountName=$account)";
    $result = @ldap_search($ad_conn, $base_dn, $filter);
    $data = array();
    if ($result) {
        $infos = @ldap_get_entries($ad_conn, $result);
        if ($infos['count'] > 0) {
            $data = $infos[0];
        }
    }

    return $data;
}

function ad_createUser($user, $dn)
{
    $ad_conn = ad_admin();
    $userinfo = array();
    $userinfo['objectClass'] = array('top', 'person', 'organizationalPerson', 'user');
    $userinfo['cn'] = $user->account;
    $userinfo['sAMAccountName'] = $user->account;
    $userinfo['accountExpires'] = 0;
    $userinfo['userAccountControl'] = '0x10220';
    $userinfo['userPassword'] = substr($user->idno, -6);
    $userinfo['unicodePwd'] = pwd_encryption(substr($user->idno, -6));
    if ($user->sn && $user->gn) {
        $userinfo['sn'] = $user->sn;
        $userinfo['givenName'] = $user->gn;
    }
    $userinfo['displayName'] = $user->realname;
    $userinfo['description'] = $user->idno;
    $userinfo['department'] = $user->dept_name;
    $userinfo['title'] = $user->role_name;
    if ($user->email) {
        $userinfo['mail'] = $user->email;
        $userinfo['userPrincipalName'] = $user->email;
    }
    if ($user->telephone) {
        $userinfo['telephoneNumber'] = $user->telephone;
    }
    $result = @ldap_add($ad_conn, $dn, $userinfo);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_syncUser($user, $dn)
{
    $ad_conn = ad_admin();
    $userinfo = array();
    if ($user->sn && $user->gn) {
        $userinfo['sn'] = $user->sn;
        $userinfo['givenName'] = $user->gn;
    }
    $userinfo['displayName'] = $user->realname;
    $userinfo['description'] = $user->idno;
    $userinfo['department'] = $user->dept_name;
    $userinfo['title'] = $user->role_name;
    if ($user->email) {
        $userinfo['mail'] = $user->email;
        $userinfo['userPrincipalName'] = $user->email;
    }
    if ($user->telphone) {
        $userinfo['telephoneNumber'] = $user->telphone;
    }
    $result = @ldap_mod_replace($ad_conn, $dn, $userinfo);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_lockUser($dn)
{
    $ad_conn = ad_admin();
    $userdata['userAccountControl'] = '0x10222';
    $result = @ldap_mod_replace($ad_conn, $dn, $userdata);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_unlockUser($dn)
{
    $ad_conn = ad_admin();
    $userdata['userAccountControl'] = '0x10220';
    $result = @ldap_mod_replace($ad_conn, $dn, $userdata);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_deleteUser($dn)
{
    $ad_conn = ad_admin();
    $result = @ldap_delete($ad_conn, $dn);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_changeAccount($dn, $new_account)
{
    $ad_conn = ad_admin();
    $result = @ldap_mod_replace($ad_conn, $dn, array('sAMAccountName' => $new_account));
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function ad_changePass($dn, $password)
{
    $ad_conn = ad_admin();
    $userdata = array();
    $userdata['userPassword'] = $password;
    $userdata['unicodePwd'] = pwd_encryption($password);
    $result = @ldap_mod_replace($ad_conn, $dn, $userdata);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function pwd_encryption($newPassword)
{
    $newPassword = '"'.$newPassword.'"';
    $len = strlen($newPassword);
    $newPassw = '';
    for ($i = 0; $i < $len; ++$i) {
        $newPassw .= "{$newPassword[$i]}\000";
    }

    return $newPassw;
}
