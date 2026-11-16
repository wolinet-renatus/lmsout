<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Page Model
 *
 * This model represents a custom page.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class CustomPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
    ];

    /**
     * Get route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Query scope to filter records by Slug.
     */
    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}

