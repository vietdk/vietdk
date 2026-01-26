<?php

namespace App\Exceptions\Import;

use App\Exceptions\CmsException;

class ImportParseException extends CmsException
{
    protected int $httpStatusCode = 422;

    public function getUserMessage(): string
    {
        return 'Unable to parse the import file.';
    }
}
