<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        if ($user->role === User::ROLE_WRITER) {
            return $article->author_id === $user->id
                || $article->assigned_to === $user->id;
        }

        return $user->isEditor();
    }

    public function approve(User $user, Article $article): bool
    {
        return $user->isEditor() && $article->canBeApproved();
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->isEditor() && $article->canBePublished();
    }
}
