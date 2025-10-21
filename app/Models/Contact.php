<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_list_id',
        'phone_number',
        'name',
        'email',
        'custom_fields',
        'is_blacklisted',
        'opted_out_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'is_blacklisted' => 'boolean',
            'opted_out_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Contact belongs to ContactList
     */
    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    /**
     * Relationship: Contact has many SentMessages
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(SentMessage::class);
    }

    /**
     * Relationship: Contact belongs to many Tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContactTag::class, 'contact_contact_tag')
            ->withTimestamps();
    }

    /**
     * Check if contact is blacklisted
     */
    public function isBlacklisted(): bool
    {
        return $this->is_blacklisted;
    }

    /**
     * Check if contact has opted out
     */
    public function hasOptedOut(): bool
    {
        return $this->opted_out_at !== null;
    }

    /**
     * Check if contact can receive messages
     */
    public function canReceiveMessage(): bool
    {
        return !$this->isBlacklisted() && !$this->hasOptedOut();
    }

    /**
     * Scope: Only contacts that can receive messages
     */
    public function scopeCanReceive($query)
    {
        return $query->where('is_blacklisted', false)
            ->whereNull('opted_out_at');
    }

    /**
     * Scope: Filter by tag
     */
    public function scopeWithTag($query, $tagId)
    {
        return $query->whereHas('tags', function($q) use ($tagId) {
            $q->where('contact_tags.id', $tagId);
        });
    }

    /**
     * Get formatted phone number for WhatsApp
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
        
        // Add country code if not exists (default Indonesia)
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . ltrim($phone, '0');
        }
        
        return $phone;
    }

    /**
     * Blacklist this contact
     */
    public function blacklist(): void
    {
        $this->update(['is_blacklisted' => true]);
    }

    /**
     * Remove from blacklist
     */
    public function unblacklist(): void
    {
        $this->update(['is_blacklisted' => false]);
    }

    /**
     * Mark as opted out
     */
    public function optOut(): void
    {
        $this->update(['opted_out_at' => now()]);
    }

    /**
     * Mark as opted in
     */
    public function optIn(): void
    {
        $this->update(['opted_out_at' => null]);
    }
}