<?php
$calendar = NULL;

function gevent_service() {
  global $calendar;
  $info = libraries_load('google-api-php-client');
  if (!$info['loaded']) {
    drupal_set_message(t('Can`t authenticate with google as library is missing check Status report or Readme for requirements, download from') . l('https://github.com/google/google-api-php-client/archive/master.zip', 'https://github.com/google/google-api-php-client/archive/master.zip'), 'error');
    return FALSE;
  }
  $client_email = variable_get('gapps_service_client_email');
  $file = file_load(variable_get('gapps_service_private_key'));
  $private_key = file_get_contents(drupal_realpath($file->uri));
  $user_to_impersonate = variable_get('gevent_admin');
  $scopes = array(
    'https://www.googleapis.com/auth/calendar',
  );
  $credentials = new Google_Auth_AssertionCredentials(
    $client_email,
    $scopes,
    $private_key,
    'notasecret',
    'http://oauth.net/grant_type/jwt/1.0/bearer',
    $user_to_impersonate
  );

  $client = new Google_Client();
  $client->setApplicationName('Drupal gevent module');
  $client->setAssertionCredentials($credentials);
  while ($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion();
  }

  $calendar = new Google_Service_Calendar($client);
  $_SESSION['gevent_access_token'] = $client->getAccessToken();
  if ($_SESSION['gevent_access_token']) {
    return $calendar;
  }
  else {
    return NULL;
  }
}

function gevent_test() {
  if (variable_get('gapps_web_client_id') && 
      variable_get('gapps_web_api_key') &&
      variable_get('gapps_service_client_email') &&
      variable_get('gapps_service_private_key') &&
      variable_get('gevent_domain') &&
      variable_get('gevent_admin')
     ) {
    $calendar = gevent_service();
    if ($calendar) {
      try {
        $calendars = $calendar->calendarList->listCalendarList();
      } catch (Exception $e) {
        return FALSE;
	  }
    }
    else {
      return FALSE;
    }
	return TRUE;
  }
  return FALSE;
}

function gevent_list_calendar() {
  global $calendar;
  if (!is_object($calendar)) {
    $domain = variable_get('gevent_domain');
    $calendar = gevent_service($domain);
  }
  $calendar_list = $calendar->calendarList->listCalendatList();
  return $calendar_list;
}
