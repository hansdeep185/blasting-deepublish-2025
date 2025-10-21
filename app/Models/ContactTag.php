<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_list_id',
        'name',
        'color',
        'description',
    ];

    /**
     * Relationship: Tag belongs to ContactList
     */
    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    /**
     * Relationship: Tag has many Contacts (many-to-many)
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_contact_tag')
            ->withTimestamps();
    }

    /**
     * Get contact count for this tag
     */
    public function getContactCountAttribute(): int
    {
        return $this->contacts()->count();
    }
}