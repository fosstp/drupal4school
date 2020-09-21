<?php

namespace Drupal\gevent\Plugin\views\style;

use Drupal\Component\Utility\Xss;
use Drupal\core\form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\gevent\TaxonomyColor;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render content for FullCalendar.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "fullcalendar_view_display",
 *   title = @Translation("Full Calendar Display"),
 *   help = @Translation("Render contents in Full Calendar view."),
 *   theme = "views_view_fullcalendar",
 *   display_types = { "normal" }
 * )
 */
class FullCalendarDisplay extends StylePluginBase
{
    /**
     * Does the style plugin for itself support to add fields to it's output.
     *
     * @var bool
     */
    protected $usesFields = true;

    protected $taxonomyColorService;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, TaxonomyColor $taxonomyColorService)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->taxonomyColorService = $taxonomyColorService;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static($configuration, $plugin_id, $plugin_definition, $container->get('gevent.taxonomy_color'));
    }

    protected function defineOptions()
    {
        $options = parent::defineOptions();
        $options['default_date_source'] = ['default' => 'now'];
        $options['defaultDate'] = ['default' => ''];
        $options['start'] = ['default' => ''];
        $options['end'] = ['default' => ''];
        $options['title'] = ['default' => ''];
        $options['duration'] = ['default' => ''];
        $options['rrule'] = ['default' => ''];
        $options['bundle_type'] = ['default' => ''];
        $options['tax_field'] = ['default' => ''];
        $options['color_bundle'] = ['default' => []];
        $options['color_taxonomies'] = ['default' => []];
        $options['vocabularies'] = ['default' => ''];
        $options['right_buttons'] = [
            'default' => [
                'dayGridMonth',
                'timeGridWeek',
                'timeGridDay',
                'listYear',
            ],
        ];
        $options['left_buttons'] = [
            'default' => 'prev,next today',
        ];
        $options['default_view'] = ['default' => 'dayGridMonth'];
        $options['nav_links'] = ['default' => 1];
        $options['timeFormat'] = ['default' => 'hh:mm a'];
        $options['defaultLanguage'] = ['default' => 'en'];
        $options['languageSelector'] = ['default' => 0];
        $options['allowEventOverlap'] = ['default' => 1];
        $options['updateAllowed'] = ['default' => 1];
        $options['updateConfirm'] = ['default' => 1];
        $options['dialogWindow'] = ['default' => 0];
        $options['createEventLink'] = ['default' => 0];
        $options['openEntityInNewTab'] = ['default' => 1];

        return $options;
    }

    public function buildOptionsForm(&$form, FormStateInterface $form_state)
    {
        parent::buildOptionsForm($form, $form_state);

        // Remove the grouping setting.
        if (isset($form['grouping'])) {
            unset($form['grouping']);
        }
        $form['default_date_source'] = [
            '#type' => 'radios',
            '#options' => [
                'now' => $this->t('Current date'),
                'first' => $this->t('Date of first view result'),
                'fixed' => $this->t('Fixed value'),
            ],
            '#title' => $this->t('Default date source'),
            '#default_value' => (isset($this->options['default_date_source'])) ? $this->options['default_date_source'] : '',
            '#description' => $this->t('Source of the initial date displayed when the calendar first loads.'),
        ];
        // Default date of the calendar.
        $form['defaultDate'] = [
            '#type' => 'date',
            '#title' => $this->t('Default date'),
            '#default_value' => (isset($this->options['defaultDate'])) ? $this->options['defaultDate'] : '',
            '#description' => $this->t('Fixed initial date displayed when the calendar first loads.'),
            '#states' => [
                'visible' => [
                    [':input[name="style_options[default_date_source]"]' => ['value' => 'fixed']],
                ],
            ],
        ];
        // All selected fields.
        $field_names = $this->displayHandler->getFieldLabels();
        $entity_type = $this->view->getBaseEntityType()->id();
        // Field name of start date.
        $form['start'] = [
            '#title' => $this->t('Start Date Field'),
            '#type' => 'select',
            '#options' => $field_names,
            '#default_value' => (!empty($this->options['start'])) ? $this->options['start'] : '',
        ];
        // Field name of end date.
        $form['end'] = [
            '#title' => $this->t('End Date Field'),
            '#type' => 'select',
            '#options' => $field_names,
            '#empty_value' => '',
            '#default_value' => (!empty($this->options['end'])) ? $this->options['end'] : '',
        ];
        // Field name of title.
        $form['title'] = [
            '#title' => $this->t('Title Field'),
            '#type' => 'select',
            '#options' => $field_names,
            '#default_value' => (!empty($this->options['title'])) ? $this->options['title'] : '',
        ];
        // Display settings.
        $form['display'] = [
            '#type' => 'details',
            '#title' => $this->t('Display'),
            '#description' => $this->t('Calendar display settings.'),
        ];
        $fullcalendar_displays = [
            'dayGridMonth' => $this->t('Month'),
            'timeGridWeek' => $this->t('Week'),
            'timeGridDay' => $this->t('Day'),
            'listYear' => $this->t('List (Year)'),
            'listMonth' => $this->t('List (Month)'),
            'listWeek' => $this->t('List (Week)'),
            'listDay' => $this->t('List (Day)'),
        ];
        // Right side buttons.
        $display_defaults = (empty($this->options['right_buttons'])) ? [] : $this->options['right_buttons'];
        if (is_string($display_defaults)) {
            $display_defaults = explode(',', $display_defaults);
        }
        // Left side buttons.
        $form['left_buttons'] = [
            '#type' => 'textfield',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['left_buttons'])) ? [] : $this->options['left_buttons'],
            '#title' => $this->t('Left side buttons'),
            '#description' => $this->t(
                'Left side buttons. Buttons are separated by commas or space. See the %fullcalendar_doc for available buttons.',
                [
                    '%fullcalendar_doc' => Link::fromTextAndUrl($this->t('Fullcalendar documentation'), Url::fromUri('https://fullcalendar.io/docs/header', ['attributes' => ['target' => '_blank']]))->toString(),
                ]
            ),
        ];
        $form['right_buttons'] = [
            '#type' => 'checkboxes',
            '#fieldset' => 'display',
            '#options' => $fullcalendar_displays,
            '#default_value' => $display_defaults,
            '#title' => $this->t('Display toggles'),
            '#description' => $this->t('Shown as buttons on the right side of the calendar view. See the %fullcalendar_doc.',
            [
                '%fullcalendar_doc' => Link::fromTextAndUrl($this->t('Fullcalendar "Views" documentation'), Url::fromUri('https://fullcalendar.io/docs', ['attributes' => ['target' => '_blank']]))->toString(),
            ]),
        ];
        // Default view.
        $form['default_view'] = [
            '#type' => 'radios',
            '#fieldset' => 'display',
            '#options' => $fullcalendar_displays,
            '#default_value' => (empty($this->options['default_view'])) ? 'month' : $this->options['default_view'],
            '#title' => $this->t('Default view'),
        ];
        // First day.
        $form['firstDay'] = [
            '#type' => 'radios',
            '#fieldset' => 'display',
            '#options' => [
                '0' => $this->t('Sunday'),
                '1' => $this->t('Monday'),
                '2' => $this->t('Tuesday'),
                '3' => $this->t('Wednesday'),
                '4' => $this->t('Thursday'),
                '5' => $this->t('Friday'),
                '6' => $this->t('Saturday'),
            ],
            '#default_value' => (empty($this->options['firstDay'])) ? '0' : $this->options['firstDay'],
            '#title' => $this->t('First Day'),
        ];
        // Nav Links.
        $form['nav_links'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['nav_links'])) ? 1 : $this->options['nav_links'],
            '#title' => $this->t('Day/Week are links'),
            '#description' => $this->t('If this option is selected, day/week names will be linked to navigation views.'),
        ];
        // Time format
        $form['timeFormat'] = [
            '#fieldset' => 'display',
            '#type' => 'textfield',
            '#title' => $this->t('Time Format settings for month view'),
            '#default_value' => (isset($this->options['timeFormat'])) ? $this->options['timeFormat'] : 'hh:mm a',
            '#description' => $this->t('See %momentjs_doc for available formatting options. <br />Leave it blank to use the default format "hh:mm a".', [
                '%momentjs_doc' => Link::fromTextAndUrl($this->t('MomentJS’s formatting characters'), Url::fromUri('http://momentjs.com/docs/#/displaying/format/', ['attributes' => ['target' => '_blank']]))->toString(),
            ]),
            '#size' => 20,
        ];
        // Allow/disallow event overlap.
        $form['allowEventOverlap'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['allowEventOverlap'])) ? 1 : $this->options['allowEventOverlap'],
            '#title' => $this->t('Allow calendar events to overlap'),
            '#description' => $this->t('If this option is selected, calendar events are allowed to overlap (default).'),
        ];
        // Allow/disallow event editing.
        $form['updateAllowed'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['updateAllowed'])) ? 1 : $this->options['updateAllowed'],
            '#title' => $this->t('Allow event editing.'),
            '#description' => $this->t('If this option is selected, editing by dragging and dropping an event will be enabled.'),
        ];
        // Event update JS confirmation dialog.
        $form['updateConfirm'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['updateConfirm'])) ? 1 : $this->options['updateConfirm'],
            '#title' => $this->t('Event update confirmation pop-up dialog.'),
            '#description' => $this->t('If this option is selected, a confirmation dialog will pop-up after dragging and dropping an event.'),
        ];
        // Language and Localization.
        $locale = [
            'current_lang' => $this->t('Current active language on the page'),
            'en' => 'English',
            'af' => 'Afrikaans',
            'ar-dz' => 'Arabic - Algeria',
            'ar-kw' => 'Arabic - Kuwait',
            'ar-ly' => 'Arabic - Libya',
            'ar-ma' => 'Arabic - Morocco',
            'ar-sa' => 'Arabic - Saudi Arabia',
            'ar-tn' => 'Arabic - Tunisia',
            'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'ca' => 'Catalan',
            'cs' => 'Czech',
            'da' => 'Danish',
            'de-at' => 'German - Austria',
            'de-ch' => 'German - Switzerland',
            'de' => 'German',
            'el' => 'Greek',
            'en-au' => 'English - Australia',
            'en-ca' => 'English - Canada',
            'en-gb' => 'English - United Kingdom',
            'en-ie' => 'English - Ireland',
            'en-nz' => 'English - New Zealand',
            'es-do' => 'Spanish - Dominican Republic',
            'es-us' => 'Spanish - United States',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Farsi',
            'fi' => 'Finnish',
            'fr-ca' => 'French - Canada',
            'fr-ch' => 'French - Switzerland',
            'fr' => 'French',
            'gl' => 'Galician',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'hr' => 'Croatian',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'kk' => 'Kannada',
            'ko' => 'Korean',
            'lb' => 'Lebanon',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian',
            'mk' => 'FYRO Macedonian',
            'ms-my' => 'Malay - Malaysia',
            'ms' => 'Malay',
            'nb' => 'Norwegian (Bokmål) - Norway',
            'nl-be' => 'Dutch - Belgium',
            'nl' => 'Dutch',
            'nn' => 'Norwegian',
            'pl' => 'Polish',
            'pt-br' => 'Portuguese - Brazil',
            'pt' => 'Portuguese',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sq' => 'Albanian',
            'sr-cyrl' => 'Serbian - Cyrillic',
            'sr' => 'Serbian',
            'sv' => 'Swedish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
            'zh-cn' => 'Chinese - China',
            'zh-tw' => 'Chinese - Taiwan',
        ];
        // Default Language.
        $form['defaultLanguage'] = [
            '#title' => $this->t('Default Language'),
            '#fieldset' => 'display',
            '#type' => 'select',
            '#options' => $locale,
            '#default_value' => (!empty($this->options['defaultLanguage'])) ? $this->options['defaultLanguage'] : 'en',
        ];
        // Language Selector Switch.
        $form['languageSelector'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['languageSelector'])) ? 0 : $this->options['languageSelector'],
            '#title' => $this->t('Allow client to select language.'),
        ];
        $form['dialogWindow'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['dialogWindow'])) ? 0 : $this->options['dialogWindow'],
            '#title' => $this->t('Show event description in dialog window.'),
            '#description' => $this->t('If this option is selected, the description (the last field in the fields list) will show in a dialog window once clicking on the event.'),
        ];
        // Open details in new window.
        $form['openEntityInNewTab'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => !isset($this->options['openEntityInNewTab']) ? 1 : $this->options['openEntityInNewTab'],
            '#title' => $this->t('Open entities (calendar items) into new tabs'),
        ];
        // Create new event link.
        $form['createEventLink'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['createEventLink'])) ? 0 : $this->options['createEventLink'],
            '#title' => $this->t('Create a new event via the Off-Canvas dialog.'),
            '#description' => $this->t('If this option is selected, there will be an Add Event link below the calendar that provides the ability to create an event In-Place.'),
        ];
        // Legend colors.
        $form['colors'] = [
            '#type' => 'details',
            '#title' => $this->t('Legend Colors'),
            '#description' => $this->t('Set color value of legends for each content type or each taxonomy.'),
        ];

        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('taxonomy')) {
            // All vocabularies.
            $cabNames = taxonomy_vocabulary_get_names();
            // Taxonomy reference field.
            $tax_fields = [];
            // Find out all taxonomy reference fields of this View.
            foreach ($field_names as $field_name => $lable) {
                $field_conf = FieldStorageConfig::loadByName($entity_type, $field_name);
                if (empty($field_conf)) {
                    continue;
                }
                if ($field_conf->getType() == 'entity_reference') {
                    $tax_fields[$field_name] = $lable;
                }
            }
            // Field name of event taxonomy.
            $form['tax_field'] = [
                '#title' => $this->t('Event Taxonomy Field'),
                '#description' => $this->t('In order to specify colors for event taxonomies, you must select a taxonomy reference field for the View.'),
                '#type' => 'select',
                '#options' => $tax_fields,
                '#empty_value' => '',
                '#disabled' => empty($tax_fields),
                '#fieldset' => 'colors',
                '#default_value' => (!empty($this->options['tax_field'])) ? $this->options['tax_field'] : '',
            ];
            // Color for vocabularies.
            $form['vocabularies'] = [
                '#title' => $this->t('Vocabularies'),
                '#type' => 'select',
                '#options' => $cabNames,
                '#empty_value' => '',
                '#fieldset' => 'colors',
                '#description' => $this->t('Specify which vocabulary is using for calendar event color. If the vocabulary selected is not the one that the taxonomy field belonging to, the color setting would be ignored.'),
                '#default_value' => (!empty($this->options['vocabularies'])) ? $this->options['vocabularies'] : '',
                '#states' => [
                    // Only show this field when the 'tax_field' is selected.
                    'invisible' => [
                        [':input[name="style_options[tax_field]"]' => ['value' => '']],
                    ],
                ],
                '#ajax' => [
                    'callback' => 'Drupal\gevent\Plugin\views\style\FullCalendarDisplay::taxonomyColorCallback',
                    'event' => 'change',
                    'wrapper' => 'color-taxonomies-div',
                    'progress' => [
                        'type' => 'throbber',
                        'message' => $this->t('Verifying entry...'),
                    ],
                ],
            ];
        }

        if (!isset($form_state->getUserInput()['style_options'])) {
            // Taxonomy color input boxes.
            $form['color_taxonomies'] = $this->taxonomyColorService->colorInputBoxs($this->options['vocabularies'], $this->options['color_taxonomies']);
        }
        // Content type colors.
        $form['color_bundle'] = [
            '#type' => 'details',
            '#title' => $this->t('Colors for Bundle Types'),
            '#description' => $this->t('Specify colors for each bundle type. If taxonomy color is specified, this settings would be ignored.'),
            '#fieldset' => 'colors',
        ];
        // All bundle types.
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        // Options list.
        $bundlesList = [];
        foreach ($bundles as $id => $bundle) {
            $label = $bundle['label'];
            $bundlesList[$id] = $label;
            // Content type colors.
            $form['color_bundle'][$id] = [
                '#title' => $label,
                '#default_value' => isset($this->options['color_bundle'][$id]) ? $this->options['color_bundle'][$id] : '#3a87ad',
                '#type' => 'color',
            ];
        }

        // Recurring event.
        $form['recurring'] = [
            '#type' => 'details',
            '#title' => $this->t('Recurring event settings'),
              // '#description' =>  $this->t('Settings for recurring event.'),.
        ];
        // Field name of rrules.
        $form['rrule'] = [
            '#title' => $this->t('RRule Field for recurring events.'),
            '#description' => $this->t('You can generate an valid rrule string via <a href=":tool-url" target="_blank">the online toole</a><br><a href=":doc-url" target="_blank">See the documentation</a> for more about RRule.',
                [
                    ':tool-url' => 'https://jakubroztocil.github.io/rrule/',
                    ':doc-url' => 'https://github.com/jakubroztocil/rrule',
                ]),
            '#type' => 'select',
            '#empty_value' => '',
            '#fieldset' => 'recurring',
            '#options' => $field_names,
            '#default_value' => (!empty($this->options['rrule'])) ? $this->options['rrule'] : '',
        ];
        // Field name of rrules.
        $form['duration'] = [
            '#fieldset' => 'recurring',
            '#title' => $this->t('Event duration field.'),
            '#description' => $this->t('For specifying the end time of each recurring event instance. The field value should be a string in the format hh:mm:ss.sss, hh:mm:sss or hh:mm. For example, "05:00" signifies 5 hours.'),
            '#type' => 'select',
            '#empty_value' => '',
            '#options' => $field_names,
            '#empty_value' => '',
            '#default_value' => (!empty($this->options['duration'])) ? $this->options['duration'] : '',
            '#states' => [
                // Only show this field when the 'rrule' is specified.
                'invisible' => [
                    [':input[name="style_options[rrule]"]' => ['value' => '']],
                ],
            ],
        ];
        // New event bundle type.
        $form['bundle_type'] = [
            '#title' => $this->t('Event bundle (Content) type'),
            '#description' => $this->t('The bundle (content) type of a new event. Once this is set, you can create a new event by double clicking a calendar entry.'),
            '#type' => 'select',
            '#options' => $bundlesList,
            '#default_value' => (!empty($this->options['bundle_type'])) ? $this->options['bundle_type'] : '',
        ];
        // Extra CSS classes.
        $form['classes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CSS classes'),
            '#default_value' => (isset($this->options['classes'])) ? $this->options['classes'] : '',
            '#description' => $this->t('CSS classes for further customization of this view.'),
        ];
    }

    /**
     * Options form validation handle function.
     *
     * @see \Drupal\views\Plugin\views\PluginBase::validateOptionsForm()
     */
    public function validateOptionsForm(&$form, FormStateInterface $form_state)
    {
        $style_options = &$form_state->getValue('style_options');
        $selected_displays = $style_options['right_buttons'];
        $default_display = $style_options['default_view'];

        if (!in_array($default_display, array_filter(array_values($selected_displays)))) {
            $form_state->setErrorByName('style_options][default_view', $this->t('The default view must be one of the selected display toggles.'));
        }
    }

    /**
     * Options form submit handle function.
     *
     * @see \Drupal\views\Plugin\views\PluginBase::submitOptionsForm()
     */
    public function submitOptionsForm(&$form, FormStateInterface $form_state)
    {
        $options = &$form_state->getValue('style_options');
        $input_value = $form_state->getUserInput();
        $input_colors = isset($input_value['style_options']['color_taxonomies']) ? $input_value['style_options']['color_taxonomies'] : [];
        // Save the input of colors.
        foreach ($input_colors as $id => $color) {
            if (!empty($color)) {
                $options['color_taxonomies'][$id] = $color;
            }
        }
        $options['right_buttons'] = isset($input_value['style_options']['right_buttons']) ? implode(',', array_filter(array_values($input_value['style_options']['right_buttons']))) : '';

        // Sanitize user input.
        $options['timeFormat'] = Xss::filter($options['timeFormat']);

        parent::submitOptionsForm($form, $form_state);
    }

    /**
     * Taxonomy colors Ajax callback function.
     */
    public static function taxonomyColorCallback(array &$form, FormStateInterface $form_state)
    {
        $options = $form_state->getValue('style_options');
        $vid = $options['vocabularies'];
        $taxonomy_color_service = \Drupal::service('gevent.taxonomy_color');

        if (isset($options['color_taxonomies'])) {
            $defaultValues = $options['color_taxonomies'];
        } else {
            $defaultValues = [];
        }
        // Taxonomy color boxes.
        $form['color_taxonomies'] = $taxonomy_color_service->colorInputBoxs($vid, $defaultValues, true);

        return $form['color_taxonomies'];
    }
}
