<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

class Provider
{

    public static function url(string $identifier_): string
    {
        return static::{'REGISTRY'}[__FUNCTION__][$identifier_];
    }

    /**
     * get ressource urls
     * @param string $searchterm_
     * @return false|array
     */
    public function queryResults(string $searchterm_, string $prefix_): false|array
    {
        return $this->{'results'}($this->url('results') . rawurlencode($searchterm_), $prefix_);
    }

    /**
     * get file urls
     * @param string $url_
     * @return false|array
     */
    public function queryDetails(string $url_, string $prefix_): false|array
    {
        return $this->{'details'}($url_, $prefix_);
    }
}
