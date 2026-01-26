<?php

namespace Tests\Unit\Policies;

use App\Models\Tag;
use App\Models\User;
use App\Policies\TagPolicy;
use Tests\TestCase;

class TagPolicyTest extends TestCase
{
    public function test_editor_can_manage_tags(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $tag = new Tag();

        $policy = new TagPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $tag));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $tag));
        $this->assertTrue($policy->delete($user, $tag));
    }

    public function test_writer_cannot_manage_tags(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $tag = new Tag();

        $policy = new TagPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $tag));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $tag));
        $this->assertFalse($policy->delete($user, $tag));
    }
}
