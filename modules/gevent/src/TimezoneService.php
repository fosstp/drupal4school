<?php

namespace Drupal\gevent;

class TimezoneService
{
    public function __construct()
    {
    }

    public function utcToLocal($utc_date, $local_timezone, $format = DATE_ATOM, $offset = '')
    {
        if (empty($local_timezone)) {
            $local_timezone = date_default_timezone_get();
        }
        $utc = new \DateTimeZone('UTC');
        $localTZ = new \DateTimeZone($local_timezone);
        $date = new \DateTime($utc_date, $utc);
        $date->setTimezone($localTZ);
        if (!empty($offset)) {
            $date->modify($offset);
        }

        return $date->format($format);
    }

    public function localToUtc($local_date, $local_timezone, $format = DATE_ATOM, $offset = '')
    {
        if (empty($local_timezone)) {
            $local_timezone = date_default_timezone_get();
        }
        $utc = new \DateTimeZone('UTC');
        $localTZ = new \DateTimeZone($local_timezone);
        $date = new \DateTime($local_date, $localTZ);
        $date->setTimezone($utc);
        if (!empty($offset)) {
            $date->modify($offset);
        }

        return $date->format($format);
    }
}
