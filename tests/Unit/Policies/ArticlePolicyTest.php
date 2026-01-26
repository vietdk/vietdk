<?php

namespace Tests\Unit\Policies;

use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    public function test_writer_can_view_own_article(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $user->id = 1;
        $article = new Article(['author_id' => 1, 'assigned_to' => null]);

        $policy = new ArticlePolicy();

        $this->assertTrue($policy->view($user, $article));
    }

    public function test_writer_can_view_assigned_article(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $user->id = 2;
        $article = new Article(['author_id' => 1, 'assigned_to' => 2]);

        $policy = new ArticlePolicy();

        $this->assertTrue($policy->view($user, $article));
    }

    public function test_writer_cannot_view_unassigned_article(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $user->id = 3;
        $article = new Article(['author_id' => 1, 'assigned_to' => 2]);

        $policy = new ArticlePolicy();

        $this->assertFalse($policy->view($user, $article));
    }

    public function test_editor_can_view_any_article(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $article = new Article(['author_id' => 99, 'assigned_to' => 98]);

        $policy = new ArticlePolicy();

        $this->assertTrue($policy->view($user, $article));
    }

    public function test_editor_can_approve_pending_review_article(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $article = new Article(['status' => Article::STATUS_PENDING_REVIEW]);

        $policy = new ArticlePolicy();

        $this->assertTrue($policy->approve($user, $article));
    }

    public function test_writer_cannot_approve_article(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $article = new Article(['status' => Article::STATUS_PENDING_REVIEW]);

        $policy = new ArticlePolicy();

        $this->assertFalse($policy->approve($user, $article));
    }

    public function test_editor_can_publish_approved_article(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $article = new Article(['status' => Article::STATUS_APPROVED]);

        $policy = new ArticlePolicy();

        $this->assertTrue($policy->publish($user, $article));
    }

    public function test_editor_cannot_publish_unapproved_article(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $article = new Article(['status' => Article::STATUS_DRAFT]);

        $policy = new ArticlePolicy();

        $this->assertFalse($policy->publish($user, $article));
    }
}
