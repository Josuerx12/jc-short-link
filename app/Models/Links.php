<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Links extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'original_url',
        'short_code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
    ];

    protected $table = 'links';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function linkStats()
    {
        return $this->hasMany(LinkStats::class, 'link_id', 'id');
    }

    protected static function booted(): void
    {
        static::saved(function (Links $link) {
            $original = $link->getOriginal('short_code');
            if ($original && $original !== $link->short_code) {
                Cache::forget("links:short_code:{$original}");
            }

            Cache::forget("links:short_code:{$link->short_code}");
        });

        static::deleted(function (Links $link) {
            Cache::forget("links:short_code:{$link->short_code}");
        });
    }
}
