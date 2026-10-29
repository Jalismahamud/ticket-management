<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function cachedActive()
    {
        return \Illuminate\Support\Facades\Cache::remember('categories_active', 3600, function () {
            return static::active()->select(['id', 'name', 'slug'])->get();
        });
    }

    protected static function booted()
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('categories_active'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('categories_active'));
    }
}
