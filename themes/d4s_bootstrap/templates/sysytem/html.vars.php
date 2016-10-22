<?php
/**
 * @file
 * Stub file for "html" theme hook [pre]process functions.
 */

/**
 * Pre-processes variables for the "html" theme hook.
 *
 * See template for list of available variables.
 *
 * @see html.tpl.php
 *
 * @ingroup theme_preprocess
 */
function d4s_bootstrap_preprocess_html(&$variables) {
  // Backport from Drupal 8 RDFa/HTML5 implementation.
  // @see https://www.drupal.org/node/1077566
  // @see https://www.drupal.org/node/1164926

  // HTML element attributes.
  $variables['html_attributes_array'] = array(
    'lang' => $variables['language']->language,
    'dir' => $variables['language']->dir,
  );

  // Override existing RDF namespaces to use RDFa 1.1 namespace prefix bindings.
  if (function_exists('rdf_get_namespaces')) {
    $rdf = array('prefix' => array());
    foreach (rdf_get_namespaces() as $prefix => $uri) {
      $rdf['prefix'][] = $prefix . ': ' . $uri;
    }
    if (!$rdf['prefix']) {
      $rdf = array();
    }
    $variables['rdf_namespaces'] = drupal_attributes($rdf);
  }

  // BODY element attributes.
  $variables['body_attributes_array'] = array(
    'role'  => 'document',
    'class' => &$variables['classes_array'],
  );
  $variables['body_attributes_array'] += $variables['attributes_array'];

  // Navbar position.
  switch (bootstrap_setting('navbar_position')) {
    case 'fixed-top':
      $variables['body_attributes_array']['class'][] = 'navbar-is-fixed-top';
      break;

    case 'fixed-bottom':
      $variables['body_attributes_array']['class'][] = 'navbar-is-fixed-bottom';
      break;

    case 'static-top':
      $variables['body_attributes_array']['class'][] = 'navbar-is-static-top';
      break;
  }
}

/**
 * Processes variables for the "html" theme hook.
 *
 * See template for list of available variables.
 *
 * @see html.tpl.php
 *
 * @ingroup theme_process
 */
function d4s_bootstrap_process_html(&$variables) {
  $variables['html_attributes'] = drupal_attributes($variables['html_attributes_array']);
  $variables['body_attributes'] = drupal_attributes($variables['body_attributes_array']);
  $variables['page_top'] = Accessibility_for_taiwan($variables['page_top']);
  $variables['page_bottom'] = Accessibility_for_taiwan($variables['page_bottom']);
  $variables['page'] = Accessibility_for_taiwan($variables['page']);
}

function d4s_bootstrap_form_search_block_form_alter(&$form, &$form_state, $form_id) {
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