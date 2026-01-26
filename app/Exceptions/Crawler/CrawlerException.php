<?php

namespace App\Exceptions\Crawler;

use App\Exceptions\CmsException;

class CrawlerException extends CmsException
{
    protected int $httpStatusCode = 502;

    public function getUserMessage(): string
    {
        return 'Crawler failed to fetch the source.';
    }
}
