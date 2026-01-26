<?php

namespace App\Policies;

use App\Models\ExportTemplate;
use App\Models\User;

class ExportTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, ExportTemplate $exportTemplate): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, ExportTemplate $exportTemplate): bool
    {
        return $user->isEditor();
    }

    public function delete(User $user, ExportTemplate $exportTemplate): bool
    {
        return $user->isEditor();
    }
}
