<?php

namespace Drupal\gevent\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GeventController extends ControllerBase
{
    protected $csrfToken;

    public function __construct(LoggerChannelFactoryInterface $loggerFactory, CsrfTokenGenerator $csrfToken)
    {
        $this->loggerFactory = $loggerFactory;
        $this->csrfToken = $csrfToken;
    }

    public static function create(ContainerInterface $container)
    {
        $loggerFactory = $container->get('logger.factory');
        $csrfToken = $container->get('csrf_token');

        return new static($loggerFactory, $csrfToken);
    }

    public function updateEvent(Request $request)
    {
        $user = $this->currentUser();
        if (!empty($user)) {
            $csrf_token = $request->request->get('token');
            if (!$this->csrfToken->validate($csrf_token, $user->id())) {
                return new Response($this->t('Access denied!'));
            }

            $eid = $request->request->get('eid', '');
            $entity_type = $request->request->get('entity_type', '');
            $start_date = $request->request->get('start', '');
            $end_date = $request->request->get('end', '');
            $date_field = $request->request->get('date_field', '');

            if (!empty($eid) && !empty($start_date) && !empty($date_field) && !empty($entity_type)) {
                $entity = $this->entityTypeManager()->getStorage($entity_type)->load($eid);

                if (!empty($entity) && $entity->access('update')) {
                    if ($entity->hasField($date_field)) {
                        $timezone_service = \Drupal::service('gevent.timezone_conversion_service');
                        $field_type = $entity->getFieldDefinition($date_field)->getType();
                        if ($field_type === 'date_recur') {
                            $date_instance = $entity->get($date_field)[0];
                            $values = $date_instance->getValue();
                            $timezone = $values['timezone'];
                            $values['value'] = $timezone_service->localToUtc($start_date, $timezone, DATE_ATOM);
                            if (!empty($end_date)) {
                                $values['end_value'] = $timezone_service->localToUtc($end_date, $timezone, DATE_ATOM);
                            } else {
                                $values['end_value'] = $timezone_service->localToUtc(substr($start_date, 0, 10).' 23:59:59', $timezone, DATE_ATOM);
                            }
                            $values['value'] = substr($values['value'], 0, 19);
                            $values['end_value'] = substr($values['end_value'], 0, 19);
                            $date_instance->setValue($values);
                            $entity->save();
                        }

                        return new Response(1);
                    }
                } else {
                    return new Response($this->t('Access denied!'));
                }
            } else {
                return new Response($this->t('Parameter Missing.'));
            }
        } else {
            return new Response($this->t('Invalid User!'));
        }
    }

    public function addEvent(Request $request)
    {
        $entity_type_id = $request->get('entity', '');
        $bundle = $request->get('bundle', '');
        $start = $request->get('start', '');
        $date_field = $request->get('date_field', '');

        if (!empty($bundle) && !empty($entity_type_id)) {
            $access_control_handler = $this->entityTypeManager()->getAccessControlHandler($entity_type_id);
            // Check the user permission.
            if ($access_control_handler->createAccess($bundle)) {
                $data = [
                    'type' => $bundle,
                ];
                // Create a new event entity for this form.
                $entity = $this->entityTypeManager()->getStorage($entity_type_id)->create($data);
                if (!empty($entity) && $entity->hasField($date_field)) {
                    $form = $this->entityFormBuilder()->getForm($entity);
                    $form['advanced']['#access'] = false;
                    $form['body']['#access'] = false;
                    $field_def = $entity->getFieldDefinitions();
                    foreach ($form as $name => &$element) {
                        // Hide all fields that are irrelevant to the event date.
                        if (substr($name, 0, 6) === 'field_' && $name !== $date_field && !$field_def[$name]->isRequired()) {
                            $element['#access'] = false;
                        }
                    }
                    if (!empty($start)) {
                        $date_type = $field_def[$date_field]->getType();
                        if ($date_type === 'date_recur') {
                            $form_display = $this->entityTypeManager()->getStorage('entity_form_display')->load("$entity_type_id.$bundle.default");
                            $widget = $form_display->getRenderer($date_field)->getPluginId();
                            $all_day = (strlen($start) < 11) ? true : false;
                            $s = new DrupalDateTime($start, date_default_timezone_get());
                            $e = new DrupalDateTime($start, date_default_timezone_get());
                            $e->add(new \DateInterval('PT1H'));
                            switch ($widget) {
                                case 'date_recur_modular_alphas':
                                    $form[$date_field]['widget'][0]['start']['#value'] = $s->format('Y-m-d H:i:s');
                                    $form[$date_field]['widget'][0]['end']['#value'] = $e->format('Y-m-d H:i:s');
                                    break;
                                case 'date_recur_modular_oscar':
                                    $form[$date_field]['widget'][0]['day_start']['#value'] = $s->format('Y-m-d');
                                    if ($all_day) {
                                        $form[$date_field]['widget'][0]['is_all_day']['#value'] = 'all-day';
                                    } else {
                                        $form[$date_field]['widget'][0]['is_all_day']['#value'] = 'partial';
                                        $form[$date_field]['widget'][0]['times']['time_start']['#value'] = $s->format('H:i:s');
                                        $form[$date_field]['widget'][0]['times']['time_end']['#value'] = $e->format('H:i:s');
                                    }
                                    break;
                                case 'date_recur_modular_sierra':
                                    $form[$date_field]['widget'][0]['day_start']['#value'] = $s->format('Y-m-d');
                                    $form[$date_field]['widget'][0]['day_end']['#value'] = $e->format('Y-m-d');
                                    if (!$all_day) {
                                        $form[$date_field]['widget'][0]['time_start']['#value'] = $s->format('H:i:s');
                                        $form[$date_field]['widget'][0]['time_end']['#value'] = $e->format('H:i:s');
                                    }
                                    break;
                            }
                        }
                    }
                    //
                    // Hide preview button.
                    if (isset($form['actions']['preview'])) {
                        $form['actions']['preview']['#access'] = false;
                    }
                    // Move the Save button to the bottom of this form.
                    $form['actions']['#weight'] = 10000;

                    return $form;
                }
            }
        }
        // Return access denied for users don't have the permission.
        throw new AccessDeniedHttpException();
    }

    public function todolist(Request $request)
    {
        $calendar_id = $request->get('calendar', '');
        $build = [];
        $build['#attached']['library'][] = 'gevent/gevent_print';
        initGoogleCalendar();
        $calendars = gs_listCalendars();
        foreach ($calendars as $calendarListEntry) {
            $calId = $calendarListEntry->getId();
            if (!strpos($calid, '#holiday') && !strpos($calid, '#contacts')) {
                $my_calendars[$calId] = $calendarListEntry->getSummary();
                if (empty($calendar_id)) {
                    $calendar_id = $calendarListEntry->getId();
                }
            }
        }
        date_default_timezone_set('Asia/Taipei');
        $mydate = gevent_current_seme();
        $title = '第'.$mydate['stryear'].'學年第'.$mydate['seme'].'學期';
        $events = gs_listEvents($calendar_id);
        $build['todolist'] = [
            '#theme' => 'gevent_todolist',
            '#title' => $title,
            '#syear' => $mydate['syear'],
            '#eyear' => $mydate['eyear'],
            '#seme' => $mydate['seme'],
            '#calendars' => $my_calendars,
            '#current' => $calendar_id,
            '#events' => $events,
        ];

        return $build;
    }
}
