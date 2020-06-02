<?php

namespace Drupal\tpedu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\MainContent\HtmlRenderer;

class TwRenderer extends HtmlRenderer
{
    public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match)
    {
        list($page, $title) = $this->prepare($main_content, $request, $route_match);
        if (!isset($page['#type']) || $page['#type'] !== 'page') {
            throw new \LogicException('Must be #type page');
        }
        $page['#title'] = $title;

        // Now render the rendered page.html.twig template inside the html.html.twig
        // template, and use the bubbled #attached metadata from $page to ensure we
        // load all attached assets.
        $html = [
            '#type' => 'html',
            'page' => $page,
        ];

        // The special page regions will appear directly in html.html.twig, not in
        // page.html.twig, hence add them here, just before rendering html.html.twig.
        $this->buildPageTopAndBottom($html);

        // Render, but don't replace placeholders yet, because that happens later in
        // the render pipeline. To not replace placeholders yet, we use
        // RendererInterface::render() instead of RendererInterface::renderRoot().
        // @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
        $render_context = new RenderContext();
        $this->renderer->executeInRenderContext($render_context, function () use (&$html) {
            // RendererInterface::render() renders the $html render array and updates
            // it in place. We don't care about the return value (which is just
            // $html['#markup']), but about the resulting render array.
            // @todo Simplify this when https://www.drupal.org/node/2495001 lands.
            $this->renderer->render($html);
            $html['#markup'] = $this->accessibility_for_taiwan($html['#markup']);
        });

        // RendererInterface::render() always causes bubbleable metadata to be
        // stored in the render context, no need to check it conditionally.
        $bubbleable_metadata = $render_context->pop();
        $bubbleable_metadata->applyTo($html);
        $content = $this->renderCache->getCacheableRenderArray($html);

        // Also associate the required cache contexts.
        // (Because we use ::render() above and not ::renderRoot(), we manually must
        // ensure the HTML response varies by the required cache contexts.)
        $content['#cache']['contexts'] = Cache::mergeContexts($content['#cache']['contexts'], $this->rendererConfig['required_cache_contexts']);

        // Also associate the "rendered" cache tag. This allows us to invalidate the
        // entire render cache, regardless of the cache bin.
        $content['#cache']['tags'][] = 'rendered';
        $response = new HtmlResponse($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);

        return $response;
    }

    public function accessibility_for_taiwan($content)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$content);
        $head = $dom->getElementsByTagName('head')[0];
        $meta = $dom->createElement('meta');
        $meta->setAttribute('http-equiv', 'content-type');
        $meta->setAttribute('content', 'text/html; charset=utf-8');
        $head->insertBefore($meta, $head->firstChild);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $myalt = $img->getAttribute('alt');
            if (!$myalt) {
                $img->setAttribute('alt', '排版用裝飾圖片');
            }
        }
        $links = $dom->getElementsByTagName('a');
        $tab_index = 1;
        foreach ($links as $lnk) {
            $mytitle = $lnk->getAttribute('title');
            if (!$mytitle) {
                if ($lnk->nodeValue) {
                    $lnk->setAttribute('title', trim($lnk->nodeValue));
                } else {
                    $lnk->nodeValue = '::';
                    $lnk->setAttribute('title', $lnk->getAttribute('id'));
                }
            }
            $lnk->setAttribute('tabindex', $tab_index);
            ++$tab_index;
        }
        $objs = $dom->getElementsByTagName('object');
        foreach ($objs as $obj) {
            $myalt = $obj->nodeValue;
            if (!$myalt) {
                $obj->nodeValue = $obj->getAttribute('data');
            }
        }
        $applets = $dom->getElementsByTagName('applet');
        foreach ($applets as $app) {
            $myalt = $app->nodeValue;
            if (!$myalt) {
                $app->setAttribute('alt', '多媒體互動物件');
                $app->nodeValue = '多媒體互動物件';
            } else {
                $app->setAttribute('alt', $myalt);
            }
        }
        $img_maps = $dom->getElementsByTagName('area');
        foreach ($img_maps as $area) {
            $myalt = $area->getAttribute('alt');
            if (!$myalt) {
                $area->setAttribute('alt', $area->getAttribute('href'));
            }
        }
        $ems = $dom->getElementsByTagName('i');
        foreach ($ems as $em) {
            $newem = $dom->createElement('em', $em->nodeValue);
            $em->parentNode->replaceChild($newem, $em);
        }
        $strongs = $dom->getElementsByTagName('b');
        foreach ($strongs as $strong) {
            $newstrong = $dom->createElement('strong', $strong->nodeValue);
            $strong->parentNode->replaceChild($newstrong, $strong);
        }
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $mytitle = $table->getAttribute('title');
            $mysummary = $table->getAttribute('summary');
            $caption = false;
            if ($table->hasChildNodes()) {
                if ($table->firstChild->nodeName == 'caption') {
                    $caption = $table->firstChild->nodeValue;
                }
            }
            if (!$mytitle && $caption) {
                $table->setAttribute('title', $caption);
            }
            if (!$mysummary && $caption) {
                $table->setAttribute('summary', $caption);
            }
            if (!$mytitle && !$mysummary && !$caption) {
                $table->setAttribute('summary', '排版用表格');
            }
        }
        $table_headers = $dom->getElementsByTagName('th');
        foreach ($table_headers as $myth) {
            $myscope = $myth->getAttribute('scope');
            $myheaders = $myth->getAttribute('headers');
            if (!$myscope && !$myheaders) {
                $myth->setAttribute('scope', 'col');
            }
        }
        $content = $dom->saveHTML();
        $content = str_replace('<?xml encoding="utf-8" ?>', '', $content);
        $content = str_replace('!DOCTYPE html', '!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"', $content);

        return $content;
    }
}
