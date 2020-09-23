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
 *   id = "gevent_view_display",
 *   title = "校園行事曆",
 *   help = "將內容以行事曆格式呈現",
 *   theme = "views_view_gevent",
 *   display_types = { "normal" }
 * )
 */
class GeventViewDisplay extends StylePluginBase
{
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
        $options['datetime'] = ['default' => 'field_daterange'];
        $options['title'] = ['default' => 'title'];
        $options['bundle_type'] = ['default' => 'calendar_event'];
        $options['tax_field'] = ['default' => 'field_catalog'];
        $options['color_taxonomies'] = ['default' => []];
        $options['vocabularies'] = ['default' => 'calendar'];
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
        $options['defaultLanguage'] = ['default' => 'zh-tw'];
        $options['languageSelector'] = ['default' => 0];
        $options['allowEventOverlap'] = ['default' => 1];
        $options['updateAllowed'] = ['default' => 1];
        $options['updateConfirm'] = ['default' => 1];
        $options['dialogWindow'] = ['default' => 1];
        $options['createEventLink'] = ['default' => 1];
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
                'now' => '今天',
                'first' => '最早的事件',
                'fixed' => '指定日期',
            ],
            '#title' => '顯示哪一天行事曆？',
            '#default_value' => (isset($this->options['default_date_source'])) ? $this->options['default_date_source'] : '',
            '#description' => '行事曆載入時將顯示的預設日期，使用者可以透過介面操作改變成自己想要觀看的日期',
        ];
        // Default date of the calendar.
        $form['defaultDate'] = [
            '#type' => 'date',
            '#title' => '指定日期',
            '#default_value' => (isset($this->options['defaultDate'])) ? $this->options['defaultDate'] : '',
            '#description' => '請指定行事曆載入時要顯示的預設日期',
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
        $form['datetime'] = [
            '#title' => '事件時間欄位',
            '#type' => 'select',
            '#options' => $field_names,
            '#default_value' => (!empty($this->options['datetime'])) ? $this->options['datetime'] : '',
            '#description' => '請指定內容類型裡儲存事件起迄時間的欄位',
        ];
        // Field name of title.
        $form['title'] = [
            '#title' => '事件標題欄位',
            '#type' => 'select',
            '#options' => $field_names,
            '#default_value' => (!empty($this->options['title'])) ? $this->options['title'] : '',
            '#description' => '請指定內容類型裡儲存事件標題的欄位',
        ];
        // Display settings.
        $form['display'] = [
            '#type' => 'details',
            '#title' => '行事曆顯示設定',
        ];
        $fullcalendar_displays = [
            'dayGridMonth' => '當月',
            'timeGridWeek' => '當週',
            'timeGridDay' => '當天',
            'listYear' => '年度表列',
            'listMonth' => '月份表列',
            'listWeek' => '一週表列',
            'listDay' => '一日表列',
        ];
        // Right side buttons.
        $display_defaults = (empty($this->options['right_buttons'])) ? [] : $this->options['right_buttons'];
        if (is_string($display_defaults)) {
            $display_defaults = explode(',', $display_defaults);
        }
        // Left side buttons.
        $doc = Link::fromTextAndUrl('Fullcalendar 說明文件', Url::fromUri('https://fullcalendar.io/docs/header', ['attributes' => ['target' => '_blank']]))->toString();
        $form['left_buttons'] = [
            '#type' => 'textfield',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['left_buttons'])) ? [] : $this->options['left_buttons'],
            '#title' => '左側按鈕',
            '#description' => '顯示哪些左側按鈕？按鈕之間請以逗號或空格分隔，請看'.$doc.'了解有哪些按鈕可以使用。',
        ];
        $doc = Link::fromTextAndUrl('Fullcalendar 顯示模式說明文件', Url::fromUri('https://fullcalendar.io/docs', ['attributes' => ['target' => '_blank']]))->toString();
        $form['right_buttons'] = [
            '#type' => 'checkboxes',
            '#fieldset' => 'display',
            '#options' => $fullcalendar_displays,
            '#default_value' => $display_defaults,
            '#title' => '右側按鈕',
            '#description' => "顯示右側按鈕（切換顯示模式）於行事曆頁面，請看 $doc！",
        ];
        // Default view.
        $form['default_view'] = [
            '#type' => 'radios',
            '#fieldset' => 'display',
            '#options' => $fullcalendar_displays,
            '#default_value' => (empty($this->options['default_view'])) ? 'month' : $this->options['default_view'],
            '#title' => '預設顯示模式',
        ];
        // First day.
        $form['firstDay'] = [
            '#type' => 'radios',
            '#fieldset' => 'display',
            '#options' => [
                '0' => '週日',
                '1' => '週一',
                '2' => '週二',
                '3' => '週三',
                '4' => '週四',
                '5' => '週五',
                '6' => '週六',
            ],
            '#default_value' => (empty($this->options['firstDay'])) ? '0' : $this->options['firstDay'],
            '#title' => '每週從哪一天開始？',
        ];
        // Nav Links.
        $form['nav_links'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['nav_links'])) ? 1 : $this->options['nav_links'],
            '#title' => '天和週顯示為導覽連結',
            '#description' => '此功能開啟時，天和週的名稱將連結到導覽頁面。',
        ];
        // Time format
        $doc = Link::fromTextAndUrl('MomentJS 函式庫格式字元', Url::fromUri('http://momentjs.com/docs/#/displaying/format/', ['attributes' => ['target' => '_blank']]))->toString();
        $form['timeFormat'] = [
            '#fieldset' => 'display',
            '#type' => 'textfield',
            '#title' => '月份顯示模式的時間格式',
            '#default_value' => (isset($this->options['timeFormat'])) ? $this->options['timeFormat'] : 'hh:mm a',
            '#description' => '請閱讀'.$doc.'，了解有哪些格式選項。<br />留白的話將會使用預設格式 "hh:mm a"（時：分 上下午）。',
            '#size' => 20,
        ];
        // Allow/disallow event overlap.
        $form['allowEventOverlap'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['allowEventOverlap'])) ? 1 : $this->options['allowEventOverlap'],
            '#title' => '允許行事曆事件時間重疊',
            '#description' => '預設為允許',
        ];
        // Allow/disallow event editing.
        $form['updateAllowed'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['updateAllowed'])) ? 1 : $this->options['updateAllowed'],
            '#title' => '允許修改事件',
            '#description' => '此功能開啟時，可以用拖曳方式改變事件的時間點',
        ];
        // Event update JS confirmation dialog.
        $form['updateConfirm'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (!isset($this->options['updateConfirm'])) ? 1 : $this->options['updateConfirm'],
            '#title' => '事件更新確認對話框',
            '#description' => '此功能開啟時，在拖曳事件後將會出現確認對話框。',
        ];
        // Language and Localization.
        $locale = [
            'current_lang' => '站台預設語言',
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
            '#title' => '預設語言',
            '#fieldset' => 'display',
            '#type' => 'select',
            '#options' => $locale,
            '#default_value' => (!empty($this->options['defaultLanguage'])) ? $this->options['defaultLanguage'] : 'zh-tw',
        ];
        // Language Selector Switch.
        $form['languageSelector'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['languageSelector'])) ? 0 : $this->options['languageSelector'],
            '#title' => '允許使用者選擇語言',
        ];
        $form['dialogWindow'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['dialogWindow'])) ? 0 : $this->options['dialogWindow'],
            '#title' => '將事件說明顯示於彈出視窗',
            '#description' => '此功能開啟時，事件說明欄位的內容將在點擊事件時自動顯示。',
        ];
        // Open details in new window.
        $form['openEntityInNewTab'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => !isset($this->options['openEntityInNewTab']) ? 1 : $this->options['openEntityInNewTab'],
            '#title' => '在新分頁開啟事件詳細內容',
        ];
        // Create new event link.
        $form['createEventLink'] = [
            '#type' => 'checkbox',
            '#fieldset' => 'display',
            '#default_value' => (empty($this->options['createEventLink'])) ? 0 : $this->options['createEventLink'],
            '#title' => '允許新增事件',
            '#description' => '此功能開啟時，顯示新增事件連結方便管理員從行事曆頁面直接新增事件。',
        ];
        // Legend colors.
        $form['colors'] = [
            '#type' => 'details',
            '#title' => '配色',
            '#description' => '為不同的內容類型或分類顯示不同的顏色',
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
                '#title' => '事件分類欄位',
                '#description' => '內容類型裡必須包含分類（taxonomy）欄位，才能根據分類給予不同配色，請指定事件分類欄位。',
                '#type' => 'select',
                '#options' => $tax_fields,
                '#empty_value' => '',
                '#disabled' => empty($tax_fields),
                '#fieldset' => 'colors',
                '#default_value' => (!empty($this->options['tax_field'])) ? $this->options['tax_field'] : '',
            ];
            // Color for vocabularies.
            $form['vocabularies'] = [
                '#title' => '主分類',
                '#type' => 'select',
                '#options' => $cabNames,
                '#empty_value' => '',
                '#fieldset' => 'colors',
                '#description' => '請選擇要用於行事曆配色的主分類。此主分類必須設定於上述的分類欄位中，否則配色的設定將被忽略！',
                '#default_value' => (!empty($this->options['vocabularies'])) ? $this->options['vocabularies'] : '',
                '#states' => [
                    // Only show this field when the 'tax_field' is selected.
                    'invisible' => [
                        [':input[name="style_options[tax_field]"]' => ['value' => '']],
                    ],
                ],
                '#ajax' => [
                    'callback' => 'Drupal\gevent\Plugin\views\style\GeventDisplay::taxonomyColorCallback',
                    'event' => 'change',
                    'wrapper' => 'color-taxonomies-div',
                    'progress' => [
                        'type' => 'throbber',
                        'message' => '驗證事件中...',
                    ],
                ],
            ];
        }

        if (!isset($form_state->getUserInput()['style_options'])) {
            // Taxonomy color input boxes.
            $form['color_taxonomies'] = $this->taxonomyColorService->colorInputBoxs($this->options['vocabularies'], $this->options['color_taxonomies']);
        }

        // All bundle types.
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        // Options list.
        $bundlesList = [];
        foreach ($bundles as $id => $bundle) {
            $label = $bundle['label'];
            $bundlesList[$id] = $label;
        }
        // New event bundle type.
        $form['bundle_type'] = [
            '#title' => '內容類型',
            '#description' => '新增事件時所使用的內容類型。如果有設定的話，您就可以雙擊行事曆空格建立新事件。',
            '#type' => 'select',
            '#options' => $bundlesList,
            '#default_value' => (!empty($this->options['bundle_type'])) ? $this->options['bundle_type'] : '',
        ];
        // Extra CSS classes.
        $form['classes'] = [
            '#type' => 'textfield',
            '#title' => 'CSS 樣式',
            '#default_value' => (isset($this->options['classes'])) ? $this->options['classes'] : '',
            '#description' => '請輸入要套用到這個行事曆頁面的 CSS 樣式名稱。',
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
            $form_state->setErrorByName('style_options][default_view', '行事曆頁面需要至少一組切換顯示按鈕（左側或右側按鈕）。');
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
