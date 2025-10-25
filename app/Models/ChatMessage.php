<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'account_id',
        'direction',
        'from_phone',
        'to_phone',
        'message_content',
        'message_type',
        'media_url',
        'media_mime_type',
        'media_filename',
        'waha_message_id',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'waha_response',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'waha_response' => 'array'
    ];

    /**
     * Relationships
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if message is incoming
     */
    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    /**
     * Check if message is outgoing
     */
    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    /**
     * Check if message has media
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }

    /**
     * Mark message as sent
     */
    public function markAsSent(?string $wahaMessageId = null, ?array $wahaData = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'waha_message_id' => $wahaMessageId,
            'waha_response' => $wahaData ? json_encode($wahaData) : null,
        ]);
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sent' => 'blue',
            'delivered' => 'green',
            'read' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status icon
     */
    /*
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'sent' => '✓',
            'delivered' => '✓✓',
            'read' => '✓✓',
            'failed' => '✕',
            default => '⏱',
        };
    } */

    /**
     * Get formatted time
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->format('H:i');
    }
}