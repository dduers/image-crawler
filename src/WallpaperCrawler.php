<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler;

use DOMDocument;
use DOMXPath;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * wallpaper crawler
 * @author Daniel Duersteler <daniel.duersteler@xsite.ch
 */
class WallpaperCrawler
{
    private DOMDocument $_dom;
    private DOMXPath $_xpath;
    private object $_provider;
    private string $_cache_path;
    private array $_resultUrls = [];
    private array $_imageUrls = [];

    /**
     * constructor
     * @param string $_provider
     */
    function __construct(object $provider_, string $imagesLocalPath_ = 'images/')
    {
        $this->_dom = new DOMDocument();
        $this->_provider = $provider_;
        $this->_cache_path = $imagesLocalPath_;
    }

    /**
     * output picture from local storage
     * @return never
     */
    public function outputCachedRandom(string $version_ = 'use'): never
    {

        $_files = $this->listCached();
        if (count($_files)) {
            $_file = $_files[rand(0, count($_files) - 1)];
            $this->outputCached($_file, $version_);
        }
        exit();
    }

    /**
     * output a cached wallpaper
     */
    public function outputCached(string $filename_, string $version_ = 'use'): never
    {
        $_file = $this->_cache_path . $filename_ . '/' . $version_ . '.jpg';
        $_meta = getimagesize($_file);
        $_data = file_get_contents($_file);
        header('Content-Type: ' . $_meta['mime']);
        echo $_data;
        exit();
    }

    /**
     * delete an image from local storage
     * @param string $filename_ without images path
     * @param bool $blacklist_
     */
    public function deleteCached(string $filename_, bool $blacklist_ = true): bool
    {
        if (file_exists($this->_cache_path . $filename_)) {
            $this->removeDirectory($this->_cache_path . $filename_);
        }
        if ($blacklist_ === true) {
            $this->addToBlacklist($filename_);
        }
        return true;
    }

    /**
     * output a random wallpaper
     * @param string $terms_
     * @return never
     */
    public function crawlRemote(string $searchterm_, $resultcount_ = 3): false|string
    {
        // get result urls
        $this->queryResultUrls($searchterm_, $resultcount_);

        $_random_url = $this->getRandomResultUrl();
        if (!$_random_url) {
            return false;
        }

        // get a random image from the results
        $this->queryImageUrls($_random_url);

        $_url = $this->_imageUrls[0];
        $_data = file_get_contents($_url);
        $_file_id = md5($_data);

        // if blacklisted locally, don't download to cache
        if ($this->isBlacklisted($_file_id)) {
            return false;
        }

        if ($this->saveToCache($_file_id, $_data) === false) {
            return false;
        }

        return $_file_id;
    }

    /**
     * get local file id list
     * @return array
     */
    public function listCached(?int $count_ = null, ?int $offset_ = null): array
    {
        $_result = glob($this->_cache_path . '*', GLOB_ONLYDIR);
        array_walk($_result, function (&$item_, $key_) {
            $item_ = explode('/', $item_);
            $item_ = array_pop($item_);
        });
        if ($offset_ !== null) {
            for (; $offset_--; array_shift($_result));
        }
        if ($count_ !== null) {
            $_result = array_slice($_result, 0, $count_);
        }
        return $_result;
    }

    /**
     * remove a directory and all pictures inside
     * @param string $directory
     * @return bool
     */
    private function removeDirectory(string $directory_): bool
    {
        $_directory = new RecursiveDirectoryIterator($directory_, RecursiveDirectoryIterator::SKIP_DOTS);
        $_files = new RecursiveIteratorIterator(
            $_directory,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        // remove files
        foreach ($_files as $file_) {
            if ($file_->isDir()) {
                rmdir($file_->getPathname());
            } else {
                unlink($file_->getPathname());
            }
        }
        // remove directory itself
        return rmdir($directory_);
    }

    /**
     * save image to file
     * @param string $filename_
     * @param string $data_
     * @return bool
     */
    private function saveToCache(string $filename_, string $data_): bool
    {
        if (!file_exists($this->_cache_path . $filename_)) {
            if (!mkdir($this->_cache_path . $filename_, 0777, true)) {
                return false;
            }
        }

        file_put_contents($this->_cache_path . $filename_ . '/source.jpg', $data_);
        file_put_contents($this->_cache_path . $filename_ . '/use.jpg', $data_);
        file_put_contents($this->_cache_path . $filename_ . '/thumb.jpg', $data_);

        if ($this->resizeImage($filename_ . '/use.jpg', 1600, 1200) && $this->resizeImage($filename_ . '/thumb.jpg', 320, 240)) {
            return true;
        }

        return false;
    }

    /**
     * get image meta data
     */
    public function getImageMetaData(string $fileid_, string $version_ = 'use'): array
    {
        $_meta = getimagesize($this->_cache_path . $fileid_ . '/' . $version_ . '.jpg');
        $_result['width'] = $_meta[0];
        $_result['height'] = $_meta[1];
        $_result['mime'] = $_meta['mime'];
        return $_result;
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
        $_url = $this->_provider->queryUrl() . rawurlencode($term_);

        $_response = $this->getHtmlContentByUrl($_url);
        if ($_response === false) {
            return false;
        }

        $this->loadDOM($_response);

        $_elements = $this->_xpath->query($this->_provider->xpathResults());
        if ($_elements === false) {
            return false;
        }

        $_count = $_elements->length < $count_ ? $_elements->length : $count_;
        for ($_i = 0; $_i <= $_count; $_i++) {
            if ($_src = $_elements->item($_i)?->getAttribute('href')) {
                $this->_resultUrls[] = $_src;
            }
        }

        return true;
    }

    /**
     * get urls from a image result
     * @param string $queryUrl
     * @return bool
     */
    private function queryImageUrls(string $queryUrl_): bool
    {
        $_response = $this->getHtmlContentByUrl($queryUrl_);

        if ($_response === false) {
            return false;
        }

        $this->loadDOM($_response);

        $_elements = $this->_xpath->query($this->_provider->xpathImages());
        if ($_elements === false) {
            return false;
        }

        for ($_i = 0; $_i <= $_elements->length; $_i++) {
            if ($_src = $_elements->item($_i)?->getAttribute('href')) {
                $this->_imageUrls[] = $this->_provider->baseUrl() . $_src;
            }
        }
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
     * add a file name to the blacklist
     * @param string $filename_
     * @return bool
     */
    private function addToBlacklist(string $filename_): bool
    {
        if (!$this->isBlacklisted($filename_) && file_put_contents($this->_cache_path . 'blacklist.txt', $filename_ . "\n", FILE_APPEND)) {
            return true;
        }
        return false;
    }

    /**
     * check if a filename is blacklisted
     * @param string $filename_
     * @return bool
     */
    private function isBlacklisted(string $filename_): bool
    {
        if (exec('grep ' . escapeshellarg($filename_) . ' ' . $this->_cache_path . 'blacklist.txt')) {
            return true;
        }
        return false;
    }

    /**
     * get content from url
     * @param string $url_
     * @return string
     */
    private function getHtmlContentByUrl(string $url_): string|false
    {
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
     * resize jpeg image and save to same filename
     * @param string $filename_
     * @param int $targetWidth_
     * @param int $targetHeight_
     * @param bool $crop_
     * @return bool
     */
    private function resizeImage(string $filename_, int $targetWidth_, int $targetHeight_, bool $crop_ = false): bool
    {
        $_filename = $this->_cache_path . $filename_;

        $_meta = getimagesize($_filename);

        $_width = $_meta[0];
        $_height = $_meta[1];
        $_mime = $_meta['mime'];

        $_r = $_width / $_height;

        if ($crop_ === true) {
            if ($_width > $_height) {
                $_width = ceil($_width - ($_width * abs($_r - $targetWidth_ / $targetHeight_)));
            } else {
                $_height = ceil($_height - ($_height * abs($_r - $targetWidth_ / $targetHeight_)));
            }
            $_newWidth = $targetWidth_;
            $_newHeight = $targetHeight_;
        } else {
            if ($targetWidth_ / $targetHeight_ > $_r) {
                $_newWidth = $targetHeight_ * $_r;
                $_newHeight = $targetHeight_;
            } else {
                $_newHeight = $targetWidth_ / $_r;
                $_newWidth = $targetWidth_;
            }
        }

        switch ($_mime) {
            case 'image/jpeg':
                $_src = imagecreatefromjpeg($_filename);
                break;

            case 'image/png':
                $_src = imagecreatefrompng($_filename);
                break;
        }

        $_dst = imagecreatetruecolor((int)$_newWidth, (int)$_newHeight);
        imagecopyresampled($_dst, $_src, 0, 0, 0, 0, (int)$_newWidth, (int)$_newHeight, $_width, $_height);
        return imagejpeg($_dst, $_filename);
    }
}
