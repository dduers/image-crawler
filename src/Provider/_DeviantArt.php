<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use DOMDocument;
use DOMXPath;

final class _DeviantArt extends Provider
{
    protected const REGISTRY = [
        'url' => [
            'base' => 'https://www.deviantart.com',
            'results' => 'https://www.deviantart.com/search?q=',
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
        foreach ($_xpath->query('/html/body/main/div/div[1]/div[1]/div/div[2]/img') as $node_) {
            $_result[] = $node_->{'getAttribute'}('src');
        }
        return $_result;
    }
}
