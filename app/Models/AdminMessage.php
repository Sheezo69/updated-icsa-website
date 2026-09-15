<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_name',
        'body',
        'read_at',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'sender_id' => 'integer',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AdminConversation::class, 'conversation_id');
    }
}
