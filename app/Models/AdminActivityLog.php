<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'actor_name', 'actor_role', 'action', 'section', 'subject_type',
        'subject_id', 'subject_label', 'description', 'before_values', 'after_values',
        'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function actionLabel(): string
    {
        return Str::headline(Str::after($this->action, '.'));
    }

    public function deviceLabel(): string
    {
        $agent = (string) $this->user_agent;
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return $browser.' on '.$platform;
    }
}
