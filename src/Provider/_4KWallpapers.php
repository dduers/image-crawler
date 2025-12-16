<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

final class _4KWallpapers extends Provider
{
    protected const REGISTRY = [
        'url' => [
            'base' => 'https://4kwallpapers.com',
            'search' => 'https://4kwallpapers.com/search/?q=',
        ],
        'expression' => [
            //'results' => '//a[@class="wallpapers__canvas_image"]',
            'results' => '//p[@class="wallpapers__item"]/a[@class="wallpapers__canvas_image"]',
            //'images' => '//a[@class="current"]',
            'images' => '//span[@class="res-ttl"]/a[@class="current"]',
        ],
    ];
}
