<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'contact_phone',
        'contact_name',
        'last_message',
        'last_message_at',
        'unread_count',
        'is_archived',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    /**
     * Get or create chat for a contact
     */
    public static function getOrCreate($accountId, $contactPhone, $contactName = null)
    {
        return static::firstOrCreate(
            [
                'account_id' => $accountId,
                'contact_phone' => $contactPhone,
            ],
            [
                'contact_name' => $contactName,
            ]
        );
    }

    /**
     * Update last message info
     */
    public function updateLastMessage(ChatMessage $message): void
    {
        $this->update([
            'last_message' => $message->message_content,
            'last_message_at' => $message->created_at,
        ]);
    }

    /**
     * Increment unread count
     */
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    /**
     * Mark as read (reset unread count)
     */
    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * Archive chat
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive chat
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeWithUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): string
    {
        // Format: +62 812-3456-7890
        $phone = $this->contact_phone;
        if (strlen($phone) >= 10) {
            return '+' . substr($phone, 0, 2) . ' ' . 
                   substr($phone, 2, 3) . '-' . 
                   substr($phone, 5, 4) . '-' . 
                   substr($phone, 9);
        }
        return $phone;
    }

    /**
     * Get display name (name or phone)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->contact_name ?? $this->contact_phone;
    }

    /**
     * Get initials for avatar
     */
    public function getInitialsAttribute(): string
    {
        if ($this->contact_name) {
            $words = explode(' ', $this->contact_name);
            if (count($words) >= 2) {
                return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
            }
            return strtoupper(substr($this->contact_name, 0, 2));
        }
        return strtoupper(substr($this->contact_phone, -2));
    }
}