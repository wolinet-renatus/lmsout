<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu Item Model
 *
 * This model represents a menu item.
 *
 * @package App\Models
 * @author Renatus Bernardo
 */
class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route_name',
        'route_parameters',
        'target',
        'is_active',
        'order',
    ];

    protected $casts = [
        'route_parameters' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['resolved_url'];

    /**
     * Eloquent belongsTo relationship for Menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Eloquent belongsTo relationship for Parent.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Eloquent hasMany relationship for Children.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('order');
    }

    /**
     * Query scope to filter records by Active.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor for computed Resolved url attribute.
     */
    public function getResolvedUrlAttribute(): string
    {
        if ($this->url) {
            return resolve_app_url($this->url);
        }

        if ($this->route_name) {
            try {
                return route($this->route_name, $this->route_parameters ?? []);
            } catch (\Throwable $exception) {
                return '#';
            }
        }

        return '#';
    }
}
