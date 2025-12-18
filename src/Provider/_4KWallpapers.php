<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use DOMDocument;
use DOMXPath;

final class _4KWallpapers extends Provider
{
    protected const REGISTRY = [
        'display' => '4K-Wallpapers',
        'url' => [
            'base' => 'https://4kwallpapers.com',
            'results' => 'https://4kwallpapers.com/search/?q=',
        ],
    ];

    /**
     * parse url to DOMXPath
     * @param string $url_
     * @return DOMXPath
     */
    private function parse(string $url_): DOMXPath
    {
        $_dom = new DOMDocument();
        $_dom->loadHTMLFile($url_, LIBXML_NOERROR);
        return new DOMXPath($_dom);
    }

    /**
     * query the results page
     * @param string $url_
     * @param string $prefix_
     * @return array
     */
    protected function results(string $url_): array
    {
        $_result = [];
        $_xpath = $this->parse($url_);
        foreach ($_xpath->query('//a[@class="wallpapers__canvas_image"]') as $node_) {
            $_node_child = $_xpath->query('span[@class="wallpapers__canvas ripple"]/img[@itemprop="thumbnail"]', $node_);
            $_result[$_node_child->item(0)->{'getAttribute'}('src')] = $node_->{'getAttribute'}('href');
        }
        return $_result;
    }

    /**
     * query the detail page
     * @param string $url_
     * @param string $prefix_
     * @return array
     */
    protected function details(string $url_): array
    {
        $_result = [];
        $_xpath = $this->parse($url_);
        foreach ($_xpath->query('//a[@class="current"]') as $node_) {
            $_result[] = $this->url('base') . $node_->{'getAttribute'}('href');
        }
        return $_result;
    }
}
