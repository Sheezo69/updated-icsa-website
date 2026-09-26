<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'assigned_to',
        'form_type',
        'analytics_visitor_hash',
        'lead_score',
        'pipeline_stage',
        'pipeline_moved_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'replied_at' => 'datetime',
        'lead_score' => 'integer',
        'pipeline_moved_at' => 'datetime',
    ];

    public function emailAttempts(): HasMany
    {
        return $this->hasMany(InquiryEmailAttempt::class)->latest('id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }
}
