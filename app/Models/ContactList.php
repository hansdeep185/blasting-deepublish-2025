<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Ganti HasManyThrough dengan Builder
use Illuminate\Database\Eloquent\Builder;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'total_contacts',
    ];

    protected function casts(): array
    {
        return [
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
     * INI ADALAH RELASI YANG DIPERBAIKI
     * Mendapatkan query builder untuk semua tag unik dari kontak di dalam daftar ini.
     */
    public function tags(): Builder
    {
        // 1. Dapatkan semua ID kontak yang ada di dalam daftar kontak ini.
        $contactIds = $this->contacts()->pluck('id');

        // 2. Buat query pada model ContactTag
        //    dan filter hanya tag yang terhubung dengan ID kontak di atas.
        return ContactTag::query()
            ->whereHas('contacts', function ($query) use ($contactIds) {
                $query->whereIn('contacts.id', $contactIds);
            })->distinct();
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
        // Sekarang kita gunakan relasi yang benar
        return $this->tags()->count();
    }
}

