<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlastSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'template_id',
        'contact_list_id',
        'name',
        'description', // NEW
        'target_type', // NEW
        'target_ids', // NEW
        'schedule_type', // NEW
        'scheduled_at',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'pending_count', // NEW
        'rate_limit_delay',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'target_ids' => 'array', // NEW
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'pending_count' => 'integer', // NEW
            'rate_limit_delay' => 'integer',
        ];
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function contactList()
    {
        return $this->belongsTo(ContactList::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(SentMessage::class);
    }

    // Alias for consistency
    public function messages()
    {
        return $this->hasMany(SentMessage::class);
    }

    /**
     * Get target contacts based on target_type and target_ids
     * NEW METHOD
     */
    public function getTargetContacts()
    {
        $query = Contact::query();

        switch ($this->target_type) {
            case 'all':
                // All contacts for this user
                $query->whereHas('contactList', function ($q) {
                    $q->where('user_id', $this->user_id);
                });
                break;

            case 'contact_list':
                // Specific contact lists
                if ($this->target_ids && is_array($this->target_ids)) {
                    $query->whereIn('contact_list_id', $this->target_ids);
                } elseif ($this->contact_list_id) {
                    // Fallback to single contact_list_id
                    $query->where('contact_list_id', $this->contact_list_id);
                }
                break;

            case 'contact_group':
                // Contacts in specific groups (tags)
                if ($this->target_ids && is_array($this->target_ids)) {
                    $query->whereHas('tags', function ($q) {
                        $q->whereIn('contact_tags.id', $this->target_ids);
                    });
                }
                break;

            case 'selected_contacts':
                // Specific selected contacts
                if ($this->target_ids && is_array($this->target_ids)) {
                    $query->whereIn('id', $this->target_ids);
                }
                break;
        }

        return $query->where('is_active', true)->get();
    }

    /**
     * Calculate statistics
     * NEW METHOD
     */
    public function calculateStats(): void
    {
        $this->update([
            'total_recipients' => $this->messages()->count(),
            'sent_count' => $this->messages()->where('status', 'sent')->count(),
            'failed_count' => $this->messages()->where('status', 'failed')->count(),
            'pending_count' => $this->messages()->whereIn('status', ['pending', 'queued'])->count(),
        ]);
    }

    /**
     * Status check methods
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'scheduled', 'processing']);
    }

    /**
     * Status update methods
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * Counter methods
     */
    public function incrementSent(): void
    {
        $this->increment('sent_count');
        $this->decrement('pending_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
        $this->decrement('pending_count');
    }

    /**
     * Attributes
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        
        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    public function getProgressAttribute(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        $processed = $this->sent_count + $this->failed_count;
        return round(($processed / $this->total_recipients) * 100, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'scheduled' => 'info',
            'processing' => 'primary',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}