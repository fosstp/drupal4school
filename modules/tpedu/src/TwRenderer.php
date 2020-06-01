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
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.$content);
        $images = $dom->getElementsByTagName('img');
        $links = $dom->getElementsByTagName('a');
        $objs = $dom->getElementsByTagName('object');
        $applets = $dom->getElementsByTagName('applet');
        $img_maps = $dom->getElementsByTagName('area');
        $ems = $dom->getElementsByTagName('i');
        $strongs = $dom->getElementsByTagName('b');
        $tables = $dom->getElementsByTagName('table');
        $table_headers = $dom->getElementsByTagName('th');
        foreach ($images as $img) {
            $myalt = $img->getAttribute('alt');
            if (!$myalt) {
                $img->setAttribute('alt', '排版用裝飾圖片');
            }
        }
        $tab_index = 1;
        foreach ($links as $lnk) {
            $mytitle = $lnk->getAttribute('title');
            if (!$mytitle) {
                $lnk->setAttribute('title', $lnk->nodeValue);
            }
            $lnk->setAttribute('tabindex', $tab_index);
            ++$tab_index;
        }
        foreach ($objs as $obj) {
            $myalt = $obj->nodeValue;
            if (!$myalt) {
                $obj->nodeValue = $obj->getAttribute('data');
            }
        }
        foreach ($applets as $app) {
            $myalt = $app->nodeValue;
            if (!$myalt) {
                $app->setAttribute('alt', '多媒體互動物件');
                $app->nodeValue = '多媒體互動物件';
            } else {
                $app->setAttribute('alt', $myalt);
            }
        }
        foreach ($img_maps as $area) {
            $myalt = $area->getAttribute('alt');
            if (!$myalt) {
                $area->setAttribute('alt', $area->getAttribute('href'));
            }
        }
        foreach ($ems as $em) {
            $newem = $dom->createElement('em', $em->nodeValue);
            $em->parentNode->replaceChild($newem, $em);
        }
        foreach ($strongs as $strong) {
            $newstrong = $dom->createElement('strong', $strong->nodeValue);
            $strong->parentNode->replaceChild($newstrong, $strong);
        }
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
        foreach ($table_headers as $myth) {
            $myscope = $myth->getAttribute('scope');
            $myheaders = $myth->getAttribute('headers');
            if (!$myscope && !$myheaders) {
                $myth->setAttribute('scope', 'col');
            }
        }
        $content = $dom->saveHTML();
        $content = preg_replace('/<!DOCTYPE .+>/', '', preg_replace('/<meta .+?>/', '', str_replace(array('<html>', '</html>', '<head>', '</head>', '<body>', '</body>'), array('', '', '', '', '', ''), $content)));

        return $content;
    }
}
