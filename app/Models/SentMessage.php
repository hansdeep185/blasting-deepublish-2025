<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    use HasFactory;

    // Support both table names
    protected $table = 'blast_messages';

    protected $fillable = [
        'blast_schedule_id',
        'contact_id',
        'phone_number',
        'recipient_name',
        'message_content',
        'status',
        'waha_message_id',
        'waha_response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'waha_response' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function blastSchedule()
    {
        return $this->belongsTo(BlastSchedule::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Mark as sent
     */
    public function markAsSent(string $wahaMessageId = null, array $wahaResponse = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'waha_message_id' => $wahaMessageId,
            'waha_response' => $wahaResponse,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage, array $wahaResponse = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'waha_response' => $wahaResponse,
        ]);
    }

    /**
     * Mark as queued
     */
    public function markAsQueued(): void
    {
        $this->update([
            'status' => 'queued',
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
        ]);
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'queued' => 'info',
            'sent' => 'success',
            'delivered' => 'success',
            'read' => 'primary',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'pending' => 'clock',
            'queued' => 'hourglass-split',
            'sent' => 'check',
            'delivered' => 'check-all',
            'read' => 'check-all text-primary',
            'failed' => 'x-circle',
            default => 'circle',
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }
}