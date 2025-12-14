<?php

namespace Dduers\ImageCrawler;

use DOMDocument;
use DOMXPath;
use Web;

class ImageCrawler
{
    /**
     * list of search engine and api urls
     */
    private const SEARCH_ENGINES = [
        'ecosia' => 'https://www.ecosia.org/images?q=',
        'google' => 'https://www.google.com/images?q=',
    ];

    /**
     * constructor
     * @param string $_searchEngine
     */
    function __construct(
        private string $_searchEngine = 'google'
    ) {}

    /**
     * get image urls
     * @param string $searchTerm_
     * @param int $count_
     * @param string $sourcePrefix_
     * @return array|false
     */
    public function getImageUrls(string $searchTerm_, int $count_ = 3, string $sourcePrefix_ = 'https://'): array|false
    {
        if (!$searchTerm_)
            return false;

        $_result = [];
        $_web = Web::instance();
        $_searchTerm = rawurlencode($searchTerm_);
        $_url = self::SEARCH_ENGINES[$this->_searchEngine] . $_searchTerm;
        $_response = $_web->request($_url);
        $_dom = new DOMDocument();
        libxml_use_internal_errors(TRUE);
        $_dom->loadHTML($_response['body']);
        libxml_clear_errors();
        $_xpath = new DOMXPath($_dom);
        // for google
        //$_elements = $_xpath->query("//img[@src]");
        // for ecosia
        $_elements = $_xpath->query("//a[@class='image-result__link']");
        if ($_elements === false)
            return false;

        $sourcePrefix_ = 'https://';
        $_count = $_elements->length < $count_ ? $_elements->length : $count_;
        for ($_i = 0; $_i <= $_count; $_i++) {
            $_src = $_elements->item($_i)?->getAttribute('href');
            if ($_src && str_starts_with($_src, $sourcePrefix_)) {
                $_result[] = $_src;
                if (count($_result) >= $_count)
                    break;
            }
        }
        return $_result;
    }


    /**
     * get the html content from url
     * (does not work like WEB::instance()->request() ?!)
     * @param string $url_
     * @return string
     */
    /*
    function getHtmlContentByUrl(string $url_): string
    {
        $_curl = curl_init($url_);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($_curl, CURLOPT_AUTOREFERER, true);
        $_html = curl_exec($_curl);
        curl_close($_curl);
        return $_html;
    }
    */
}
