<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use DOMDocument;
use DOMXPath;

final class _Pornpics extends Provider
{
    protected const REGISTRY = [
        'display' => 'Pornpics',
        'url' => [
            'base' => 'https://www.pornpics.com/',
            'results' => 'https://www.pornpics.com/?q=',
        ],
    ];
    
    /**
     * query the results page
     * @param string $url_
     * @param int $pages_
     * @return array
     */
    protected function results(string $url_, int $pages_): array
    {
        $_result = [];
        $_xpath = $this->parse($url_);
        foreach ($_xpath->query('//li[@class="thumbwook "]/a[@class="rel-link"]') as $node_) {
            $_href = $node_->{'getAttribute'}('href');
            $_node_child = $_xpath->query('img[data-src]', $node_);
            $_node_child_attr = $_node_child->item(0)?->{'getAttribute'}('src');
            //$_src = $this->url('base') . '/images/thumbnail/' . str_replace('.html', '.jpg', substr($_href, strrpos($_href, '/') + 1));
            $_src = $_node_child_attr;
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
        foreach ($_xpath->query('//li[@class="thumbwook "]/a[@class="rel-link"]\img[data-src]') as $node_) {
            $_result[] = $node_->{'getAttribute'}('src');
        }
        return $_result;
    }
}
