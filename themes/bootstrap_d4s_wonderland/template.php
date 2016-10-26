<?php
/**
 * @file
 * The primary PHP file for this theme.
 */

/**
 * Override or insert variables into the html template.
 */
function bootstrap_d4s_wonderland_process_html(&$vars) {
    $vars['page_top'] = bootstrap_d4s_wonderland($vars['page_top']);
    $vars['page_bottom'] = bootstrap_d4s_wonderland($vars['page_bottom']);
    $vars['page'] = bootstrap_d4s_wonderland($vars['page']);
}

$tab_index = 1;
function bootstrap_d4s_wonderland($content) {
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
    /*foreach ($ems as $em) {
        $newem=$dom->createElement('em',$em->nodeValue);
        $em->parentNode->replaceChild($newem, $em);
    }*/
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

