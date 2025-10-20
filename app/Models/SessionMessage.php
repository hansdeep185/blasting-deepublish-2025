<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'chat_id',
        'from_number',
        'to_number',
        'direction',
        'message_content',
        'message_type',
        'media_url',
        'waha_message_id',
        'is_ai_response',
        'message_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'is_ai_response' => 'boolean',
            'message_timestamp' => 'datetime',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }
}