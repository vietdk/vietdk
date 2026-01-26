<?php

namespace App\Exceptions\Template;

use App\Exceptions\CmsException;

class TemplateValidationException extends CmsException
{
    protected int $httpStatusCode = 422;

    public function getUserMessage(): string
    {
        return 'Template validation failed.';
    }
}
