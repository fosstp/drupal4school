<?php

$calendar = null;

function gevent_current_seme()
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
    $mydate = gevent_current_seme();
    $opt_param['timeMin'] = $mydate['min'];
    $opt_param['timeMax'] = $mydate['max'];
    $opt_param['singleEvents'] = true;
    $opt_param['orderBy'] = 'startTime';
    try {
        $events = $calendar->events->listEvents($calendarId, $opt_param);

        return $events->getItems();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_listEvents($calendarId):".$e->getMessage());

        return false;
    }
}

function gs_getEvent($calendarId, $eventId)
{
    global $calendar;
    try {
        return $calendar->events->get($calendarId, $eventId);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_getEvent($calendarId, $eventId):".$e->getMessage());

        return false;
    }
}

function gs_moveEvent($calendarId, $eventId, $target)
{
    global $calendar;
    try {
        return $calendar->events->move($calendarId, $eventId, $target);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_moveEvent($calendarId, $eventId, $target):".$e->getMessage());

        return false;
    }
}

function gs_deleteEvent($calendarId, $eventId)
{
    global $calendar;
    try {
        $calendar->events->delete($calendar_id, $event_id);

        return true;
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_deleteEvent($calendarId, $eventId):".$e->getMessage());

        return false;
    }
}

function gs_createEvent($calendarId, $event)
{
    global $calendar;
    try {
        return $calendar->events->insert($calendarId, $event);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_createEvent($calendarId,".var_export($event, true).'):'.$e->getMessage());

        return false;
    }
}

function gs_updateEvent($calendarId, $eventId, $event)
{
    global $calendar;
    try {
        $updatedEvent = $calendar->events->update($calendarId, $eventId, $event);

        return $updatedEvent->getUpdated();
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_updateEvent($calendarId,$eventId,".var_export($event, true).'):'.$e->getMessage());

        return false;
    }
}

function gs_syncEvent($node)
{
    initGoogleCalendar();
    $config = \Drupal::config('gevent.settings');
    $calendar_id_field = $config->get('field_calendar_id');
    if (substr($calendar_id_field, 0, 6) == 'field_') {
        $calendar_id_obj = $node->$calendar_id_field;
        if (isset($calendar_id_obj['und'][0])) {
            $calendar_id = $calendar_id_obj['und'][0]['value'];
        }
    } else {
        $calendar_id = $node->$calendar_id_field;
    }
    $event_id_field = $config->get('field_event_id');
    if (substr($event_id_field, 0, 6) == 'field_') {
        $event_id_obj = $node->$event_id_field;
        if (isset($event_id_obj['und'][0])) {
            $event_id = $event_id_obj['und'][0]['value'];
        }
    } else {
        $event_id = $node->$event_id_field;
    }
    if (!empty($calendar_id) && !empty($event_id)) {
        $event = gs_getEvent($calendar_id, $event_id);
        if (!$event) {
            $event = new Google_Service_Calendar_Event();
        }
    } else {
        $event = new Google_Service_Calendar_Event();
    }
    $title_field = $config->get('field_title');
    if (substr($title_field, 0, 6) == 'field_') {
        $title_obj = $node->$title_field;
        if (isset($title_obj['und'][0])) {
            $title = $title_obj['und'][0]['value'];
        }
    } else {
        $title = $node->$title_field;
    }
    if (!empty($title)) {
        $event->setSummary($title);
    }
    $memo_field = $config->get('field_memo');
    if ($memo_field != 'none') {
        if (substr($memo_field, 0, 6) == 'field_') {
            $memo_obj = $node->$memo_field;
            if (isset($memo_obj['und'][0])) {
                $memo = $memo_obj['und'][0]['value'];
            }
        } else {
            $memo = $node->$memo_field;
        }
        if (!empty($memo)) {
            $event->setDescription($memo);
        }
    }
    $place_field = $config->get('field_place');
    if ($place_field != 'none') {
        if (substr($place_field, 0, 6) == 'field_') {
            $place_obj = $node->$place_field;
            if (isset($place_obj['und'][0])) {
                $place = $place_obj['und'][0]['value'];
            }
        } else {
            $place = $node->$place_field;
        }
        if (!empty($place)) {
            $event->setLocation($place);
        }
    }
    $teachers_field = $config->get('field_attendee');
    if ($teachers_field != 'none') {
        if (substr($teachers_field, 0, 6) == 'field_') {
            $teachers_obj = $node->$teachers_field;
            $teachers = $teachers_obj['und'];
        }
        if (count($teachers) > 0) {
            $attendees = [];
            foreach ($teachers as $delta => $value) {
                $uuid = $value['value'];
                if ($user = get_user($uuid)) {
                    $attendee = new Google_Service_Calendar_EventAttendee();
                    $attendee->setId($uuid);
                    $attendee->setEmail($user->email);
                    $attendee->setDisplayName($user->realname);
                    $attendees[] = $attendee;
                }
            }
            if (count($attendees) > 0) {
                $event->setAttendees($attendees);
            }
        }
    }

    $date_field = $config->get('field_date');
    $date_obj = $node->$date_field;
    $timezone = $date_obj['und'][0]['timezone'];
    $event_start = new Google_Service_Calendar_EventDateTime();
    $event_end = new Google_Service_Calendar_EventDateTime();
    $event_start->setTimeZone($timezone);
    $event_end->setTimeZone($timezone);
    $start_date = date_create($date_obj['und'][0]['value'], timezone_open('UTC'));
    date_timezone_set($start_date, timezone_open($timezone));
    $end_date = date_create($date_obj['und'][0]['value2'], timezone_open('UTC'));
    date_timezone_set($end_date, timezone_open($timezone));
    if ($date_obj['und'][0]['all_day'] == 1) {
        $event_start->setDate(date_format($start_date, 'Y-m-d'));
        $event_end->setDate(date_format($end_date, 'Y-m-d'));
    } else {
        $event_start->setDateTime(date_format($start_date, 'Y-m-d\TH:i:sP'));
        $event_end->setDateTime(date_format($end_date, 'Y-m-d\TH:i:sP'));
    }
    $event->setStart($event_start);
    $event->setEnd($event_end);
    $rrule = $date_obj['und'][0]['rrule'];
    $event->setRecurrence($rrule);

    $user = user_load($node->uid);
    $creator = new Google_Service_Calendar_EventCreator();
    $creator->setId($user->uuid);
    if (isset($user->email)) {
        $creator->setEmail($user->email);
    }
    $creator->setDisplayName($user->dept_name.' '.$user->realname);
    $event->setCreator($creator);
    if ($config->get('calendar_taxonomy')) {
        $taxonomy_field = $config->get('field_taxonomy');
        $term_obj = $node->$taxonomy_field;
        $term = $term_obj['und'][0]['tid'];
        $calendar_newid = $config->get('calendar_term_'.$term);
    } else {
        $calendar_newid = $config->get('calendar_id');
    }
    if (!empty($calendar_id) && !empty($event_id)) {
        if ($calendar_newid != $calendar_id) {
            gs_updateEvent($calendar_id, $event_id, $event);

            return gs_moveEvent($calendar_id, $event_id, $calendar_newid);
        } else {
            return gs_updateEvent($calendar_id, $event_id, $event);
        }
    } else {
//        $summary = gs_getCalendar($calendar_id)->getSummary();
//        $organizer = new Google_Service_Calendar_EventOrganizer();
//        $organizer->setEmail($calendar_id);
//        $organizer->setDisplayName($summary);
//        $event->setOrganizer($organizer);

        return gs_createEvent($calendar_newid, $event);
    }
}
