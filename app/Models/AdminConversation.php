<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminConversation extends Model
{
    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'participant_one_name',
        'participant_two_name',
        'last_message_at',
    ];

    protected $casts = [
        'participant_one_id' => 'integer',
        'participant_two_id' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AdminMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(AdminMessage::class, 'conversation_id')->latestOfMany();
    }

    public function scopeForAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where(function (Builder $builder) use ($adminId): void {
            $builder->where('participant_one_id', $adminId)
                ->orWhere('participant_two_id', $adminId);
        });
    }

    public function includes(int $adminId): bool
    {
        return $this->participant_one_id === $adminId || $this->participant_two_id === $adminId;
    }

    public function otherParticipantId(int $adminId): int
    {
        return $this->participant_one_id === $adminId
            ? $this->participant_two_id
            : $this->participant_one_id;
    }

    public function otherParticipantName(int $adminId): string
    {
        return $this->participant_one_id === $adminId
            ? $this->participant_two_name
            : $this->participant_one_name;
    }
}
