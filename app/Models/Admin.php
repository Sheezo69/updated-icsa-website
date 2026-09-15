<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Admin extends Model
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    public const TIMEZONES = [
        'Asia/Kuwait' => '(UTC+03:00) Kuwait',
        'Asia/Riyadh' => '(UTC+03:00) Riyadh',
        'Asia/Dubai' => '(UTC+04:00) Dubai',
        'UTC' => '(UTC+00:00) Universal Time',
    ];

    public const LANGUAGES = [
        'en' => 'English',
    ];

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'avatar_path',
        'last_login',
        'login_attempts',
        'locked_until',
        'role',
        'can_manage_courses',
        'can_manage_media',
        'notify_email',
        'notify_inquiries',
        'notify_messages',
        'timezone',
        'language',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
        'login_attempts' => 'integer',
        'can_manage_courses' => 'boolean',
        'can_manage_media' => 'boolean',
        'notify_email' => 'boolean',
        'notify_inquiries' => 'boolean',
        'notify_messages' => 'boolean',
    ];

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canAccess(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return match ($permission) {
            'courses' => $this->can_manage_courses,
            'media' => $this->can_manage_media,
            default => false,
        };
    }

    public function assignedInquiries(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'assigned_to');
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
