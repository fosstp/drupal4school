<?php

namespace Drupal\gevent;

/**
 * Class TimezoneService.
 */
class TimezoneService
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Return the value of the converted date from UTC date.
     */
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
