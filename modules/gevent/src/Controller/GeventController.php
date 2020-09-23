<?php

namespace Drupal\gevent\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
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

            if (!empty($eid) && !empty($start_date) && !empty($end_date) && !empty($date_field) && !empty($entity_type)) {
                $entity = $this->entityTypeManager()->getStorage($entity_type)->load($eid);

                if (!empty($entity) && $entity->access('update')) {
                    if ($entity->hasField($date_field)) {
                        // Field definitions.
                        $fields_def = $entity->getFieldDefinition($date_field);
                        $date_type = $fields_def->getType();
                        // Datetime field.
                        if ($date_type === 'date_recur') {
                            $date_instance = $entity->get($date_field);
                            $helper = $date_instance->getHelper();
                            $generator = $helper->generateOccurrences($start_date, $end_date);
                        }
                        $entity->save();
                        // Log the content changed.
                        $this->loggerFactory->get($entity_type)->notice('%entity_type: updated %title', [
                            '%entity_type' => $entity->getEntityType()->getLabel(),
                            '%title' => $entity->label(),
                        ]);
                        // Returen 1 as success.
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
        $srat = $request->get('start', '');
        $date_field = $request->get('date_field', '');

        if (!empty($bundle) && !empty($entity_type_id)) {
            $access_control_handler = $this->entityTypeManager()->getAccessControlHandler($entity_type_id);
            // Check the user permission.
            if ($access_control_handler->createAccess($bundle)) {
                $data = [
                    'type' => $bundle,
                ];
                // Create a new event entity for this form.
                $entity = $this->entityTypeManager()
                    ->getStorage($entity_type_id)
                    ->create($data);

                if (!empty($entity)) {
                    // Add form.
                    $form = $this->entityFormBuilder()->getForm($entity);
                    // Field definitions of this entity.
                    $field_def = $entity->getFieldDefinitions();
                    // Hide those fields we don't need for this form.
                    foreach ($form as $name => &$element) {
                        switch ($name) {
                            case 'advanced':
                            case 'body':
                                $element['#access'] = false;
                        }
                        // Hide all fields that are irrelevant to the event date.
//                        if (substr($name, 0, 6) === 'field_' && $name !== $date_field && $name !== 'field_monthly_event' && $name !== 'field_weekly_event' && !$field_def[$name]->isRequired()) {
//                            $element['#access'] = false;
//                        }
                        if ($name === $date_field) {
                            $form[$date_field]['widget'][0]['value'] = $start;
                        }
                    }
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
        $build = [];
        $build['#attached']['library'][] = 'gevent/gevent_print';
        initGoogleCalendar();
        date_default_timezone_set('Asia/Taipei');
        $mydate = gevent_current_seme();
        $title = '第'.$mydate['stryear'].'學年第'.$mydate['seme'].'學期行事曆';
        $calid = \Drupal::config('gevent.settings')->get('calendar_id');
        $events = gs_listEvents($calid);
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
