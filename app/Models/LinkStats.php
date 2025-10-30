<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LinkStats extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'link_id',
        'accessed_at',
        'ip_address',
        'user_agent',
    ];

    protected $table = 'link_stats';
}
