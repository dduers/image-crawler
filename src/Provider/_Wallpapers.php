<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

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
     * query the results page
     * @param string $url_
     * @param int $pages_
     * @return array
     */
    protected function results(string $url_, int $pages_ = 1): array
    {
        $_result = [];
        foreach (range(1, $pages_) as $page_) {
            $_xpath = $this->parse($url_ . ($pages_ > 1 ? '?p=' . $page_ : ''));
            foreach ($_xpath->query('//figure[@class="detail-data"]/a[picture]') as $node_) {
                $_href = $node_->{'getAttribute'}('href');
                $_src = $this->url('base') . '/images/thumbnail/' . str_replace('.html', '.jpg', substr($_href, strrpos($_href, '/') + 1));
                $_result[$_src] = $_href;
            }
        }
        return $_result;
    }

    /**
     * query the detail page
     * @param string $url_
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
        foreach ($_xpath->query('//h1/following-sibling::p') as $node_) {
            $_result['description'] = trim($node_->{'textContent'});
        }
        $_result['source'] = $url_;
        return $_result;
    }
}
