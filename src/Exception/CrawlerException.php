<?php

namespace Dduers\ImageCrawler\Exception;

use Exception;
use Throwable;

class CrawlerException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct(string $message_, int $code_ = 0, ?Throwable $previous_ = null)
    {
        parent::__construct($message_, $code_, $previous_);
    }

    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ': [{$this->code}]: ' . $this->message . "\n";
    }
}
