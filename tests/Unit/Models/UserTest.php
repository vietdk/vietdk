<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Filament\Panel;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_verified_admin_can_access_panel(): void
    {
        $user = new User(['role' => User::ROLE_ADMIN]);
        $user->email_verified_at = now();
        $panel = $this->createMock(Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_unverified_editor_cannot_access_panel(): void
    {
        $user = new User(['role' => User::ROLE_EDITOR, 'email_verified_at' => null]);
        $panel = $this->createMock(Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_verified_writer_can_access_panel(): void
    {
        $user = new User(['role' => User::ROLE_WRITER]);
        $user->email_verified_at = now();
        $panel = $this->createMock(Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_invalid_role_cannot_access_panel(): void
    {
        $user = new User(['role' => 'guest', 'email_verified_at' => now()]);
        $panel = $this->createMock(Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }
}
