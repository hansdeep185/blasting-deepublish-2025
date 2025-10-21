<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'contact_count',
    ];

    protected $casts = [
        'contact_count' => 'integer',
    ];

    /**
     * Relationship: Group belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Group has many Contacts
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_group')
            ->withTimestamps();
    }

    /**
     * Update contact count
     */
    public function updateContactCount(): void
    {
        $this->update([
            'contact_count' => $this->contacts()->count()
        ]);
    }

    /**
     * Scope: Only user's groups
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}