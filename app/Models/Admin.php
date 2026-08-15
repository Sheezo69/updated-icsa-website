<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Admin extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'last_login',
        'login_attempts',
        'locked_until',
        'role',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
        'login_attempts' => 'integer',
    ];

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isLocked(): bool
    {
        return $this->locked_until instanceof CarbonInterface && $this->locked_until->isFuture();
    }

    public function recordFailedLogin(int $maxAttempts = 5, int $lockoutSeconds = 900): void
    {
        $attempts = ((int) $this->login_attempts) + 1;

        $this->forceFill([
            'login_attempts' => $attempts,
            'locked_until' => $attempts >= $maxAttempts ? Carbon::now()->addSeconds($lockoutSeconds) : null,
        ])->save();
    }

    public function clearLoginFailures(): void
    {
        $this->forceFill([
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }
}
