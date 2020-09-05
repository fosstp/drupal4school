<?php

$directory = null;

function initGoogleDirectory()
{
    global $directory;
    if ($directory instanceof \Google_Service_Directory) {
        return $directory;
    } else {
        $config = \Drupal::config('gsync.settings');
        $uri = $config->get('google_service_json');
        $path = \Drupal::service('file_system')->realpath($uri);
        $user_to_impersonate = $config->get('google_domain_admin');
        $scopes = [
            \Google_Service_Directory::ADMIN_DIRECTORY_ORGUNIT,
            \Google_Service_Directory::ADMIN_DIRECTORY_USER,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
        ];

        $client = new \Google_Client();
        $client->setAuthConfig($path);
        $client->setApplicationName('Drupal for School');
        $client->setScopes($scopes);
        $client->setSubject($user_to_impersonate);
        try {
            $directory = new \Google_Service_Directory($client);

            return $directory;
        } catch (\Google_Service_Exception $e) {
            \Drupal::logger('google')->debug('directory:'.$e->getMessage());

            return null;
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
        \Drupal::logger('google')->debug('gs_listOrgUnits:'.$e->getMessage());

        return false;
    }
}

function gs_getOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->get('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_getOrgUnit($orgPath):".$e->getMessage());

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
        \Drupal::logger('google')->debug("gs_createOrgUnit($orgPath,$orgName,$orgDescription):".$e->getMessage());

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
        \Drupal::logger('google')->debug("gs_updateOrgUnit($orgPath,$orgName):".$e->getMessage());

        return false;
    }
}

function gs_deleteOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->delete('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_deleteOrgUnit($orgPath):".$e->getMessage());

        return false;
    }
}

function gs_findUsers($filter)
{
    global $directory;
    $config = \Drupal::config('gsync.settings');
    try {
        $result = $directory->users->listUsers(['domain' => $config->get('google_domain'), 'query' => $filter]);

        return $result->getUsers();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_findUsers($filter):".$e->getMessage());

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
        \Drupal::logger('google')->debug("gs_getUser($userKey):".$e->getMessage());

        return false;
    }
}

function gs_createUser($userObj)
{
    global $directory;
    try {
        return $directory->users->insert($userObj);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_createUser('.var_export($userObj, true).'):'.$e->getMessage());

        return false;
    }
}

function gs_updateUser($userKey, $userObj)
{
    global $directory;
    try {
        return $directory->users->update($userKey, $userObj);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_updateUser($userKey,".var_export($userObj, true).'):'.$e->getMessage());

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
        \Drupal::logger('google')->debug("gs_deleteUser($userKey):".$e->getMessage());

        return false;
    }
}

function gs_syncUser($t, $user_key, $user = null, $recover = false)
{
    $config = \Drupal::config('gsync.settings');
    if (is_null($user)) {
        $create = true;
        $user = new \Google_Service_Directory_User();
        $user->setChangePasswordAtNextLogin(false);
        $user->setAgreedToTerms(true);
        $user->setPrimaryEmail($user_key);
        $user->setHashFunction('SHA-1');
        $user->setPassword(sha1(substr($t->idno, -6)));
    } else {
        $create = false;
        $old_key = $user->getPrimaryEmail();
        if ($old_key != $user_key) {
            $user->setPrimaryEmail($user_key);
        }
        if ($recover) {
            $user->setHashFunction('SHA-1');
            $user->setPassword(sha1(substr($t->idno, -6)));
        }
    }
    $sysid = new \Google_Service_Directory_UserExternalId();
    $sysid->setType('organization');
    $sysid->setValue($t->id);
    $user->setExternalIds([$sysid]);
    $names = new \Google_Service_Directory_UserName();
    if ($t->sn && $t->gn) {
        $names->setFamilyName($t->sn);
        $names->setGivenName($t->gn);
    } else {
        $myname = guess_name($t->realname);
        $names->setFamilyName($myname[0]);
        $names->setGivenName($myname[1]);
    }
    $names->setFullName($t->realname);
    $user->setName($names);
    if (!empty($t->email) && $t->email != $user->getPrimaryEmail()) {
        $user->setRecoveryEmail($t->email);
    }
    $orgs = [];
    if ($t->student) {
        $neworg = new \Google_Service_Directory_UserOrganization();
        $neworg->setType('school');
        $neworg->setTitle($t->dept_name.$t->seat.'號');
        $neworg->setPrimary(true);
        $orgs[] = $neworg;
    } else {
        $jobs = get_jobs($t->uuid);
        foreach ($jobs as $job) {
            $neworg = new \Google_Service_Directory_UserOrganization();
            $neworg->setType('school');
            $neworg->setTitle($job->dept_name.$job->role_name);
            if ($job->role_id == $t->role_id) {
                $neworg->setPrimary(true);
            }
            $orgs[] = $neworg;
        }
    }
    if (!empty($orgs)) {
        $user->setOrganizations($orgs);
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
    if ($create) {
        return gs_createUser($user);
    } else {
        return gs_updateUser($user_key, $user);
    }
}

function gs_createUserAlias($userKey, $alias)
{
    global $directory;
    $email_alias = new \Google_Service_Directory_Alias();
    $email_alias->setAlias($alias);
    try {
        return $directory->users_aliases->insert($userKey, $email_alias);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_createUserAlias($userKey, $alias):".$e->getMessage());

        return false;
    }
}

function gs_listUserAliases($userKey)
{
    global $directory;
    try {
        return $directory->users_aliases->listUsersAliases($userKey);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_listUserAliases($userKey):".$e->getMessage());

        return false;
    }
}

function gs_removeUserAlias($userKey, $alias)
{
    global $directory;
    try {
        return $directory->users_aliases->delete($userKey, $alias);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_removeUserAlias($userKey,$alias):".$e->getMessage());

        return false;
    }
}

function gs_listGroups()
{
    global $directory;
    $config = \Drupal::config('gsync.settings');
    try {
        return $directory->groups->listGroups(['domain' => $config->get('google_domain')])->getGroups();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_listGroups:'.$e->getMessage());

        return false;
    }
}

function gs_listUserGroups($user_key)
{
    global $directory;
    $config = \Drupal::config('gsync.settings');
    try {
        return $directory->groups->listGroups(['domain' => $config->get('google_domain'), 'userKey' => $user_key])->getGroups();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_listGroups:'.$e->getMessage());

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
        \Drupal::logger('google')->debug("gs_createGroup($groupId,$groupName):".$e->getMessage());

        return false;
    }
}

function gs_listMembers($groupId)
{
    global $directory;
    try {
        return $directory->members->listMembers($groupId)->getMembers();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_listMembers($groupId):".$e->getMessage());

        return false;
    }
}

function gs_addMember($groupId, $member)
{
    global $directory;
    $memberObj = new \Google_Service_Directory_Member();
    $memberObj->setEmail($member);
    $memberObj->setRole('MEMBER');
    $memberObj->setType('USER');
    $memberObj->setStatus('ACTIVE');
    try {
        return $directory->members->insert($groupId, $memberObj);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_addMember($groupId,$member):".$e->getMessage());

        return false;
    }
}

function gs_removeMember($groupId, $member)
{
    global $directory;
    try {
        return $directory->members->delete($groupId, $member);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_removeMember($groupId,$member):".$e->getMessage());

        return false;
    }
}

function guess_name($myname)
{
    $len = mb_strlen($myname, 'UTF-8');
    if ($len > 3) {
        return [mb_substr($myname, 0, 2, 'UTF-8'), mb_substr($myname, 2, null, 'UTF-8')];
    } else {
        return [mb_substr($myname, 0, 1, 'UTF-8'), mb_substr($myname, 1, null, 'UTF-8')];
    }
}
