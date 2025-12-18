<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use Dom\XMLDocument;
use DOMDocument;
use DOMXPath;

final class _Wallpapers extends Provider
{
    protected const REGISTRY = [
        'display' => 'Wallpapers',
        'url' => [
            'base' => 'https://wallpapers.com',
            'results' => 'https://wallpapers.com/search/',
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
        foreach ($_xpath->query('//figure[@class="detail-data"]/a[picture]') as $node_) {
            $_href = $node_->{'getAttribute'}('href');
            //$_node_child = $_xpath->query('picture/img[@class="promote"]', $node_);
            //$_node_child_attr = $_node_child->item(0)?->{'getAttribute'}('src');
            $_src = $this->url('base') . '/images/thumbnail/' . str_replace('.html', '.jpg', substr($_href, strrpos($_href, '/') + 1));
            $_result[$_src] = $_href;
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
        foreach ($_xpath->query('//img[@class="post-image priority promote"]') as $node_) {
            $_result[] = $this->url('base') . $node_->{'getAttribute'}('src');
        }
        return $_result;
    }
}
