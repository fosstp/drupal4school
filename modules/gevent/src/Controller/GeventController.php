<?php

namespace Drupal\gevent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GeventController extends ControllerBase
{
    public function handle(Request $request)
    {
        $form = [];
        $form['#attached']['library'][] = 'gevent/gevent_print';
        date_default_timezone_set('Asia/Taipei');
        $mydate = current_seme();
        $events = gs_listEvents();
        $node_type = variable_get('gevent_node_type');
        $date_field_name = variable_get('gevent_field_date');
        $date_field = field_info_field($date_field_name);
        $date_storage = $date_field['storage']['details']['sql']['FIELD_LOAD_CURRENT'];
        $date_table = key($date_storage);
        $value1_field = $date_storage[$date_table]['value'];
        $value2_field = $date_storage[$date_table]['value2'];
        $rrule_field = $date_storage[$date_table]['rrule'];
        $output = '<TABLE class="views-table cols-4">';
        $output .= '<thead><TR>';
        $output .= '<Th class="views-field" colspan="4" style="text-align: center; font-size: 150%">第'.$mydate['year'].'學年第'.$mydate['seme'].'學期行事曆 <a href="javascript:window.print()">友善列印</a></Th>';
        $output .= '</TR><TR>';
        $output .= '<Th class="views-field" width="16" style="text-align: center;">月</Th>';
        $output .= '<Th class="views-field" width="16" style="text-align: center;">日</Th>';
        $output .= '<Th class="views-field" width="32" style="text-align: center;">星期</Th>';
        $output .= '<Th class="views-field" style="text-align: center;">行　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　事</Th>';
        $output .= '</TR></thead><tbody>';
        for ($y = $syear; $y <= $eyear; ++$y) {
            if ($seme == 1) {
                if ($y == $syear) {
                    $ms = 8;
                    $me = 12;
                } else {
                    $ms = 1;
                    $me = 1;
                }
            } else {
                $ms = 2;
                $me = 7;
            }
            for ($m = $ms; $m <= $me; ++$m) {
                $days = date('j', strtotime('last day of this month', strtotime("$y-$m-02")));
                for ($d = 1; $d <= $days; ++$d) {
                    $meeting = date('w', mktime(0, 0, 0, $m, $d, $y));
                    switch ($meeting) {
              case 0:
                $cweekday = '<TD align=center bgcolor=#FF0000><font color=#FFFFFF>日</font></TD>';
                break;
              case 1:
                $cweekday = '<TD align=center>一</TD>';
                break;
              case 2:
                $cweekday = '<TD align=center>二</TD>';
                break;
              case 3:
                $cweekday = '<TD align=center>三</TD>';
                break;
              case 4:
                $cweekday = '<TD align=center>四</TD>';
                break;
              case 5:
                $cweekday = '<TD align=center>五</TD>';
                break;
              case 6:
                $cweekday = '<TD align=center bgcolor=#FF0000><font color=#FFFFFF>六</font></TD>';
                break;
            }
                    $output .= '<TR>';
                    $output .= "<TD align=center>$m</TD>";
                    $output .= "<TD align=center>$d</TD>";
                    $output .= $cweekday.'<td>';
                    $events = [];
                    $day_offset = date_create("$y-$m-$d", timezone_open('Asia/Taipei'));
                    $sdate = $day_offset->format('Y-m-d');
                    $sql = "SELECT a.nid AS nid, a.uid AS uid FROM {node} a LEFT JOIN {$date_table} b ON a.nid = b.entity_id AND (b.entity_type = 'node' AND b.deleted = '0') WHERE (a.status = '1') AND (a.type = '$node_type') AND (b.$value1_field <= '$sdate') AND (b.$value2_field >= '$sdate') ORDER BY b.$value1_field ASC";
                    $result = db_query($sql);
                    foreach ($result as $row) {
                        $node = node_load($row->nid);
                        if (!node_access('view', $node)) {
                            continue;
                        }
                        $account = user_load($row->uid);
                        $title_field = variable_get('gevent_field_title');
                        if (substr($title_field, 0, 6) == 'field_') {
                            $title_obj = $node->$title_field;
                            if (count($title_obj) > 0) {
                                $title = $title_obj['und'][0]['value'];
                            } else {
                                $title = '';
                            }
                        } else {
                            $title = $node->$title_field;
                        }
                        $memo_field = variable_get('gevent_field_memo');
                        if (substr($memo_field, 0, 6) == 'field_') {
                            $memo_obj = $node->$memo_field;
                            if (count($memo_obj) > 0) {
                                $memo = $memo_obj['und'][0]['value'];
                            } else {
                                $memo = '';
                            }
                        } else {
                            $memo = $node->$memo_field;
                        }
                        $place_field = variable_get('gevent_field_place');
                        if (substr($place_field, 0, 6) == 'field_') {
                            $place_obj = $node->$place_field;
                            if (count($place_obj) > 0) {
                                $place = $place_obj['und'][0]['value'];
                            } else {
                                $place = '';
                            }
                        } else {
                            $place = $node->$place_field;
                        }
                        $date_obj_field = $node->$date_field_name;
                        $date1_obj = $date_obj_field['und'][0]['db']['value'];
                        $date2_obj = $date_obj_field['und'][0]['db']['value2'];
                        $date1_obj->setTimezone(new DateTimeZone('Asia/Taipei'));
                        $date2_obj->setTimezone(new DateTimeZone('Asia/Taipei'));
                        $time1 = $date1_obj->format('U');
                        $time2 = $date2_obj->format('U');
                        $show = $title;
                        if ($time1 == $time2) {
                            $show .= '(全天)';
                        } else {
                            $start = $date1_obj->toArray();
                            $end = $date2_obj->toArray();
                            if ($start['year'] == $end['year']) {
                                if ($start['month'] == $end['month']) {
                                    if ($start['day'] == $end['day']) {
                                        $show .= '('.$date1_obj->format('H:i').'到'.$date2_obj->format('H:i').'止)';
                                    } else {
                                        $show .= '('.$date1_obj->format('j日').'到'.$date2_obj->format('j日').'止)';
                                    }
                                } else {
                                    $show .= '('.$date1_obj->format('n月j日').'到'.$date2_obj->format('n月j日').'止)';
                                }
                            } else {
                                $show .= '('.$date1_obj->format('Y年n月j日').'到'.$date2_obj->format('Y年n月j日').'止)';
                            }
                        }
                        if ($place) {
                            $show .= ' 地點：'.$place;
                        }
                        if ($memo) {
                            $show .= ' 備註：'.$memo;
                        }
                        $found = false;
                        foreach ($events as $key => $event) {
                            if ($event->depname == $account->depname) {
                                $events[$key]->show .= $show;
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $event = new stdClass();
                            $event->depname = $account->depname;
                            $event->show = $show;
                            $events[] = $event;
                        }
                    }
                    foreach ($events as $key => $event) {
                        $output .= '【<span style="color:red;">'.$event->depname.'</span>】'.$event->show;
                    }
                }
                $output .= '</TD>';
                $day_offset = date_add($day_offset, date_interval_create_from_date_string('1 day'));
            }
        }
        $output .= '</tbody></table>';

        return $output;
    }
}
