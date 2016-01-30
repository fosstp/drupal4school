<?php
/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @param $form
 *   The form.
 * @param $form_state
 *   The form state.
 */
function Accessibility_form_system_theme_settings_alter(&$form, &$form_state) {
  $form['mtt_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Accessibility Theme Settings'),
    '#collapsible' => FALSE,
	'#collapsed' => FALSE,
  );

  $form['mtt_settings']['breadcrumb'] = array(
    '#type' => 'fieldset',
    '#title' => t('Breadcrumb'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['breadcrumb']['breadcrumb_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show breadcrumb'),
  	'#description'   => t('Use the checkbox to enable or disable breadcrumb.'),
	'#default_value' => theme_get_setting('breadcrumb_display','Accessibility'),
  );
  
  $form['mtt_settings']['breadcrumb']['breadcrumb_separator'] = array(
    '#type' => 'textfield',
    '#title' => t('Breadcrumb separator'),
	'#default_value' => theme_get_setting('breadcrumb_separator','Accessibility'),
    '#size'          => 5,
    '#maxlength'     => 10,
  );
  
  $form['mtt_settings']['breadcrumb']['breadcrumb_home'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Show home page link in breadcrumb'),
  	'#description'   => t('Use the checkbox to enable or disable the home page link in breadcrumb.'),
    '#default_value' => theme_get_setting('breadcrumb_home'),
  );
  
  $form['mtt_settings']['slideshow'] = array(
    '#type' => 'fieldset',
    '#title' => t('Front Page Slideshow'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['slideshow']['slideshow_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show slideshow'),
  	'#description'   => t('Slide for major events prompted the message of great help. Using this feature, you must first make a basic page message, layout height allowing only <b> 400 px </b>, as accompanied by beautiful pictures. Then they want carousel page number, enter the <em> slide source page </em> field.'),
	'#default_value' => theme_get_setting('slideshow_display','Accessibility'),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_pages'] = array(
    '#type' => 'textfield',
    '#title' => t('slide source page'),
  	'#description'   => t('Set the pages(node type) to be used as slides, between the page and the page with a comma (,) separated, go to <em> Administer -> Content </em> to see node number, example: if the page link to node/38, the page number is No. 38. Suppose there are three pages to be displayed in the slide, which are node/38, node/40, node/41, please enter the above text box: 38,40,41'),
	'#default_value' => theme_get_setting('slideshow_pages','Accessibility'),
  );

  $form['mtt_settings']['slideshow']['slideshow_js'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include slideshow javascript code'),
	'#default_value' => theme_get_setting('slideshow_js','Accessibility'),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_effect'] = array(
    '#type' => 'select',
    '#title' => t('Effects'),
  	'#description'   => t('From the drop-down menu, select the slideshow effect you prefer.'),
	'#default_value' => theme_get_setting('slideshow_effect','Accessibility'),
    '#options' => array(
		'blindX' => t('blindX'),
		'blindY' => t('blindY'),
		'blindZ' => t('blindZ'),
		'cover' => t('cover'),
		'curtainX' => t('curtainX'),
		'curtainY' => t('curtainY'),
		'fade' => t('fade'),
		'fadeZoom' => t('fadeZoom'),
		'growX' => t('growX'),
		'growY' => t('growY'),
		'scrollUp' => t('scrollUp'),
		'scrollDown' => t('scrollDown'),
		'scrollLeft' => t('scrollLeft'),
		'scrollRight' => t('scrollRight'),
		'scrollHorz' => t('scrollHorz'),
		'scrollVert' => t('scrollVert'),
		'shuffle' => t('shuffle'),
		'slideX' => t('slideX'),
		'slideY' => t('slideY'),
		'toss' => t('toss'),
		'turnUp' => t('turnUp'),
		'turnDown' => t('turnDown'),
		'turnLeft' => t('turnLeft'),
		'turnRight' => t('turnRight'),
		'uncover' => t('uncover'),
		'wipe' => t('wipe'),
		'zoom' => t('zoom'),
    ),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_effect_time'] = array(
    '#type' => 'textfield',
    '#title' => t('Effect duration (sec)'),
	'#default_value' => theme_get_setting('slideshow_effect_time','Accessibility'),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_randomize'] = array(
    '#type' => 'checkbox',
    '#title' => t('Randomize slideshow order'),
	'#default_value' => theme_get_setting('slideshow_randomize','Accessibility'),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_wrap'] = array(
    '#type' => 'checkbox',
    '#title' => t('Prevent slideshow from wrapping'),
	'#default_value' => theme_get_setting('slideshow_wrap','Accessibility'),
  );
  
  $form['mtt_settings']['slideshow']['slideshow_pause'] = array(
    '#type' => 'checkbox',
    '#title' => t('Pause slideshow on hover'),
	'#default_value' => theme_get_setting('slideshow_pause','Accessibility'),
  );
  
  $form['mtt_settings']['adaptive_menu'] = array(
    '#type' => 'fieldset',
    '#title' => t('Adaptive Menu'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
    '#description'   => t('Please select the menu to be displayed in the navigation region, this function will automatic switching to the specified menu based on user identity.'),
  );
  
  $all_menus = menu_load_all();
  foreach($all_menus as $menu_id => $menu) {
    $menu_title = $menu['title'];
    $all_menus[$menu_id] = $menu_title;
  }
  $form['mtt_settings']['adaptive_menu']['anonymous_menu'] = array(
    '#type' => 'select',
    '#title' => t('Menu for Anonymous'),
    '#multiple' => FALSE,
    '#options' => $all_menus,
    '#size' => 1,
	'#default_value' => theme_get_setting('anonymous_menu','Accessibility'),
  );
  
  $form['mtt_settings']['adaptive_menu']['teacher_menu'] = array(
    '#type' => 'select',
    '#title' => t('Menu for Teachers'),
    '#multiple' => FALSE,
    '#options' => $all_menus,
    '#size' => 1,
	'#default_value' => theme_get_setting('teacher_menu','Accessibility'),
  );
  
  $form['mtt_settings']['adaptive_menu']['student_menu'] = array(
    '#type' => 'select',
    '#title' => t('Menu for Students'),
    '#multiple' => FALSE,
    '#options' => $all_menus,
    '#size' => 1,
	'#default_value' => theme_get_setting('student_menu','Accessibility'),
  );
  
  $form['mtt_settings']['support'] = array(
    '#type' => 'fieldset',
    '#title' => t('Accessibility and support settings'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['support']['responsive'] = array(
    '#type' => 'fieldset',
    '#title' => t('Responsive'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['support']['responsive']['responsive_meta'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add meta tags to support responsive design on mobile devices.'),
	'#default_value' => theme_get_setting('responsive_meta','Accessibility'),
  );
  
  $form['mtt_settings']['support']['responsive']['responsive_respond'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Respond.js JavaScript to add basic CSS3 media query support to IE 6-8.'),
	'#default_value' => theme_get_setting('responsive_respond','Accessibility'),
    '#description'   => t('IE 6-8 require a JavaScript polyfill solution to add basic support of CSS3 media queries. Note that you should enable <strong>Aggregate and compress CSS files</strong> through <em>/admin/config/development/performance</em>.'),
  );
    
  $form['mtt_settings']['support']['brick'] = array(
    '#type' => 'fieldset',
    '#title' => t('Guide brick'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['support']['brick']['access_brick'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show guide brick'),
	'#default_value' => theme_get_setting('access_brick','Accessibility'),
    '#description' => t('After enabled guide brick function, you still have to build their own instructions page (<em> Administer -> Content -> Add new content </em>), and then create a link for the page, placed prominently on the home page position (<em> Administer -> Structure -> Menu </em>)!'),
  );

  $form['mtt_settings']['support']['brick']['access_navigation'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Secondary Menu'),
	'#default_value' => theme_get_setting('access_navigation','Accessibility'),
    '#description' => t('Now that we know has been used by browser as keyboard shortcuts, include: d, e, f. This block using access key Alt+n by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_second_menu'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Secondary Menu'),
	'#default_value' => theme_get_setting('access_second_menu','Accessibility'),
    '#description' => t('This block using access key Alt+m by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_search_area'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Search area'),
	'#default_value' => theme_get_setting('access_search_area','Accessibility'),
    '#description' => t('This block using access key Alt+s by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_breadcrumb'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Breadcrumb'),
	'#default_value' => theme_get_setting('access_breadcrumb','Accessibility'),
    '#description' => t('This block using access key Alt+b by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_content'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Content Block'),
	'#default_value' => theme_get_setting('access_content','Accessibility'),
    '#description' => t('This block using access key Alt+c by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_sidebar_left'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Left Sidebar'),
	'#default_value' => theme_get_setting('access_sidebar_left','Accessibility'),
    '#description' => t('This block using access key Alt+l by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_sidebar_right'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Right Sidebar'),
	'#default_value' => theme_get_setting('access_sidebar_right','Accessibility'),
    '#description' => t('This block using access key Alt+r by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_footer_first'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Footer first block'),
	'#default_value' => theme_get_setting('access_footer_first','Accessibility'),
    '#description' => t('This block using access key Alt+i by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_footer_second'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Footer second block'),
	'#default_value' => theme_get_setting('access_footer_second','Accessibility'),
    '#description' => t('This block using access key Alt+j by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_footer_third'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Footer third block'),
	'#default_value' => theme_get_setting('access_footer_third','Accessibility'),
    '#description' => t('This block using access key Alt+k by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_footer'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Footer block'),
	'#default_value' => theme_get_setting('access_footer','Accessibility'),
    '#description' => t('This block using access key Alt+t by default!'),
  );

  $form['mtt_settings']['support']['brick']['access_footer_bottom'] = array(
    '#type' => 'textfield',
    '#title' => t('Accesskey for Footer bottom right block'),
	'#default_value' => theme_get_setting('access_footer_bottom','Accessibility'),
    '#description' => t('This block using access key Alt+o by default!'),
  );

  $form['mtt_settings']['credits'] = array(
    '#type' => 'fieldset',
    '#title' => t('Credits'),
    '#collapsible' => TRUE,
	'#collapsed' => FALSE,
  );
  
  $form['mtt_settings']['credits']['credits_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show credits'),
  	'#description'   => t('Use the checkbox to enable or disable credits.'),
	'#default_value' => theme_get_setting('credits_display','Accessibility'),
  );
  
}