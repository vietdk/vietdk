<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tone) {
            if (empty($tone->slug)) {
                $tone->slug = Str::slug($tone->name);
            }
        });

        static::updating(function ($tone) {
            if ($tone->isDirty('name') && !$tone->isDirty('slug')) {
                $tone->slug = Str::slug($tone->name);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
