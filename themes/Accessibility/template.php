<?php
/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return
 *   A string containing the breadcrumb output.
 */
function Accessibility_breadcrumb($variables){
  $breadcrumb = $variables['breadcrumb'];
  $breadcrumb_separator=theme_get_setting('breadcrumb_separator','Accessibility');
  $access_key=theme_get_setting('access_breadcrumb','Accessibility');
  $brick='';
  
  $show_breadcrumb_home = theme_get_setting('breadcrumb_home');
  if (!$show_breadcrumb_home) {
  array_shift($breadcrumb);
  }
  
  if (!empty($breadcrumb)) {
    $breadcrumb[] = drupal_get_title();
	if (theme_get_setting('access_brick','Accessibility')) $brick='<a accesskey="'.$access_key.'" href="'.'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'].'" class="brick" title="階層式快捷列">:::</a> ';
    return '<div class="breadcrumb">' . $brick . implode(' <span class="breadcrumb-separator">' . $breadcrumb_separator . '</span>', $breadcrumb) . '</div>';
  }
}

function Accessibility_page_alter($page) {

	if (theme_get_setting('responsive_meta','Accessibility')):
	$mobileoptimized = array(
		'#type' => 'html_tag',
		'#tag' => 'meta',
		'#attributes' => array(
		'name' =>  'MobileOptimized',
		'content' =>  'width'
		)
	);

	$handheldfriendly = array(
		'#type' => 'html_tag',
		'#tag' => 'meta',
		'#attributes' => array(
		'name' =>  'HandheldFriendly',
		'content' =>  'true'
		)
	);

	$viewport = array(
		'#type' => 'html_tag',
		'#tag' => 'meta',
		'#attributes' => array(
		'name' =>  'viewport',
		'content' =>  'width=device-width, initial-scale=1'
		)
	);
	
	drupal_add_html_head($mobileoptimized, 'MobileOptimized');
	drupal_add_html_head($handheldfriendly, 'HandheldFriendly');
	drupal_add_html_head($viewport, 'viewport');
	endif;
	
}

function Accessibility_preprocess_html(&$variables) {

	if (!theme_get_setting('responsive_respond','Accessibility')):
	drupal_add_css(path_to_theme() . '/css/basic-layout.css', array('group' => CSS_THEME, 'browsers' => array('IE' => '(lte IE 8)&(!IEMobile)', '!IE' => FALSE), 'preprocess' => FALSE));
	endif;
	
	drupal_add_css(path_to_theme() . '/css/ie.css', array('group' => CSS_THEME, 'browsers' => array('IE' => '(lte IE 8)&(!IEMobile)', '!IE' => FALSE), 'preprocess' => FALSE));
}

/**
 * Override or insert variables into the html template.
 */
function Accessibility_process_html(&$vars) {
	// Hook into color.module
	if (module_exists('color')) {
	_color_html_alter($vars);
	}
$vars['page_top'] = Accessibility_for_taiwan($vars['page_top']);
$vars['page_bottom'] = Accessibility_for_taiwan($vars['page_bottom']);
$vars['page'] = Accessibility_for_taiwan($vars['page']);
}

/**
 * Override or insert variables into the page template.
 */
function Accessibility_process_page(&$variables) {
  // Hook into color.module.
  if (module_exists('color')) {
    _color_page_alter($variables);
  }
 
}

function Accessibility_form_search_block_form_alter(&$form, &$form_state, $form_id) {
    unset($form['search_block_form']['#title']);
    $form['search_block_form']['#title_display'] = 'invisible';
	$form_default = t('Search');
    $form['search_block_form']['#default_value'] = $form_default;
    $form['actions']['submit'] = array('#type' => 'image_button', '#src' => base_path() . path_to_theme() . '/images/search-button.png', '#attributes' => array( 'alt' => '網站全文搜尋') );

 	$form['search_block_form']['#attributes'] = array('onblur' => "if (this.value == '') {this.value = '{$form_default}';}", 'onfocus' => "if (this.value == '{$form_default}') {this.value = '';}" );
}

$tab_index = 1;
function Accessibility_for_taiwan($content) {
  global $tab_index;
  $dom = new DOMDocument;
  libxml_use_internal_errors(true);
  $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.$content);
  $images = $dom->getElementsByTagName('img');
  $links = $dom->getElementsByTagName('a');
  $objs = $dom->getElementsByTagName('object');
  $applets =  $dom->getElementsByTagName('applet');
  $img_maps = $dom->getElementsByTagName('area');
  $ems = $dom->getElementsByTagName('i');
  $strongs = $dom->getElementsByTagName('b');
  $tables = $dom->getElementsByTagName('table');
  $table_headers = $dom->getElementsByTagName('th');
  foreach ($images as $img) {
    $myalt=$img->getAttribute('alt');
    if (!$myalt) $img->setAttribute('alt','排版用裝飾圖片');
  }
  foreach ($links as $lnk) {
    $mytitle=$lnk->getAttribute('title');
    if (!$mytitle) $lnk->setAttribute('title', $lnk->nodeValue);
    $lnk->setAttribute('tabindex', $tab_index);
    $tab_index++;
  }
  foreach ($objs as $obj) {
    $myalt=$obj->nodeValue;
    if (!$myalt) $obj->nodeValue=$obj->getAttribute('data');
  }
  foreach ($applets as $app) {
    $myalt=$app->nodeValue;
    if (!$myalt) {
      $app->setAttribute('alt', '多媒體互動物件');
      $app->nodeValue='多媒體互動物件';
    }
    else {
      $app->setAttribute('alt', $myalt);
    }
  }
  foreach ($img_maps as $area) {
    $myalt=$area->getAttribute('alt');
    if (!$myalt) $area->setAttribute('alt',$area->getAttribute('href'));
  }
  foreach ($ems as $em) {
    $newem=$dom->createElement('em',$em->nodeValue);
    $em->parentNode->replaceChild($newem, $em);
  }
  foreach ($strongs as $strong) {
    $newstrong=$dom->createElement('strong',$strong->nodeValue);
    $strong->parentNode->replaceChild($newstrong, $strong);
  }
  foreach ($tables as $table) {
    $mytitle=$table->getAttribute('title');
    $mysummary=$table->getAttribute('summary');
    $caption=FALSE;
    if ($table->hasChildNodes()) {
      if ($table->firstChild->nodeName=='caption') $caption=$table->firstChild->nodeValue;
    }
    if (!$mytitle && $caption) $table->setAttribute('title',$caption);
    if (!$mysummary && $caption) $table->setAttribute('summary',$caption);
    if (!$mytitle && !$mysummary && !$caption) $table->setAttribute('summary','排版用表格');
  }
  foreach ($table_headers as $myth) {
    $myscope=$myth->getAttribute('scope');
    $myheaders=$myth->getAttribute('headers');
    if (!$myscope && !$myheaders) $myth->setAttribute('scope','col');
  }
  $content = $dom->saveHTML();
  $content = preg_replace('/<!DOCTYPE .+>/', '', preg_replace('/<meta .+?>/', '', str_replace( array('<html>', '</html>', '<head>', '</head>', '<body>', '</body>'), array('', '', '', '', '', ''), $content)));
  return $content;
}

/**
 * Add javascript files for jquery slideshow.
 */
if (theme_get_setting('slideshow_js','Accessibility')):

	drupal_add_js(drupal_get_path('theme', 'Accessibility') . '/js/jquery.cycle.all.js');
	
	//Initialize slideshow using theme settings
	$effect=theme_get_setting('slideshow_effect','Accessibility');
	$effect_time= (int) theme_get_setting('slideshow_effect_time','Accessibility')*1000;
	$slideshow_randomize=theme_get_setting('slideshow_randomize','Accessibility');
	$slideshow_wrap=theme_get_setting('slideshow_wrap','Accessibility');
	$slideshow_pause=theme_get_setting('slideshow_pause','Accessibility');
	
	drupal_add_js('jQuery(document).ready(function($) {
	
	$(window).load(function() {
	
		$("#slideshow img").show();
		$("#slideshow").fadeIn("slow");
		$("#slider-controls-wrapper").fadeIn("slow");
	
		$("#slideshow").cycle({
			fx:    "'.$effect.'",
			speed:  "slow",
			timeout: "'.$effect_time.'",
			random: '.$slideshow_randomize.',
			nowrap: '.$slideshow_wrap.',
			pause: '.$slideshow_pause.',
			pager:  "#slider-navigation",
			pagerAnchorBuilder: function(idx, slide) {
				return "#slider-navigation li:eq(" + (idx) + ") a";
			},
			slideResize: true,
			containerResize: false,
			height: "auto",
			fit: 1,
			before: function(){
				$(this).parent().find(".slider-item.current").removeClass("current");
			},
			after: onAfter
		});
	});
	
	function onAfter(curr, next, opts, fwd) {
		var $ht = $(this).height();
		$(this).parent().height($ht);
		$(this).addClass("current");
	}
	
	$(window).load(function() {
		var $ht = $(".slider-item.current").height();
		$("#slideshow").height($ht);
	});
	
	$(window).resize(function() {
		var $ht = $(".slider-item.current").height();
		$("#slideshow").height($ht);
	});
	
	});',
	array('type' => 'inline', 'scope' => 'footer', 'weight' => 5)
	);

endif;

?>