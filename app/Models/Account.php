<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_name',
        'phone_number',
        'status',
        'qr_code',
        'waha_session_id',
        'ai_agent_active',
        'rate_limit_delay',
    ];

    protected function casts(): array
    {
        return [
            'ai_agent_active' => 'boolean',
            'rate_limit_delay' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessionMessages()
    {
        return $this->hasMany(SessionMessage::class);
    }

    public function blastSchedules()
    {
        return $this->hasMany(BlastSchedule::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDisconnected(): bool
    {
        return $this->status === 'disconnected';
    }

    public function markAsConnected(string $phoneNumber = null): void
    {
        $this->update([
            'status' => 'connected',
            'phone_number' => $phoneNumber,
            'qr_code' => null,
        ]);
    }

    public function markAsDisconnected(): void
    {
        $this->update(['status' => 'disconnected']);
    }

    public function toggleAiAgent(): bool
    {
        $this->ai_agent_active = !$this->ai_agent_active;
        $this->save();
        
        return $this->ai_agent_active;
    }
}