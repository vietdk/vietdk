<?php

namespace App\Exceptions\Authorization;

use App\Exceptions\CmsException;

class UnauthorizedException extends CmsException
{
    protected int $httpStatusCode = 403;

    public function getUserMessage(): string
    {
        return 'You are not authorized to perform this action.';
    }
}
