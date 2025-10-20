<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'question',
        'answer',
        'content',
        'type',
        'file_path',
        'status',
        'is_embedded',
        'vector_id',
    ];

    protected function casts(): array
    {
        return [
            'is_embedded' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(string $vectorId = null): void
    {
        $this->update([
            'status' => 'completed',
            'is_embedded' => true,
            'vector_id' => $vectorId,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
