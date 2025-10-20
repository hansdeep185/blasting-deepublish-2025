<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'placeholders',
        'type',
        'media_url',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
            'usage_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blastSchedules()
    {
        return $this->hasMany(BlastSchedule::class);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function renderContent(array $data): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }

    public function extractPlaceholders(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->content, $matches);
        return $matches[1] ?? [];
    }
}