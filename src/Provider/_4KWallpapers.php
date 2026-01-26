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
     * query the results page
     * @param string $url_
     * @param int $pages_
     * @return array
     */
    protected function results(string $url_, int $pages_ = 1): array
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

    /**
     * query meta details
     * @param string $url_
     * @return array
     */
    protected function detailsMeta(string $url_): array
    {
        $_result = [];
        $_xpath = $this->parse($url_);
        foreach ($_xpath->query('//span[@class="main-id"]//h1') as $node_) {
            $_result['title'] = trim($node_->{'textContent'});
        }
        foreach ($_xpath->query('//span[@class="main-id"]/following-sibling::p') as $node_) {
            $_result['description'] = trim($node_->{'textContent'});
        }
        $_result['source'] = $url_;
        return $_result;
    }
}
