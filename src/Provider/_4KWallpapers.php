<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

final class _4KWallpapers
{
    use ProviderTrait;

    private const _PROVIDER_ = [
        'base' => 'https://4kwallpapers.com',
        'query' => 'https://4kwallpapers.com/search/?q=',
        'xpath' => [
            'results' => '//a[@class="wallpapers__canvas_image"]',
            'images' => '//a[@class="current"]',
        ],
    ];
}
