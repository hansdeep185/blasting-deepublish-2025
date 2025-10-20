<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'role',
        'message_quota',
        'message_used',
        'is_active',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'message_quota' => 'integer',
            'message_used' => 'integer',
        ];
    }

    // Relationships
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function contactLists()
    {
        return $this->hasMany(ContactList::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function blastSchedules()
    {
        return $this->hasMany(BlastSchedule::class);
    }

    public function trainingData()
    {
        return $this->hasMany(TrainingData::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function hasQuotaAvailable(int $amount = 1): bool
    {
        return ($this->message_quota - $this->message_used) >= $amount;
    }

    public function decrementQuota(int $amount = 1): void
    {
        $this->increment('message_used', $amount);
    }

    public function resetQuota(): void
    {
        $this->update(['message_used' => 0]);
    }
}