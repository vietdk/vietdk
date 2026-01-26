<?php

namespace App\Exceptions;

use Exception;

abstract class CmsException extends Exception
{
    protected int $httpStatusCode = 500;

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    abstract public function getUserMessage(): string;
}
