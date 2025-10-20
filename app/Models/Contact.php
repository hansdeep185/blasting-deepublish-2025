<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function contactList()
    {
        return $this->belongsTo(ContactList::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(SentMessage::class);
    }

    public function isBlacklisted(): bool
    {
        return $this->is_blacklisted;
    }

    public function hasOptedOut(): bool
    {
        return $this->opted_out_at !== null;
    }

    public function canReceiveMessage(): bool
    {
        return !$this->isBlacklisted() && !$this->hasOptedOut();
    }
}