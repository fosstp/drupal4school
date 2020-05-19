<?php

$directory = null;

function gapps_service($domain)
{
    global $directory;
    $info = libraries_load('google-api-php-client');
    if (!$info['loaded']) {
        drupal_set_message(t('Can`t authenticate with google as library is missing check Status report or Readme for requirements, download from').l('https://github.com/google/google-api-php-client/archive/master.zip', 'https://github.com/google/google-api-php-client/archive/master.zip'), 'error');

        return false;
    }
    $client_email = variable_get('gapps_service_client_email');
    $file = file_load(variable_get('gapps_service_private_key'));
    $private_key = file_get_contents(drupal_realpath($file->uri));
    if ($domain == 'teacher') {
        $user_to_impersonate = variable_get('google_domain_admin');
    }
    $scopes = array(
        'https://www.googleapis.com/auth/admin.directory.orgunit',
        'https://www.googleapis.com/auth/admin.directory.group',
        'https://www.googleapis.com/auth/admin.directory.group.member',
        'https://www.googleapis.com/auth/admin.directory.user',
        'https://www.googleapis.com/auth/admin.directory.user.alias',
    );
    $credentials = new Google_Auth_AssertionCredentials(
        $client_email,
        $scopes,
        $private_key
    );
    $credentials->sub = $user_to_impersonate;

    $client = new Google_Client();
    $client->setApplicationName('Drupal gapps module');
    $client->setAssertionCredentials($credentials);
    while ($client->getAuth()->isAccessTokenExpired()) {
        $client->getAuth()->refreshTokenWithAssertion($credentials);
    }

    $directory = new Google_Service_Directory($client);
    $_SESSION['gapps_'.$domain.'_access_token'] = $client->getAccessToken();
    if ($_SESSION['gapps_'.$domain.'_access_token']) {
        return $directory;
    } else {
        return null;
    }
}

function gapps_test()
{
    if (variable_get('gapps_web_client_id') &&
      variable_get('gapps_web_api_key') &&
      variable_get('gapps_service_client_email') &&
      variable_get('gapps_service_private_key') &&
      variable_get('gapps_teacher_domain') &&
      variable_get('gapps_teacher_admin') &&
      variable_get('gapps_student_domain') &&
      variable_get('gapps_student_admin')
     ) {
        $directory = gapps_service('teacher');
        if ($directory) {
            try {
                $userkey = variable_get('gapps_teacher_admin');
                $user = $directory->users->get($userkey);
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
        $directory = gapps_service('student');
        if ($directory) {
            try {
                $userkey = variable_get('gapps_student_admin');
                $user = $directory->users->get($userkey);
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    return false;
}

function gapps_change_uid($domain, $username, $new_account)
{
    $directory = gapps_service($domain);
    if ($domain == 'teacher') {
        $user_key = $username.'@'.variable_get('gapps_teacher_domain');
        $primary_email = $new_account.'@'.variable_get('gapps_teacher_domain');
    } else {
        $user_key = $username.'@'.variable_get('gapps_student_domain');
        $primary_email = $new_account.'@'.variable_get('gapps_student_domain');
    }
    $user = $directory->users->get($user_key);
    $user->setPrimaryEmail($primary_email);
    $response = $directory->users->patch($user_key, $user);
}

function gapps_change_pass($domain, $username, $password)
{
    $directory = gapps_service($domain);
    if ($domain == 'teacher') {
        $user_key = $username.'@'.variable_get('gapps_teacher_domain');
    } else {
        $user_key = $username.'@'.variable_get('gapps_student_domain');
    }
    $user = $directory->users->get($user_key);
    $user->setHashFunction('SHA-1');
    $user->setPassword(sha1($password));
    $response = $directory->users->patch($user_key, $user);
}
