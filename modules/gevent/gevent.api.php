<?php

use Drupal\Core\Entity\EntityInterface;

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

function gs_listEvents($calendarId, $opt_param = null)
{
    global $calendar;
    if (empty($opt_param)) {
        $mydate = gevent_current_seme();
        $opt_param['timeMin'] = $mydate['min'];
        $opt_param['timeMax'] = $mydate['max'];
        $opt_param['singleEvents'] = true;
        $opt_param['orderBy'] = 'startTime';
    }
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

function gs_syncEvent(EntityInterface $node)
{
    global $calendar;
    $config = \Drupal::config('gevent.settings');
    $calendar_id_field = $config->get('field_calendar_id');
    $calendar_id = $node->get($calendar_id_field)->value;
    $event_id_field = $config->get('field_event_id');
    $event_id = $node->get($event_id_field)->value;
    if (!empty($calendar_id) && !empty($event_id)) {
        $event = gs_getEvent($calendar_id, $event_id);
        if (!$event) {
            $event = new \Google_Service_Calendar_Event();
            $calendar_id = '';
            $event_id = '';
        } else {
            if ($event->getStatus() == 'cancelled') {
                $event->setStatus('confirmed');
            }
        }
    } else {
        $event = new \Google_Service_Calendar_Event();
    }
    $title_field = $config->get('field_title');
    $title = $node->getTitle();
    $event->setSummary($title);
    $memo_field = $config->get('field_memo');
    if ($memo_field != 'none') {
        $memo = $node->get($memo_field)->value;
        if (!empty($memo)) {
            $event->setDescription($memo);
        }
    }
    $place_field = $config->get('field_place');
    if ($place_field != 'none') {
        $place = $node->get($place_field)->value;
        if (!empty($place)) {
            $event->setLocation($place);
        }
    }
    $teachers_field = $config->get('field_attendee');
    if ($teachers_field != 'none') {
        $teachers = $node->get($teachers_field)->value;
        if (is_array($teachers) && count($teachers) > 0) {
            $attendees = [];
            foreach ($teachers as $delta => $uuid) {
                if ($user = get_user($uuid)) {
                    $attendee = new \Google_Service_Calendar_EventAttendee();
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
    $timezone = date_default_timezone_get();
    $timezone_service = \Drupal::service('gevent.timezone_conversion_service');
    $start_date = $timezone_service->utcToLocal($date_array['value'], $timezone, DATE_ATOM);
    $end_date = $timezone_service->utcToLocal($date_array['end_value'], $timezone, DATE_ATOM);
    $event_start = new \Google_Service_Calendar_EventDateTime();
    $event_end = new \Google_Service_Calendar_EventDateTime();
    $event_start->setTimeZone($date_array['timezone']);
    $event_end->setTimeZone($date_array['timezone']);
    $all_day = (substr($start_date, 11, 8) == '00:00:00' && substr($end_date, 11, 8) == '23:59:59') ? true : false;
    if ($all_day) {
        $event_start->setDate(substr($start_date, 0, 10));
        $event_end->setDate(substr($end_date, 0, 10));
    } else {
        $event_start->setDateTime($start_date);
        $event_end->setDateTime($end_date);
    }
    $event->setStart($event_start);
    $event->setEnd($event_end);
    if (!empty($date_array['rrule'])) {
        $event->setRecurrence($date_array['rrule']);
    }
    if (!empty($calendar_id) && !empty($event_id)) {
        $event = gs_updateEvent($calendar_id, $event_id, $event);
        if ($config->get('calendar_taxonomy')) {
            $taxonomy_field = $config->get('field_taxonomy');
            $term = $node->get($taxonomy_field)->target_id;
            $calendar_term = $config->get('calendar_term_'.$term) ?: 'none';
            if (!empty($calendar_term) && $calendar_term != 'none') {
                $event = gs_moveEvent($calendar_id, $event_id, $calendar_term);
                $calendar_id = $calendar_term;
            }
        }
    } else {
        $calendar_id = $config->get('calendar_id');
        if ($config->get('calendar_taxonomy')) {
            $taxonomy_field = $config->get('field_taxonomy');
            $term = $node->get($taxonomy_field)->target_id;
            $calendar_term = $config->get('calendar_term_'.$term) ?: 'none';
            if (!empty($calendar_term) && $calendar_term != 'none') {
                $calendar_id = $calendar_term;
            }
        }
        $event = gs_createEvent($calendar_id, $event);
    }
    if ($event) {
        $event->calendar_id = $calendar_id;
    }

    return $event;
}
