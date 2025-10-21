<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'category',
        'variables',
        'media_type',
        'media_url',
        'description',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Relationship: Template belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Template has many BlastSchedules
     */
    public function blastSchedules(): HasMany
    {
        return $this->hasMany(BlastSchedule::class);
    }

    /**
     * Extract variables from template content
     * Returns array of variable names found in {variable} format
     */
    public function extractVariables(): array
    {
        preg_match_all('/{([^}]+)}/', $this->content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Replace variables in content with actual values
     */
    public function render(array $data): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Get available variable placeholders
     */
    public static function getAvailableVariables(): array
    {
        return [
            'name' => 'Contact Name',
            'phone' => 'Phone Number',
            'email' => 'Email Address',
            'company' => 'Company Name',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'date' => 'Current Date',
            'time' => 'Current Time',
            'custom_field_1' => 'Custom Field 1',
            'custom_field_2' => 'Custom Field 2',
        ];
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope: Only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if template has media
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'marketing' => 'Marketing',
            'notification' => 'Notification',
            'reminder' => 'Reminder',
            'greeting' => 'Greeting',
            default => 'Other',
        };
    }

    /**
     * Get category color
     */
    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'marketing' => 'primary',
            'notification' => 'info',
            'reminder' => 'warning',
            'greeting' => 'success',
            default => 'secondary',
        };
    }
}