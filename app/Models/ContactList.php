<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'total_contacts',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'total_contacts' => 'integer',
        ];
    }

    /**
     * Relationship: ContactList belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: ContactList has many Contacts
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Relationship: ContactList has many BlastSchedules
     */
    public function blastSchedules(): HasMany
    {
        return $this->hasMany(BlastSchedule::class);
    }

    /**
     * Relationship: ContactList has many ContactTags
     */
    public function contactTags(): HasMany
    {
        return $this->hasMany(ContactTag::class);
    }

    /**
     * Update contact count
     */
    public function updateContactCount(): void
    {
        $this->update(['total_contacts' => $this->contacts()->count()]);
    }

    /**
     * Get tags count - uses relationship
     */
    public function getTagsCountAttribute(): int
    {
        return $this->contactTags()->count();
    }
}