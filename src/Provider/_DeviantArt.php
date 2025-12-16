<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

final class _DeviantArt extends Provider
{
    protected const REGISTRY = [
        'url' => [
            'base' => 'https://www.deviantart.com/',
            'search' => 'https://www.deviantart.com/search?q=',
        ],
        'expression' => [
            'results' => '//img[@promerty="contentUrl"]',
            'images' => '//div[@typeof="ImageObject"]/img',
        ],
        'attribute' => [
            'results' => 'srcset',
            'images' => 'src',
        ],
    ];
}
