<?php

$directory = null;

function initGoogleService()
{
    global $directory;
    if ($directory instanceof \Google_Service_Directory) {
        return $directory;
    } else {
        $config = \Drupal::configFactory()->getEditable('gsync.settings');
        $realpath = \Drupal::service('file_system')->realpath($config->get('google_serivce_json'));
        $user_to_impersonate = $config->get('google_domain_admin');
        $scopes = array(
            \Google_Service_Directory::ADMIN_DIRECTORY_ORGUNIT,
            \Google_Service_Directory::ADMIN_DIRECTORY_ROLEMANAGEMENT,
            \Google_Service_Directory::ADMIN_DIRECTORY_USER,
            \Google_Service_Directory::ADMIN_DIRECTORY_USER_ALIAS,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
        );

        $client = new \Google_Client();
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$realpath");
        $client->useApplicationDefaultCredentials();
        $client->setApplicationName('Drupal for School');
        $client->setScopes($scopes);
        $client->setSubject($user_to_impersonate);
        try {
            $_SESSION['gsync_access_token'] = $client->getAccessToken();
        } catch (\Google_Service_Exception $e) {
            \Drupal::logger('google')->debug($e->getMessage());
        }
        if ($_SESSION['gsync_access_token']) {
            $directory = new \Google_Service_Directory($client);

            return $directory;
        } else {
            return false;
        }
    }
}

function gs_listOrgUnits()
{
    global $directory;
    try {
        $result = $directory->orgunits->listOrgunits('my_customer');

        return $result->getOrganizationUnits();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_getOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->get('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_createOrgUnit($orgPath, $orgName, $orgDescription)
{
    global $directory;
    $org_unit = new \Google_Service_Directory_OrgUnit();
    $org_unit->setName($orgName);
    $org_unit->setDescription($orgDescription);
    $org_unit->setParentOrgUnitPath($orgPath);
    try {
        return $directory->orgunits->insert('my_customer', $org_unit);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_updateOrgUnit($orgPath, $orgName)
{
    global $directory;
    $org_unit = new \Google_Service_Directory_OrgUnit();
    $org_unit->setDescription($orgName);
    try {
        return $directory->orgunits->update('my_customer', $orgPath, $org_unit);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_deleteOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->delete('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_findUsers($filter)
{
    global $directory;
    try {
        $result = $directory->users->listUsers(array('domain' => config('saml.email_domain'), 'query' => $filter));

        return $result->getUsers();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

// userKey must be their gmail address.
function gs_getUser($userKey)
{
    global $directory;
    if (!strpos($userKey, '@')) {
        return false;
    }
    try {
        return $directory->users->get($userKey);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_createUser($t, $userKey)
{
    global $directory;
    $user = new \Google_Service_Directory_User();
    $user->setChangePasswordAtNextLogin(false);
    $user->setAgreedToTerms(true);
    $user->setPrimaryEmail($userKey);
    $user->setHashFunction('SHA-1');
    $user->setPassword(sha1(substr($t->idno, -6)));
    try {
        $user = $directory->users->insert($user);

        return gs_syncUser($t, $user);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_updateUser($userKey, $userObj)
{
    global $directory;
    try {
        return $directory->users->update($userKey, $userObj);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_deleteUser($userKey)
{
    global $directory;
    if (!strpos($userKey, '@')) {
        return false;
    }
    try {
        return $directory->users->delete($userKey);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_syncUser($t, $user)
{
    $config = \Drupal::config('gsync.settings');
    $names = new \Google_Service_Directory_UserName();
    $names->setFamilyName($t->sn);
    $names->setGivenName($t->gn);
    $names->setFullName($t->realname);
    $user->setName($names);
    if (!empty($t->email) && $t->email != $user->getPrimaryEmail()) {
        $user->setRecoveryEmail($t->email);
    }
    if (!empty($t->dept_id) && !empty($t->role_id)) {
        $myorg = $user->getOrganizations();
        $neworg = new \Google_Service_Directory_UserOrganization();
        $neworg->setDepartment($t->dept_name);
        $neworg->setTitle($t->role_name);
        $neworg->setPrimary(true);
        if (is_array($myorg)) {
            if (!in_array($neworg, $myorg)) {
                $myorg = array_unshift($myorg, $neworg);
            }
        } else {
            $myorg = $neworg;
        }
        $user->setOrganizations($myorg);
    }
    if (!empty($t->mobile)) {
        $phones = $user->getPhones();
        $phone = new \Google_Service_Directory_UserPhone();
        $phone->setPrimary(true);
        $phone->setType('mobile');
        $phone->setValue($t->mobile);
        if (is_array($phones)) {
            if (!in_array($phone, $phones)) {
                $phones = array_unshift($phones, $phone);
            }
        }
        $user->setPhones($phones);
        $user->setRecoveryPhone($phone);
    }
    if (!empty($t->telephone)) {
        $phones = $user->getPhones();
        $phone = new \Google_Service_Directory_UserPhone();
        $phone->setPrimary(false);
        $phone->setValue($t->telephone);
        if (is_array($phones)) {
            if (!in_array($phone, $phones)) {
                $phones = array_unshift($phones, $phone);
            }
        }
        $user->setPhones($phones);
    }

    $gender = new \Google_Service_Directory_UserGender();
    if (!empty($t->gender)) {
        switch ($t->gender) {
            case 0:
                $gender->setType('unknow');
                break;
            case 1:
                $gender->setType('male');
                break;
            case 2:
                $gender->setType('female');
                break;
            case 9:
                $gender->setType('other');
                break;
        }
    }
    $user->setGender($gender);
    if ($t->student) {
        if ($config->get('student_orgunit')) {
            $user->setOrgUnitPath($config->get('student_orgunit'));
        }
    } else {
        if (!empty($t->class)) {
            $user->setIsAdmin(true);
        }
        if ($config->get('teacher_orgunit')) {
            $user->setOrgUnitPath($config->get('teacher_orgunit'));
        }
    }

    return gs_updateUser($user->getPrimaryEmail(), $user);
}

function gs_createUserAlias($userKey, $alias)
{
    global $directory;
    $email_alias = new \Google_Service_Directory_Alias();
    $email_alias->setAlias($alias);
    try {
        return $directory->users_aliases->insert($userKey, $email_alias);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_listUserAliases($userKey)
{
    global $directory;
    try {
        return $directory->users_aliases->listUsersAliases($userKey);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_removeUserAlias($userKey, $alias)
{
    global $directory;
    try {
        return $directory->users_aliases->delete($userKey, $alias);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_listGroups()
{
    global $directory;
    try {
        return $directory->groups->listGroups();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_createGroup($groupId, $groupName)
{
    global $directory;
    $group = new Google_Service_Directory_Group();
    $group->setEmail($groupId);
    $group->setDescription($groupName);
    $group->setName($groupName);
    try {
        return $directory->groups->insert($group);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_listMembers($groupId)
{
    global $directory;
    try {
        return $directory->members->listMembers($groupId);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug($e->getMessage());

        return false;
    }
}

function gs_addMembers($groupId, $members)
{
    global $directory;
    $users = array();
    foreach ($members as $m) {
        $member = new \Google_Service_Directory_Member();
        $member->setEmail($m);
        $member->setRole('MEMBER');
        try {
            $users[] = $directory->members->insert($groupId, $member);
        } catch (\Google_Service_Exception $e) {
            \Drupal::logger('google')->debug($e->getMessage());

            return false;
        }
    }

    return $users;
}

function gs_removeMembers($groupId, $members)
{
    global $directory;
    $users = array();
    foreach ($members as $m) {
        $member = $m->getEmail();
        try {
            $directory->members->delete($groupId, $member);
        } catch (\Google_Service_Exception $e) {
            \Drupal::logger('google')->debug($e->getMessage());
        }
    }
}
