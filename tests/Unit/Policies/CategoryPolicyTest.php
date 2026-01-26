<?php

namespace Tests\Unit\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    public function test_editor_can_manage_categories(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR]);
        $category = new Category();

        $policy = new CategoryPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $category));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $category));
        $this->assertTrue($policy->delete($user, $category));
    }

    public function test_writer_cannot_manage_categories(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $category = new Category();

        $policy = new CategoryPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $category));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $category));
        $this->assertFalse($policy->delete($user, $category));
    }
}
