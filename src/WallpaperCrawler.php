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
        return count(glob($this->_images_local_path . '*', GLOB_ONLYDIR));
    }

    /**
     * output picture from local storage
     * @return never
     */
    public function outputRandomWallpaperFromLocal(): never
    {
        $_files = glob($this->_images_local_path . '*', GLOB_ONLYDIR);
        $_picture_data = '';
        $_picture_info = ['mime' => 'image/jpeg'];
        if (count($_files)) {
            $_picture_file = $_files[rand(0, count($_files) - 1)] . '/use.jpg';
            $_picture_info = getimagesize($_picture_file);
            $_picture_data = file_get_contents($_picture_file);
        }
        header('Content-Type: ' . $_picture_info['mime']);
        echo $_picture_data;
        exit();
    }

    /**
     * add a file name to the blacklist
     * @param string $filename_
     */
    private function addToBlacklist(string $filename_): bool
    {
        if ($this->isBlacklisted($filename_)) {
            return false;
        }
        //$_filename = $this->getFilenameByUrl($filename_);
        if (file_put_contents($this->_images_local_path . 'blacklist.txt', $filename_ . "\n", FILE_APPEND)) {
            return true;
        }
        return false;
    }

    /**
     * check if a filename is blacklisted
     * @param string $filename_
     */
    private function isBlacklisted(string $filename_): bool
    {
        if (exec('grep ' . escapeshellarg($this->getFilenameByUrl($filename_)) . ' ' . $this->_images_local_path . 'blacklist.txt')) {
            return true;
        }
        return false;
    }

    /**
     * delete an image from local storage
     * @param string $filename_ without images path
     * @param bool $blacklist_
     */
    public function deleteFromLocal(string $filename_, bool $blacklist_ = true): bool
    {
        if (file_exists($this->_images_local_path . $filename_)) {
            $this->removeDirectory($this->_images_local_path . $filename_);
        }
        if ($blacklist_ === true) {
            $this->addToBlacklist($filename_);
        }
        return true;
    }

    /**
     * remove a directory and all pictures inside
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
     * output an image from the local storage
     * @param string $filename_
     */
    public function outputWallpaperFromLocal(string $filename_, string $version_ = 'use'): never
    {
        //$_files = glob($this->_images_local_path . '*.jpg');
        $_picture_data = '';
        $_picture_info = ['mime' => 'image/jpeg'];
        $_picture_info = getimagesize($this->_images_local_path . $filename_ . '/' . $version_ . '.jpg');
        $_picture_data = file_get_contents($this->_images_local_path . $filename_ . '/' . $version_ . '.jpg');
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
        $_picture_data_md5 = md5($_picture_data);
        // get filename only
        //$_filename = $this->getFilenameByUrl($_picture_url);
        //$_filename = $_picture_data_md5 . '.jpg';
        // if filename is blacklisted, output image from local
        if ($this->isBlacklisted($_picture_data_md5)) {
            $this->outputRandomWallpaperFromLocal();
            exit();
        }
        // save image to file
        if ($saveToFile_ === true) {
            mkdir($this->_images_local_path . $_picture_data_md5);
            $this->saveImageToFile($_picture_data_md5 . '/source.jpg', $_picture_data);
            $this->saveImageToFile($_picture_data_md5 . '/use.jpg', $_picture_data);
            $this->saveImageToFile($_picture_data_md5 . '/thumb.jpg', $_picture_data);
            $this->resizeImage($_picture_data_md5 . '/use.jpg', 1600, 1200);
            $this->resizeImage($_picture_data_md5 . '/thumb.jpg', 320, 240);
            $_picture_data = file_get_contents($this->_images_local_path . $_picture_data_md5 . '/use.jpg');
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

    private function resizeImage(string $filename_, int $width_, int $height_): bool
    {
        return $this->resizeJpeg($this->_images_local_path . $filename_, $width_, $height_);
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
