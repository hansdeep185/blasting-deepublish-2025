<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function blastSchedules()
    {
        return $this->hasMany(BlastSchedule::class);
    }

    public function updateContactCount(): void
    {
        $this->update(['total_contacts' => $this->contacts()->count()]);
    }
}