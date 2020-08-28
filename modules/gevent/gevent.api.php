<?php

$calendar = null;

function initGoogleCalendar()
{
    global $calendar;
    if ($calendar instanceof \Google_Service_Calendar) {
        return $calendar;
    } else {
        $uri = \Drupal::config('gsync.settings')->get('google_service_json');
        $path = \Drupal::service('file_system')->realpath($uri);
        $user_to_impersonate = \Drupal::config('gevent.settings')->get('calendar_owner');
        $scopes = [
            \Google_Service_Directory::ADMIN_DIRECTORY_ORGUNIT,
            \Google_Service_Directory::ADMIN_DIRECTORY_USER,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP,
            \Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
            \Google_Service_Calendar::CALENDAR,
            \Google_Service_Calendar::CALENDAR_EVENTS,
        ];

        $client = new \Google_Client();
        $client->setAuthConfig($path);
        $client->setApplicationName('Drupal for School');
        $client->setScopes($scopes);
        $client->setSubject($user_to_impersonate);
        try {
            $calendar = new \Google_Service_Calendar($client);

            return $calendar;
        } catch (\Google_Service_Exception $e) {
            \Drupal::logger('google')->debug('calendar:'.$e->getMessage());

            return null;
        }
    }
}

function gs_listCalendars()
{
    global $calendar;
    try {
        return $calendar->calendarList->listCalendarList();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_listCalendars:'.$e->getMessage());

        return false;
    }
}
