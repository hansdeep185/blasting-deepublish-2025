<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'blast_schedule_id',
        'contact_id',
        'phone_number',
        'message_content',
        'status',
        'waha_message_id',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function blastSchedule()
    {
        return $this->belongsTo(BlastSchedule::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function markAsSent(string $wahaMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'waha_message_id' => $wahaMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}