<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

final class _DeviantArt
{
    use ProviderTrait;

    private const _PROVIDER_ = [
        'base' => 'https://www.deviantart.com',
        'query' => 'https://www.deviantart.com/search?q=',
        'xpath' => [
            'results' => '//div[@typeof="ImageObject"]/img',
            'images' => '//div[@typeof="ImageObject"]/img',
        ],
    ];
}
