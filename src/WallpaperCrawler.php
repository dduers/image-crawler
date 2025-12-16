<?php

declare(strict_types=1);

namespace Dduers\ImageCrawler;

use Dduers\ImageCrawler\Provider\Provider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * wallpaper crawler
 * @author Daniel Duersteler <daniel.duersteler@xsite.ch
 */
class WallpaperCrawler
{
    private Provider $_provider;
    private string $_cache_path;

    /**
     * constructor
     * @param string $_provider
     */
    function __construct(string $provider_, string $cachePath_ = 'images/')
    {
        $this->_provider = new $provider_();
        $this->_cache_path = $cachePath_;
    }

    /**
     * output random from local cache
     * @param string $version_
     * @return never
     */
    public function outputCachedRandom(string $version_ = 'use'): never
    {
        $_fileids = $this->listCached();
        if (count($_fileids))
            $this->outputCached($_fileids[array_rand($_fileids)], $version_);
        exit();
    }

    /**
     * output specific from local cache
     * @param string $fileid_
     * @param string $version_
     * @return never
     */
    public function outputCached(string $fileid_, string $version_ = 'use'): never
    {
        $_filename = $this->_cache_path . $fileid_ . '/' . $version_ . '.jpg';
        $_data = file_get_contents($_filename);
        header('Content-Type: ' . mime_content_type($_filename));
        echo $_data;
        exit();
    }

    /**
     * delete an image from local storage
     * @param string $fileid_ 
     * @param bool $blacklist_
     * @return bool
     */
    public function deleteCached(string $fileid_, bool $blacklist_ = true): bool
    {
        if (file_exists($this->_cache_path . $fileid_))
            $this->removeDirectory($this->_cache_path . $fileid_);
        if ($blacklist_ === true)
            $this->addToBlacklist($fileid_);
        return true;
    }

    /**
     * output file from websource
     * @param string $fileUrl_
     * @return never
     */
    public function output(string $fileUrl_): never
    {
        $_data = file_get_contents($fileUrl_);
        header('Content-Type: ' . mime_content_type($fileUrl_));
        echo $_data;
        exit();
    }

    /**
     * crawl a file from web to cache
     * @param string $searchterm_
     * @return false|string
     */
    public function crawl(string $imageUrl_, bool $permanent_ = false): false|string
    {
        $_data = file_get_contents($imageUrl_);
        if ($permanent_ === true) {
            $_file_id = md5($_data);
            if (!$this->addCached($_file_id, $_data)) {
                unset($_data);
                return false;
            }
            return $_file_id;
        }
        return $_data;
    }

    /**
     * get ressource urls
     * @param string $searchterm_
     * @return false|array
     */
    public function queryRessourceUrls(string $searchterm_): false|array
    {
        return $this->_provider->query(
            $this->_provider->url('search') . rawurlencode($searchterm_),
            $this->_provider->expression('results')
        );
    }

    /**
     * get file urls
     * @param string $url_
     * @return false|array
     */
    public function queryFileUrls(string $ressourceUrl_): false|array
    {
        return $this->_provider->query(
            $ressourceUrl_,
            $this->_provider->expression('images'),
            $this->_provider->url('base')
        );
    }

    /**
     * get local file id list
     * @param ?int $count_
     * @param ?int $offset_
     * @return array
     */
    public function listCached(?int $count_ = null, ?int $offset_ = null): array
    {
        $_result = glob($this->_cache_path . '*', GLOB_ONLYDIR);
        // remove path name, only keep directory name
        array_walk($_result, function (&$item_, $key_) {
            $item_ = explode('/', $item_);
            $item_ = array_pop($item_);
        });
        if ($offset_ !== null)
            for (; $offset_--; array_shift($_result));
        if ($count_ !== null)
            $_result = array_slice($_result, 0, $count_);
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
            if ($file_->isDir())
                rmdir($file_->getPathname());
            else unlink($file_->getPathname());
        }
        // remove directory itself
        return rmdir($directory_);
    }

    /**
     * add file to cache
     * @param string $fileid_
     * @param string $data_
     * @return bool
     */
    private function addCached(string $fileid_, string $data_): bool
    {
        if (file_exists($this->_cache_path . $fileid_) || !mkdir($this->_cache_path . $fileid_, 0777, true) || $this->isBlacklisted($fileid_))
            return false;

        file_put_contents($this->_cache_path . $fileid_ . '/source.jpg', $data_);
        file_put_contents($this->_cache_path . $fileid_ . '/use.jpg', $data_);
        file_put_contents($this->_cache_path . $fileid_ . '/thumb.jpg', $data_);

        if ($this->resizeImage($this->_cache_path . $fileid_ . '/use.jpg', 1600, 1200) && $this->resizeImage($this->_cache_path . $fileid_ . '/thumb.jpg', 320, 240))
            return true;
        return false;
    }

    /**
     * get image meta data
     * @param string $fileid_
     * @param string $version_
     * @return array
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
     * add a file name to the blacklist
     * @param string $fileid_
     * @return bool
     */
    public function addToBlacklist(string $fileid_): bool
    {
        if (!$this->isBlacklisted($fileid_) && file_put_contents($this->_cache_path . 'blacklist.txt', $fileid_ . "\n", FILE_APPEND))
            return true;
        return false;
    }

    /**
     * check if a file is blacklisted
     * @param string $fileid_
     * @return bool
     */
    public function isBlacklisted(string $fileid_): bool
    {
        if (exec('grep ' . escapeshellarg($fileid_) . ' ' . $this->_cache_path . 'blacklist.txt'))
            return true;
        return false;
    }

    /**
     * resize jpeg image and save to same filename
     * @param string $filename_
     * @param int $targetWidth_
     * @param int $targetHeight_
     * @param bool $crop_
     * @return bool
     */
    private function resizeImage(string $filename_, int $width_target_, int $height_target_, bool $crop_ = false): bool
    {
        $_meta = getimagesize($filename_);
        $_width = $_meta[0];
        $_height = $_meta[1];

        $_r = $_width / $_height;

        if ($crop_ === true) {
            if ($_width > $_height)
                $_width = ceil($_width - ($_width * abs($_r - $width_target_ / $height_target_)));
            else $_height = ceil($_height - ($_height * abs($_r - $width_target_ / $height_target_)));
            $_width_new = $width_target_;
            $_height_new = $height_target_;
        } else {
            if ($width_target_ / $height_target_ > $_r) {
                $_width_new = $height_target_ * $_r;
                $_height_new = $height_target_;
            } else {
                $_height_new = $width_target_ / $_r;
                $_width_new = $width_target_;
            }
        }

        switch ($_meta['mime']) {
            case 'image/jpeg':
                $_src = imagecreatefromjpeg($filename_);
                break;

            case 'image/png':
                $_src = imagecreatefrompng($filename_);
                break;
        }

        $_dst = imagecreatetruecolor((int)$_width_new, (int)$_height_new);
        imagecopyresampled($_dst, $_src, 0, 0, 0, 0, (int)$_width_new, (int)$_height_new, $_width, $_height);
        return imagejpeg($_dst, $filename_);
    }
}
