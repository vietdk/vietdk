<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    const ROLE_WRITER = 'writer';
    const ROLE_EDITOR = 'editor';
    const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail()
            && in_array($this->role, [self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_WRITER], true);
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR || $this->isAdmin();
    }

    public function isWriter(): bool
    {
        return in_array($this->role, [self::ROLE_WRITER, self::ROLE_EDITOR, self::ROLE_ADMIN]);
    }

    public function canApproveArticles(): bool
    {
        return $this->isEditor();
    }

    public function canPublishArticles(): bool
    {
        return $this->isEditor();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public static function getRoles(): array
    {
        return [
            self::ROLE_WRITER => 'Writer',
            self::ROLE_EDITOR => 'Editor',
            self::ROLE_ADMIN => 'Admin',
        ];
    }
}
