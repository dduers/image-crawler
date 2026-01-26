<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler\Provider;

use DOMDocument;
use DOMXPath;

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
    public function queryResults(string $searchterm_, int $pages_ = 1): false|array
    {
        return $this->{'results'}($this->url('results') . rawurlencode($searchterm_), $pages_);
    }

    /**
     * get file urls
     * @param string $url_
     * @return false|array
     */
    public function queryDetails(string $url_): false|array
    {
        return $this->{'details'}($url_);
    }


    /**
     * parse url to DOMXPath
     * @param string $url_
     * @return DOMXPath
     */
    protected function parse(string $url_): DOMXPath
    {
        $_html = $this->curl($url_);
        $_dom = new DOMDocument();
        //$_dom->loadHTMLFile($url_, LIBXML_NOERROR);
        $_dom->loadHTML($_html, LIBXML_NOERROR);
        return new DOMXPath($_dom);
    }

    /**
     * curl by url
     * @param string $url_
     * @param string $agent_
     * @return string|false
     */
    protected function curl(string $url_, string $agent_ = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'): string|false
    {
        $_curl = curl_init();
        curl_setopt($_curl, CURLOPT_USERAGENT, $agent_);
        curl_setopt($_curl, CURLOPT_URL, $url_);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        $_result = curl_exec($_curl);
        return $_result;
    }
}
