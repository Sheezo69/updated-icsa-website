<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_hash', 'page_path', 'page_title', 'route_name', 'page_type',
        'referrer_host', 'utm_source', 'utm_medium', 'utm_campaign',
        'device_type', 'browser', 'operating_system', 'country_code',
        'is_signed_in', 'visited_at',
    ];

    protected $casts = [
        'is_signed_in' => 'boolean',
        'visited_at' => 'datetime',
    ];
}
