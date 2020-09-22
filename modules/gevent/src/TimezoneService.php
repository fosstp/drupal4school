<?php

namespace Drupal\gevent;

class TimezoneService
{
    public function __construct()
    {
    }

    public function utcToLocal($utc_date, $local_timezone, $format = DATE_ATOM, $offset = '')
    {
        // UTC timezone.
        $utc = new \DateTimeZone('UTC');
        // Local time zone.
        $localTZ = new \DateTimeZone($local_timezone);
        // Date object in UTC timezone.
        $date = new \DateTime($utc_date, $utc);
        $date->setTimezone($localTZ);

        if (!empty($offset)) {
            $date->modify($offset);
        }

        return $date->format($format);
    }
}
