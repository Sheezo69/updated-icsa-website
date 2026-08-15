<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_ARCHIVED = 'archived';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'course_interest',
        'subject',
        'message',
        'status',
        'admin_notes',
        'replied_at',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'replied_at' => 'datetime',
    ];
}
