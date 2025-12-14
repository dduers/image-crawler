<?php

namespace Dduers\ImageCrawler;

use DOMDocument;
use DOMXPath;

/**
 * wallpaper crawler
 * @author Daniel Duersteler <daniel.duersteler@xsite.ch
 */
class WallpaperCrawler
{
    private const URL_PROVIDERS = [
        '4kwallpapers.com' => [
            'base' => 'https://4kwallpapers.com',
            'query' => 'https://4kwallpapers.com/search/?q=',
            'xpaths' => [
                'results' => '//a[@class="wallpapers__canvas_image"]',
                'images' => '//a[@class="current"]',
            ],
        ],
    ];

    private DOMDocument $_dom;
    private DOMXPath $_xpath;
    private string $_provider;
    private string $_images_local_path;
    private array $_resultUrls;
    private array $_imageUrls;

    /**
     * constructor
     * @param string $_provider
     */
    function __construct(string $provider_ = '4kwallpapers.com', string $imagesLocalPath_ = 'images/')
    {
        $this->_dom = new DOMDocument();
        $this->_provider = $provider_;
        $this->_images_local_path = $imagesLocalPath_;
    }

    /**
     * get count of local images
     * @return int
     */
    public function getCountOfLocalImages(): int
    {
        return count(glob($this->_images_local_path . '*.jpg'));
    }

    /**
     * output picture from local storage
     * @return never
     */
    public function outputRandomWallpaperFromLocal(): never
    {
        $_files = glob($this->_images_local_path . '*.jpg');
        $_picture_data = '';
        $_picture_info = ['mime' => 'image/jpeg'];
        if (count($_files)) {
            $_picture_file = $_files[rand(0, count($_files) - 1)];
            $_picture_info = getimagesize($_picture_file);
            $_picture_data = file_get_contents($_picture_file);
        }
        header('Content-Type: ' . $_picture_info['mime']);
        echo $_picture_data;
        exit();
    }

    /**
     * output a random wallpaper
     * @param array $terms_
     * @return never
     */
    public function outputRandomWallpaper(array $terms_ = ['landscape'], $countResults_ = 3, $saveToFile_ = false): never
    {
        // get a random image path
        $_search_term_random = $terms_[rand(0, count($terms_) - 1)];
        // get query result urls
        $this->queryResultUrls($_search_term_random, $countResults_);
        // get image from a random result
        $this->queryImageUrls($this->getRandomResultUrl());
        $_picture_url = $this->_imageUrls[0];
        // get image information
        $_picture_info = getimagesize($_picture_url);
        // get image data
        $_picture_data = file_get_contents($_picture_url);
        // save image to file
        if ($saveToFile_ === true) {
            $_filename = $this->getFilenameByUrl($_picture_url);
            $this->saveImageToFile($_filename, $_picture_data);
            $this->resizeJpeg($this->_images_local_path . $_filename, 1600, 1200);
            $_picture_data = file_get_contents($this->_images_local_path . $_filename);
        }
        // output image
        header('Content-Type: ' . $_picture_info['mime']);
        echo $_picture_data;
        exit();
    }

    /**
     * save image to file
     * @param string $filename_
     * @param string $data_
     * @return bool
     */
    private function saveImageToFile(string $filename_, string $data_): bool
    {
        if (!file_exists($this->_images_local_path))
            mkdir($this->_images_local_path);
        file_put_contents($this->_images_local_path . $filename_, $data_);
        return true;
    }

    /**
     * get a random result url
     * @return string|false
     */
    private function getRandomResultUrl(): string|false
    {
        return $this->_resultUrls[rand(0, count($this->_resultUrls) - 1)] ?? false;
    }

    /**
     * get images url
     * @param string $term_
     * @param int $count_
     * @return bool
     */
    private function queryResultUrls(string $term_, int $count_ = 3): bool
    {
        if (!$term_)
            return false;

        // combine searchterm with provider query url
        $_url = self::URL_PROVIDERS[$this->_provider]['query'] . rawurlencode($term_);

        $_response = $this->getHtmlContentByUrl($_url);
        if ($_response === false)
            return false;
        $this->loadDOM($_response);

        $_elements = $this->_xpath->query(self::URL_PROVIDERS[$this->_provider]['xpaths']['results']);
        if ($_elements === false)
            return false;

        $_count = $_elements->length < $count_ ? $_elements->length : $count_;
        for ($_i = 0; $_i <= $_count; $_i++)
            if ($_src = $_elements->item($_i)?->getAttribute('href'))
                $this->_resultUrls[] = $_src;

        return true;
    }

    /**
     * get urls from a image result
     * @param string $queryUrl
     * @return void
     */
    private function queryImageUrls(string $queryUrl_): bool
    {
        $_response = $this->getHtmlContentByUrl($queryUrl_);
        if ($_response === false)
            return false;
        $this->loadDOM($_response);

        $_elements = $this->_xpath->query(self::URL_PROVIDERS[$this->_provider]['xpaths']['images']);
        if ($_elements === false)
            return false;

        for ($_i = 0; $_i <= $_elements->length; $_i++)
            if ($_src = $_elements->item($_i)?->getAttribute('href'))
                $this->_imageUrls[] = self::URL_PROVIDERS[$this->_provider]['base'] . $_src;
        return true;
    }

    /**
     * load html string to dom document
     * @param string $html_
     * @return bool
     */
    private function loadDOM(string $html_): bool
    {
        libxml_use_internal_errors(true);
        $this->_dom->loadHTML($html_);
        libxml_clear_errors();
        $this->_xpath = new DOMXPath($this->_dom);
        return true;
    }

    /**
     * get content from url
     * @param string $url_
     * @return string
     */
    private function getHtmlContentByUrl(string $url_): string|false
    {
        $_result = '';
        $_curl = curl_init($url_);
        if ($_curl === false)
            return false;
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($_curl, CURLOPT_AUTOREFERER, true);
        $_result = curl_exec($_curl);
        unset($_curl);
        return $_result;
    }

    /**
     * get the filename by an url
     * @param string $url_
     * @return string
     */
    private function getFilenameByUrl(string $url_): string
    {
        $_parts = explode('/', $url_);
        return array_pop($_parts);
    }

    /**
     * resize jpeg image and save to same filename
     * @param string $filename_
     * @param int $targetWidth_
     * @param int $targetHeight_
     * @param bool $crop_
     * @return bool
     */
    private function resizeJpeg(string $filename_, int $targetWidth_, int $targetHeight_, bool $crop_ = false): bool
    {
        list($_width, $_height) = getimagesize($filename_);
        $r = $_width / $_height;
        if ($crop_ === true) {
            if ($_width > $_height)
                $_width = ceil($_width - ($_width * abs($r - $targetWidth_ / $targetHeight_)));
            else $_height = ceil($_height - ($_height * abs($r - $targetWidth_ / $targetHeight_)));
            $_newWidth = $targetWidth_;
            $_newHeight = $targetHeight_;
        } else {
            if ($targetWidth_ / $targetHeight_ > $r) {
                $_newWidth = $targetHeight_ * $r;
                $_newHeight = $targetHeight_;
            } else {
                $_newHeight = $targetWidth_ / $r;
                $_newWidth = $targetWidth_;
            }
        }

        $_src = imagecreatefromjpeg($filename_);
        $_dst = imagecreatetruecolor((int)$_newWidth, (int)$_newHeight);
        imagecopyresampled($_dst, $_src, 0, 0, 0, 0, (int)$_newWidth, (int)$_newHeight, $_width, $_height);
        return imagejpeg($_dst, $filename_);
        //return $_dst;
    }
}
