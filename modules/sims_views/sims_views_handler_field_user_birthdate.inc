<?php

/**
 * @file
 * Definition of simsauth_handler_field_user_birthdate.
 */

/**
 * Field handler to provide simple renderer that allows using a themed user link.
 */
class sims_views_handler_field_user_birthdate extends views_handler_field_date {

  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
  }

  function render($values) {
    $birthdate = NULL;
    if (module_exists('simsauth')) {
      if ($uid = $this->get_value($values)) {
        $account = user_load($uid);
        $birthdate = strtotime($account->birthyear . '/' . $account->birthmonth . '/' . $account->birthday);
      }
    }
    $format = $this->options['date_format'];
    if (in_array($format, array('custom', 'raw time ago', 'time ago', 'today time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span'))) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($birthdate) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      $time_diff = REQUEST_TIME - $birthdate;
      switch ($format) {
        case 'raw time ago':
          return format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time ago':
          return t('%time ago', array('%time' => format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'today time ago':
          $second_format = $this->options['second_date_format'];
          $second_custom_format = $this->options['second_date_format_custom'];
          if (format_date(REQUEST_TIME, 'custom', 'Y-m-d', $timezone) == format_date($birthdate, 'custom', 'Y-m-d', $timezone)) {
            return t('%time ago', array('%time' => format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2)));
          }
          elseif ($second_format == 'custom') {
            if ($second_custom_format == 'r') {
              return format_date($birthdate, $second_format, $second_custom_format, $timezone, 'en');
            }
            return format_date($birthdate, $second_format, $second_custom_format, $timezone);
          }
          else {
            return format_date($birthdate, $this->options['second_date_format'], '', $timezone);
          }
        case 'raw time hence':
          return format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time hence':
          return t('%time hence', array('%time' => format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'inverse time span':
          return ($time_diff > 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'time span':
	  if ($time_diff < 0) {
	    return t('%time hence', array('%time' => format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2)));
          }
	  else {
	    return t('%time ago', array('%time' => format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2)));
	  }
	case 'custom':
          if ($custom_format == 'r') {
            return format_date($birthdate, $format, $custom_format, $timezone, 'en');
          }
          return format_date($birthdate, $format, $custom_format, $timezone);
        default:
          return format_date($birthdate, $format, '', $timezone);
      }
    }
  }
}
