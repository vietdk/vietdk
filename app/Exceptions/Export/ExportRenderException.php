<?php

namespace App\Exceptions\Export;

use App\Exceptions\CmsException;

class ExportRenderException extends CmsException
{
    protected int $httpStatusCode = 500;

    public function getUserMessage(): string
    {
        return 'Unable to render the export file.';
    }
}
