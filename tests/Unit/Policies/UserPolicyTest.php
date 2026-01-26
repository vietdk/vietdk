<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_admin_can_manage_users(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $target = new User(['role' => User::ROLE_WRITER]);

        $policy = new UserPolicy();

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $target));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $target));
        $this->assertTrue($policy->delete($admin, $target));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $editor = new User(['role' => User::ROLE_EDITOR]);
        $target = new User(['role' => User::ROLE_WRITER]);

        $policy = new UserPolicy();

        $this->assertFalse($policy->viewAny($editor));
        $this->assertFalse($policy->view($editor, $target));
        $this->assertFalse($policy->create($editor));
        $this->assertFalse($policy->update($editor, $target));
        $this->assertFalse($policy->delete($editor, $target));
    }
}
