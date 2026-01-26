<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use DOMDocument;
use DOMXPath;

final class _DeviantArt extends Provider
{
    protected const REGISTRY = [
        'display' => 'Deviant-Art',
        'url' => [
            'base' => 'https://www.deviantart.com',
            'results' => 'https://www.deviantart.com/search?q=',
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
        foreach ($_xpath->query('//div[@class="Hw5CoU dwd9jn"]/a') as $node_) {
            $_node_child = $_xpath->query('div["vYfnpn"]/img', $node_);
            $_node_child_attr = $_node_child->item(0)?->{'getAttribute'}('src');
            if ($_node_child_attr)
                $_result[$_node_child_attr] = $node_->{'getAttribute'}('href');
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

        // TODO:: better xpath
        //foreach ($_xpath->query('//img[@class="lGws3n imYPxe"]') as $node_) {
        foreach ($_xpath->query('//div[@typeof="ImageObject"]/img') as $node_) {
            $_result[] = $node_->{'getAttribute'}('src');
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
        foreach ($_xpath->query('//h1') as $node_) {
            $_result['title'] = trim($node_->{'textContent'});
        }
        foreach ($_xpath->query('//div[@id="description"]/div/div/p[position()=1]') as $node_) {
            $_result['description'] = trim($node_->{'textContent'});
        }
        $_result['source'] = $url_;
        return $_result;
    }
}
