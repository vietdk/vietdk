<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'html_body',
        'text_body',
        'filters',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'filters' => 'array',
    ];

    public function bulletinExports(): HasMany
    {
        return $this->hasMany(BulletinExport::class, 'template_id');
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::first();
    }

    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
