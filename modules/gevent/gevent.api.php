<?php

$calendar = null;

function current_seme()
{
    if (date('m') > 7) {
        $year = date('Y');
        $nextyear = $year++;
        $min = "$year-08-01T00:00:00+08:00";
        $max = "$nextyear-01-31T00:00:00+08:00";
    } elseif (date('m') < 2) {
        $year = date('Y');
        $lastyear = $year--;
        $min = "$lastyear-08-01T00:00:00+08:00";
        $max = "$year-01-31T00:00:00+08:00";
    } else {
        $year = date('Y');
        $min = "$year-02-01T00:00:00+08:00";
        $max = "$year-07-31T00:00:00+08:00";
    }

    return ['min' => $min, 'max' => $max];
}

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
        $list = $calendar->calendarList->listCalendarList();

        return $list->getItems();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_listCalendars:'.$e->getMessage());

        return false;
    }
}

function gs_getCalendar($calendarId)
{
    global $calendar;
    try {
        return $calendar->calendars->get($calendarId);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_getCalendar($calendarId):".$e->getMessage());

        return false;
    }
}

function gs_deleteCalendar($calendarId)
{
    global $calendar;
    try {
        $calendar->calendars->delete($calendarId);

        return true;
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_deleteCalendar($calendarId):".$e->getMessage());

        return false;
    }
}

function gs_createCalendar($description)
{
    global $calendar;
    $cObj = new \Google_Service_Calendar_Calendar();
    $cObj->setSummary($description);
    $cObj->setTimeZone('Asia/Taipei');
    try {
        $created = $calendar->calendars->insert($cObj);

        return $created->getId();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug('gs_createCalendar('.var_export($cObj, true).'):'.$e->getMessage());

        return false;
    }
}

function gs_updateCalendar($calendarId, $description)
{
    global $calendar;
    $cObj = gs_getCalendar($calendarId);
    $cObj->setSummary($description);
    try {
        $calendar->calendars->update($calendarId, $cObj);

        return true;
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_updateCalendar($calendarId,".var_export($cObj, true).'):'.$e->getMessage());

        return false;
    }
}

function gs_pruneEvents($calendarId)
{
    global $calendar;
    try {
        $calendar->calendars->clear($calendarId);

        return true;
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_pruneEvents($calendarId):".$e->getMessage());

        return false;
    }
}

function gs_listEvents($calendarId)
{
    global $calendar;
    $mydate = current_seme();
    $opt_param['timeMin'] = $mydate['min'];
    $opt_param['timeMax'] = $mydate['max'];
    try {
        $events = $calendar->$service->events->listEvents($calendarId, $opt_param);

        return $events->getItems();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_listEvents($calendarId):".$e->getMessage());

        return false;
    }
}
