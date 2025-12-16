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

    public static function expression(string $identifier_): string
    {
        return static::{'REGISTRY'}[__FUNCTION__][$identifier_];
    }

    public static function attribute(string $identifier_): string
    {
        return static::{'REGISTRY'}[__FUNCTION__][$identifier_];
    }

    /**
     * xpath query on an content behind an url
     * @param string $url_
     * @param string $identifier_ id of xpath explression as registered by provider classes
     * @param string $prefix_ prefix results
     * @param string $attr_ attribute to grab the results from
     * @return false|array
     */
    public function query(string $url_, string $expression_, string $prefix_ = '', string $attr_ = 'href'): false|array
    {
        $_response = $this->curl($url_);
        if ($_response === false)
            return false;

        $_result = [];
        $_elements = $this->parseHtml($_response)->query($expression_);
        unset($_response);
        for ($_i = 0; $_i <= ($_elements?->length ?? 0); $_i++)
            if ($_r = $_elements->item($_i)?->{'getAttribute'}($attr_))
                $_result[] = $prefix_ . $_r;

        unset($_elements);
        return $_result;
    }

    /**
     * parse html text file to domxpath
     * @param string $html_
     * @return DOMXPath
     */
    private function parseHtml(string $html_): DOMXPath
    {
        $_dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $_dom->loadHTML($html_);
        libxml_clear_errors();
        $_xpath = new DOMXPath($_dom);
        unset($_dom);
        return $_xpath;
    }

    /**
     * get content from url
     * @param string $url_
     * @param array $options_
     * @return string
     */
    private function curl(string $url_, array $options_ = []): false|string
    {
        if (($_curl = curl_init()) === false)
            return false;
        curl_setopt_array($_curl, [
            CURLOPT_URL => $url_,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_AUTOREFERER => true,
            ...$options_
        ]);
        $_result = curl_exec($_curl);
        unset($_curl);
        return $_result;
    }
}
