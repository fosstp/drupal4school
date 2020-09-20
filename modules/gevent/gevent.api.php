<?php

$calendar = null;

function gevent_current_seme()
{
    if (date('m') > 7) {
        $syear = date('Y');
        $eyear = $syear++;
        $stryear = $syear - 1912;
        $seme = 1;
        $min = "$syear-08-01T00:00:00+08:00";
        $max = "$eyear-01-31T00:00:00+08:00";
    } elseif (date('m') < 2) {
        $eyear = date('Y');
        $syear = $eyear--;
        $stryear = $syear - 1912;
        $seme = 1;
        $year = date('Y');
        $lastyear = $year--;
        $min = "$syear-08-01T00:00:00+08:00";
        $max = "$eyear-01-31T00:00:00+08:00";
    } else {
        $syear = date('Y');
        $eyear = $syear;
        $stryear = $syear - 1913;
        $seme = 2;
        $min = "$syear-02-01T00:00:00+08:00";
        $max = "$eyear-07-31T00:00:00+08:00";
    }

    return ['min' => $min, 'max' => $max, 'syear' => $syear, 'eyear' => $eyear, 'stryear' => $stryear, 'seme' => $seme];
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
        $cals = $calendar->calendarList->listCalendarList();

        return $cals->getItems();
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

function gs_importEvent($calendarId, $event)
{
    global $calendar;
    try {
        return $calendar->events->import($calendarId, $event);
    } catch (\Google_Service_Exception $e) {
        \Drupal::logger('google')->debug("gs_importEvent($calendarId,".var_export($event, true).'):'.$e->getMessage());

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
    $calendar_id = $node->get($calendar_id_field)->getValue();
    $event_id_field = $config->get('field_event_id');
    $event_id = $node->get($event_id_field)->getValue();
    if (!empty($calendar_id) && !empty($event_id)) {
        $event = gs_getEvent($calendar_id, $event_id);
        if (!$event) {
            $event = new Google_Service_Calendar_Event();
        }
    } else {
        $event = new Google_Service_Calendar_Event();
    }
    $title_field = $config->get('field_title');
    $title = $node->get($title_field)->getValue();
    $event->setSummary($title);
    $memo_field = $config->get('field_memo');
    if ($memo_field != 'none') {
        $memo = $node->get($memo_field)->getValue();
        if (!empty($memo)) {
            $event->setDescription($memo);
        }
    }
    $place_field = $config->get('field_place');
    if ($place_field != 'none') {
        $place = $node->get($place_field)->getValue();
        if (!empty($place)) {
            $event->setLocation($place);
        }
    }
    $teachers_field = $config->get('field_attendee');
    if ($teachers_field != 'none') {
        $teachers = $node->get($teachers_field)->getValue();
        if (count($teachers) > 0) {
            $attendees = [];
            foreach ($teachers as $delta => $uuid) {
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
    $date_array = $node->get($date_field)->getValue()[0];
    $event_start = new Google_Service_Calendar_EventDateTime();
    $event_end = new Google_Service_Calendar_EventDateTime();
    $event_start->setTimeZone('Asia/Teipei');
    $event_end->setTimeZone('Asia/Teipei');
    $start_date = $date_array->getStart();
    $end_date = $date_array->getEnd();
    if ($date_array['all_day'] == 1) {
        $event_start->setDate(date_format($start_date, 'Y-m-d'));
        $event_end->setDate(date_format($end_date, 'Y-m-d'));
    } else {
        $event_start->setDateTime(date_format($start_date, 'Y-m-d\TH:i:sP'));
        $event_end->setDateTime(date_format($end_date, 'Y-m-d\TH:i:sP'));
    }
    $event->setStart($event_start);
    $event->setEnd($event_end);
    if ($date_array->isRecurring()) {
        $helper = $date_array->getHelper();
        $rrule = $helper->getRules();
        $event->setRecurrence($rrule);
    }

    $user = user_load($node->uid);
    $creator = new Google_Service_Calendar_EventCreator();
    $creator->setId($user->uuid);
    if (isset($user->email)) {
        $creator->setEmail($user->email);
    }
    $dept_field = $config->get('field_department');
    $department = $node->get($dept_field)->getValue();
    $creator->setDisplayName($department);
    $event->setCreator($creator);
    $calendar_newid = $config->get('calendar_id');
    if (!empty($calendar_id) && !empty($event_id)) {
        if ($calendar_newid != $calendar_id) {
            gs_updateEvent($calendar_id, $event_id, $event);
            $event = gs_moveEvent($calendar_id, $event_id, $calendar_newid);
        } else {
            $event = gs_updateEvent($calendar_id, $event_id, $event);
        }
    } else {
//        $summary = gs_getCalendar($calendar_id)->getSummary();
//        $organizer = new Google_Service_Calendar_EventOrganizer();
//        $organizer->setEmail($calendar_id);
//        $organizer->setDisplayName($summary);
//        $event->setOrganizer($organizer);

        $event = gs_createEvent($calendar_newid, $event);
    }
    if ($config->get('calendar_taxonomy')) {
        $taxonomy_field = $config->get('field_taxonomy');
        $term = $node->get($taxonomy_field)->getValue();
        $calendar_newid = $config->get('calendar_term_'.$term);
    }
    $event->setICalUID('originalUID');
    gs_importEvent($calendar_newid, $event);

    return $event;
}
