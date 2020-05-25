<?php

$directory = null;

function initGoogleService()
{
    global $directory;
    $config = \Drupal::configFactory()->getEditable('gsync.settings');
    $file = file_load($config->get('google_serivce_json'));
    $json = file_get_contents(drupal_realpath($file->uri));
    $user_to_impersonate = $config->get('google_domain_admin');
    $scopes = array(
        'https://www.googleapis.com/auth/admin.directory.orgunit',
        'https://www.googleapis.com/auth/admin.directory.group',
        'https://www.googleapis.com/auth/admin.directory.group.member',
        'https://www.googleapis.com/auth/admin.directory.user',
        'https://www.googleapis.com/auth/admin.directory.user.alias',
    );
    $client = new \Google_Client();
    $client->setAuthConfig($json);
    $client->setApplicationName('Drupal for School');
    $client->setScopes($scopes);
    $client->setSubject($user_to_impersonate);
    $directory = new \Google_Service_Directory($client);
    $_SESSION['gsync_'.$domain.'_access_token'] = $client->getAccessToken();
    if ($_SESSION['gsync_'.$domain.'_access_token']) {
        return $directory;
    } else {
        return null;
    }
}

function listOrgUnits()
{
    global $directory;
    try {
        $result = $directory->orgunits->listOrgunits('my_customer');

        return $result->getOrganizationUnits();
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function getOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->get('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function createOrgUnit($orgPath, $orgName, $orgDescription)
{
    global $directory;
    $org_unit = new \Google_Service_Directory_OrgUnit();
    $org_unit->setName($orgName);
    $org_unit->setDescription($orgDescription);
    $org_unit->setParentOrgUnitPath($orgPath);
    try {
        return $directory->orgunits->insert('my_customer', $org_unit);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function updateOrgUnit($orgPath, $orgName)
{
    global $directory;
    $org_unit = new \Google_Service_Directory_OrgUnit();
    $org_unit->setDescription($orgName);
    try {
        return $directory->orgunits->update('my_customer', $orgPath, $org_unit);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function deleteOrgUnit($orgPath)
{
    global $directory;
    try {
        return $directory->orgunits->delete('my_customer', $orgPath);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function findUsers($filter)
{
    global $directory;
    try {
        $result = $directory->users->listUsers(array('domain' => config('saml.email_domain'), 'query' => $filter));

        return $result->getUsers();
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

// userKey must be their gmail address.
function getUser($userKey)
{
    global $directory;
    if (!strpos($userKey, '@')) {
        return false;
    }
    try {
        return $directory->users->get($userKey);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function createUser($userObj)
{
    global $directory;
    try {
        return $directory->users->insert($userObj);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function updateUser($userKey, $userObj)
{
    global $directory;
    try {
        return $directory->users->update($userKey, $userObj);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function deleteUser($userKey)
{
    global $directory;
    if (!strpos($userKey, '@')) {
        return false;
    }
    try {
        return $directory->users->delete($userKey);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function sync(User $user)
{
    $new_user = false;
    if ($user->nameID()) {
        $gmail = $user->nameID().'@'.config('saml.email_domain');
        $gsuite_user = $this->getUser($gmail);
        if (!$gsuite_user) {
            $new_user = true;
        }
    } else {
        $new_user = true;
    }
    if ($new_user) {
        $gsuite_user = new \Google_Service_Directory_User();
        $gsuite_user->setChangePasswordAtNextLogin(false);
        $gsuite_user->setAgreedToTerms(true);
        $nameID = $user->account();
        if (!empty($nameID) && !$user->is_default_account()) {
            $gmail = $nameID.'@'.config('saml.email_domain');
            $gsuite_user->setPrimaryEmail($gmail);
            $gsuite_user->setPassword($user->uuid);
        } else {
            return false;
        }
    }
    if ($user->email) {
        $gsuite_user->setRecoveryEmail($user->email);
    }
    $phone = new \Google_Service_Directory_UserPhone();
    if ($user->mobile) {
        $phone->setPrimary(true);
        $phone->setType('mobile');
        $phone->setValue($user->mobile);
        $phones[] = $phone;
        $gsuite_user->setPhones($phones);
    }
    $names = new \Google_Service_Directory_UserName();
    $names->setFamilyName($user->ldap['sn']);
    $names->setGivenName($user->ldap['givenName']);
    $names->setFullName($user->name);
    $gsuite_user->setName($names);
    $gender = new \Google_Service_Directory_UserGender();
    if (!empty($user->ldap['gender'])) {
        switch ($user->ldap['gender']) {
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
    $gsuite_user->setGender($gender);
    $gsuite_user->setIsAdmin($user->is_admin ? true : false);
    if (!empty($user->ldap['o'])) {
        $orgs = array();
        $orgIds = array();
        if (is_array($user->ldap['o'])) {
            $orgs = $user->ldap['o'];
        } else {
            $orgs[] = $user->ldap['o'];
        }
        if (is_array($user->ldap['adminSchools'])) {
            $orgs = array_values(array_unique(array_merge($orgs, $user->ldap['adminSchools'])));
        }
        foreach ($orgs as $org) {
            $org_title = $user->ldap['school'][$org];
            $org_unit = $this->getOrgUnit($org);
            if (!$org_unit) {
                $org_unit = $this->createOrgUnit('/', $org, $org_title);
                if (!$org_unit) {
                    return false;
                }
            }
            $orgIds[$org] = substr($org_unit->getOrgUnitId(), 3);
        }
        if ($user->ldap['employeeType'] == '學生') {
            if (!$this->getOrgUnit($orgs[0].'/students')) {
                if (!$this->createOrgUnit('/'.$orgs[0], 'students', '學生')) {
                    return false;
                }
            }
            $gsuite_user->setOrgUnitPath('/'.$orgs[0].'/students');
        } else {
            $gsuite_user->setOrgUnitPath('/'.$orgs[0]);
        }
    }
    // Google is not support bcrypt yet!! so we can't sync password to g-suite!
    // $gsuite_user->setPassword($user->password);
    // $gsuite_user->setHashFunction('bcrypt');
    if (!$new_user) {
        $result = $this->updateUser($gmail, $gsuite_user);
    } else {
        if ($result = $this->createUser($gsuite_user)) {
            $gsuite = new Gsuite();
            $gsuite->idno = $user->idno;
            $gsuite->nameID = $nameID;
            $gsuite->primary = true;
            $gsuite->save();
        }
    }
    if ($result) {
        if (is_array($user->ldap['adminSchools'])) {
            $userID = $result->getId();
            foreach ($user->ldap['adminSchools'] as $org) {
                $orgID = $orgIds[$org];
                $this->delegatedAdmin($userID, $orgID);
            }
        }

        return true;
    }

    return false;
}

function createUserAlias($userKey, $alias)
{
    global $directory;
    $email_alias = new \Google_Service_Directory_Alias();
    $email_alias->setAlias($alias);
    try {
        return $directory->users_aliases->insert($userKey, $email_alias);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function listUserAliases($userKey)
{
    global $directory;
    try {
        return $directory->users_aliases->listUsersAliases($userKey);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function removeUserAlias($userKey, $alias)
{
    global $directory;
    try {
        return $directory->users_aliases->delete($userKey, $alias);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function listGroups()
{
    global $directory;
    try {
        return $directory->groups->listGroups();
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function listMembers($groupId)
{
    global $directory;
    try {
        return $directory->members->listMembers($groupId);
    } catch (\Google_Service_Exception $e) {
        return false;
    }
}

function addMembers($groupId, $members)
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
        }
    }

    return $users;
}
