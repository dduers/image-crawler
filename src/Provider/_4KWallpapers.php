<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

final class _4KWallpapers
{
    private const _PROVIDER_ = [
        'base' => 'https://4kwallpapers.com',
        'query' => 'https://4kwallpapers.com/search/?q=',
        'xpath' => [
            'results' => '//a[@class="wallpapers__canvas_image"]',
            'images' => '//a[@class="current"]',
        ],
    ];

    public static function instance($class_): self
    {
        return new self;
    }

    public static function base(): string
    {
        return self::_PROVIDER_['base'];
    }

    public static function query(): string
    {
        return self::_PROVIDER_['query'];
    }

    public static function xpath_results(): string
    {
        return self::_PROVIDER_['xpath']['results'];
    }

    public static function xpath_images(): string
    {
        return self::_PROVIDER_['xpath']['images'];
    }
}
