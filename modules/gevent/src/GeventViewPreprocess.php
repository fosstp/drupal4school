<?php

namespace Drupal\gevent;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class GeventViewPreprocess
{
    use StringTranslationTrait;

    protected static $viewIndex = 0;

    public function process(array &$variables)
    {
        /* @var \Drupal\views\ViewExecutable $view */
        $view = $variables['view'];
        // View index.
        $view_index = self::$viewIndex++;
        $style = $view->style_plugin;
        $options = $style->options;
        $fields = $view->field;

        // Get current language.
        $language = \Drupal::languageManager()->getCurrentLanguage();

        // Current user.
        $user = $variables['user'];
        // CSRF token.
        $token = '';
        if (!$user->isAnonymous()) {
            $token = \Drupal::csrfToken()->get($user->id());
        }
        //
        // New event bundle type.
        $event_bundle_type = $options['bundle_type'];
        $entity_type = $view->getBaseEntityType();
        if ($entity_type->id() === 'node') {
            $add_form = 'event-add';
        } else {
            $entity_links = $entity_type->get('links');
            if (isset($entity_links['add-form'])) {
                $add_form = str_replace('{'.$entity_type->id().'}', $event_bundle_type, $entity_links['add-form']);
            } elseif (isset($entity_links['add-page'])) {
                $add_form = str_replace('{'.$entity_type->id().'}', $event_bundle_type, $entity_links['add-page']);
            }
        }

        // Can the user add a new event?
        $entity_manager = \Drupal::entityTypeManager();
        $access_handler = $entity_manager->getAccessControlHandler($entity_type->id());
        $dbl_click_to_create = false;
        if ($access_handler->createAccess($event_bundle_type)) {
            $dbl_click_to_create = true;
        }
        // Pass entity type to twig template.
        $variables['entity_id'] = $entity_type->id();
        // Update options for twig.
        $variables['options'] = $options;
        // Hide the create event link from user who doesn't have the permission
        // or if this feature is turn off.
        $variables['showAddEvent'] = $dbl_click_to_create && $options['createEventLink'];
        // Time format
        $timeFormat = $options['timeFormat'];
        // Field machine name of date field.
        $date_field = $options['datetime'];
        // Start date field is critical.
        if (empty($date_field)) {
            return;
        }
        // Field machine name of taxonomy field.
        $tax_field = isset($options['tax_field']) ? $options['tax_field'] : null;
        // Default date of the calendar.
        switch ($options['default_date_source']) {
            case 'now':
                $default_date = date('Y-m-d');
                break;
            case 'fixed':
                $default_date = $options['defaultDate'];
                break;
            default:
            // Don't do anything, we'll set it below.
        }
        // Default Language.
        $default_lang = $options['defaultLanguage'] === 'current_lang' ? $this->map_langcodes($language->getId()) : $options['defaultLanguage'];
        // Color for taxonomies.
        $color_tax = $options['color_taxonomies'];
        // Date fields.
        $date_field_option = $fields[$date_field]->options;
        // Custom timezone or user timezone.
        $timezone = !empty($date_field_option['settings']['timezone_override']) ? $date_field_option['settings']['timezone_override'] : date_default_timezone_get();
        // Set the first day setting.
        $first_day = isset($options['firstDay']) ? intval($options['firstDay']) : 0;
        // Left side buttons.
        $left_buttons = Xss::filter($options['left_buttons']);
        // Right side buttons.
        $right_buttons = Xss::filter($options['right_buttons']);
        $entries = [];

        // Allowed tags for title markup.
        $title_allowed_tags = Xss::getAdminTagList();
        // Remove the 'a' tag from allowed list.
        if (($tag_key = array_search('a', $title_allowed_tags)) !== false) {
            unset($title_allowed_tags[$tag_key]);
        }
        // Timezone conversion service.
        $timezone_service = \Drupal::service('gevent.timezone_conversion_service');
        // Save view results into entries array.
        foreach ($view->result as $row) {
            // Set the row_index property used by advancedRender function.
            $view->row_index = $row->index;
            // Result entity of current row.
            $current_entity = $row->_entity;
            // Start field is vital, if it doesn't exist then ignore this entity.
            if (!$current_entity->hasField($date_field)) {
                continue;
            }
            // Entity id.
            $entity_id = $current_entity->id();
            // Entity bundle type.
            $entity_bundle = $current_entity->bundle();
            // Background color based on taxonomy field.
            if (!empty($tax_field) && $current_entity->hasField($tax_field)) {
                // Event type.
                $event_type = $current_entity->get($tax_field)->target_id;
            }
            $view_builder = $entity_manager->getViewBuilder($entity_type->id());
            $des = \Drupal::service('renderer')->render($view_builder->view($current_entity));
            // Event title.
            if (!empty($fields[$options['department']])) {
                $unit = $fields[$options['department']]->advancedRender($row);
            }
            if (empty($options['title']) || $options['title'] == 'title') {
                $title = $fields['title']->advancedRender($row);
            } elseif (!empty($fields[$options['title']])) {
                $title = $fields[$options['title']]->advancedRender($row);
            } else {
                $title = '無標題事件';
            }
            $link_url = strstr($title, 'href="');
            if ($link_url) {
                $link_url = substr($link_url, 6);
                $link_url = strstr($link_url, '"', true);
            } else {
                $link_url = '';
            }
            // Calendar event date.
            if (strpos($date_field_option['type'], 'date_recur') === false) {
                continue;
            } else {
                $mydate = $current_entity->get($date_field)->getValue()[0];
                $start_date = new DrupalDateTime($mydate['value']);
                $end_date = new DrupalDateTime($mydate['end_value']);
                $rrule = $mydate['rrule'];
                $entry = [
                    'title' => Xss::filter('【<b>'.$unit.'</b>】'.$title, $title_allowed_tags),
                    'id' => $row->index,
                    'eid' => $entity_id,
                    'url' => $link_url,
                    'des' => isset($des) ? $des : '',
                ];
                // A user who doesn't have the permission can't edit an event.
                if (!$current_entity->access('update')) {
                    $entry['editable'] = false;
                }
                if (!isset($default_date)) {
                    $default_date = $start_date->format('Y-m-d');
                }
                $all_day = ($start_date->diff($end_date)->format('%a') == '1') ? true : false;
                if ($all_day) {
                    $entry['start'] = $start_date->format('Y-m-d');
                    $entry['end'] = $end_date->format('Y-m-d');
                    $entry['allDay'] = true;
                    $entry['eventDurationEditable'] = false;
                } else {
                    // Drupal store date time in UTC timezone.
                    // So we need to convert it into user timezone.
                    $entry['start'] = $timezone_service->utcToLocal($start_date->format('Y-m-d'), $timezone, DATE_ATOM);
                    $entry['end'] = $timezone_service->utcToLocal($end_date->format('Y-m-d'), $timezone, DATE_ATOM);
                }
                // Set the color for this event.
                if (isset($event_type) && isset($color_tax[$event_type])) {
                    $entry['backgroundColor'] = $color_tax[$event_type];
                }
                // Recurring event.
                if (!empty($rrule)) {
                    $entry['rrule'] = Xss::filter($rrule);
                    // Recurring events are read-only.
                    $entry['editable'] = false;
                }
                // Add this event into the array.
                $entries[] = $entry;
            }
        }

        // Remove the row_index property as we don't it anymore.
        unset($view->row_index);
        // Fullcalendar options.
        $calendar_options = [
            'plugins' => ['moment', 'interaction', 'dayGrid', 'timeGrid', 'list', 'rrule'],
            'defaultView' => isset($options['default_view']) ? $options['default_view'] : 'dayGridMonth',
            'defaultDate' => empty($default_date) ? date('Y-m-d') : $default_date,
            'header' => [
                'left' => $left_buttons,
                'center' => 'title',
                'right' => $right_buttons,
            ],
            'eventTimeFormat' => $timeFormat,
            'firstDay' => $first_day,
            'locale' => $default_lang,
            'events' => $entries,
            'navLinks' => $options['nav_links'] !== 0,
            'editable' => $options['updateAllowed'] !== 0,
            'eventLimit' => true, // Allow "more" link when too many events.
            'eventOverlap' => $options['allowEventOverlap'] !== 0,
        ];
        // Dialog options.
        // Other modules can override following options by custom plugin.
        // For reference of JSFrame options see:
        // https://github.com/riversun/JSFrame.js/
        $dialog_options = [
            'left' => 0,
            'top' => 0,
            'width' => 300,
            'height' => 200,
            'movable' => true, //Enable to be moved by mouse
            'resizable' => true, //Enable to be resized by mouse
            'style' => [
                'backgroundColor' => 'rgba(255,255,255,0.9)',
                'font-size' => '1rem',
            ],
        ];

        // Load the fullcalendar js library.
        $variables['#attached']['library'][] = 'gevent/fullcalendar';
        if ($options['dialogWindow']) {
            // Load the JS library for dialog.
            $variables['#attached']['library'][] = 'gevent/libraries.jsframe';
        }
        $variables['view_index'] = $view_index;
        // View name.
        $variables['view_id'] = $view->storage->id();
        // Display name.
        $variables['display_id'] = $view->current_display;
        // Pass data to js file.
        $variables['#attached']['drupalSettings']['geventView'][$view_index] = [
            // Allow client to select language, if it is 1.
            'languageSelector' => $options['languageSelector'],
            // Event update confirmation pop-up dialog.
            // If it is 1, a confirmation dialog will pop-up after dragging and dropping an event.
            'updateConfirm' => $options['updateConfirm'],
            // Open event links in dialog window.
            // If it is 1, event links in the calendar will open in a dialog window.
            'dialogWindow' => $options['dialogWindow'],
            // The bundle (content) type of a new event.
            'eventBundleType' => $event_bundle_type,
            // The machine name of date_recur field.
            'dateField' => $date_field,
            // Allow to create a new event by double clicking.
            'dblClickToCreate' => $dbl_click_to_create,
            // Entity type.
            'entityType' => $entity_type->id(),
            // URL of the new event form.
            'addForm' => isset($add_form) ? $add_form : '',
            // CSRF token.
            'token' => $token,
            // Show an event details in a new window (tab).
            'openEntityInNewTab' => $options['openEntityInNewTab'],
            // The options of the Fullcalendar object.
            'calendar_options' => json_encode($calendar_options),
            // The options of the pop-up dialog object.
            'dialog_options' => json_encode($dialog_options),
        ];
    }

    private function map_langcodes($langcode)
    {
        switch ($langcode) {
            case 'en-x-simple':
                return 'en';
            case 'pt-pt':
                return 'pt';
            case 'zh-hans':
                return 'zh-cn';
            case 'zh-hant':
                return 'zh-tw';
            default:
                return $langcode;
        }
    }
}
