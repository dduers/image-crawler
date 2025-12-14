<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

trait ProviderTrait
{
    public static function baseUrl(): string
    {
        return static::_PROVIDER_['base'];
    }

    public static function queryUrl(): string
    {
        return static::_PROVIDER_['query'];
    }

    public static function xpathResults(): string
    {
        return static::_PROVIDER_['xpath']['results'];
    }

    public static function xpathImages(): string
    {
        return static::_PROVIDER_['xpath']['images'];
    }
}
