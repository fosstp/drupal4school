<?php

namespace Drupal\gevent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class GeventController extends ControllerBase
{
    public function todolist(Request $request)
    {
        $form = [];
        $form['#attached']['library'][] = 'gevent/gevent_print';
        date_default_timezone_set('Asia/Taipei');
        $mydate = gevent_current_seme();
        $title = '第'.$mydate['stryear'].'學年第'.$mydate['seme'].'學期行事曆';
        $events = gs_listEvents();
        $build['todolist'] = [
            '#theme' => 'gevent_todolist',
            '#title' => $title,
            '#syear' => $mydate['syear'],
            '#eyear' => $mydate['eyear'],
            '#seme' => $mydate['seme'],
            '#events' => $events,
        ];

        return $build;
    }
}
