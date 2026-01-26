<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->isEditor();
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->isEditor();
    }
}
