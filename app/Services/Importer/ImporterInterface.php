<?php

namespace App\Services\Importer;

use Illuminate\Support\Collection;

interface ImporterInterface
{
    public function parse(string $filePath): Collection;

    public function supports(string $fileType): bool;
}
