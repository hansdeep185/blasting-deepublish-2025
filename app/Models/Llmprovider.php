<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LlmProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_name',
        'display_name',
        'description',
        'api_key',
        'api_url',
        'available_models',
        'default_model',
        'price_per_1k_tokens',
        'is_active',
        'last_tested_at',
        'test_error',
    ];

    protected $casts = [
        'available_models' => 'array',
        'price_per_1k_tokens' => 'decimal:6',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    // Accessor untuk encrypt/decrypt API key
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function getModelOptions()
    {
        return collect($this->available_models)->mapWithKeys(function ($model) {
            return [$model => $model];
        })->toArray();
    }

    public function markAsTested($success = true, $error = null)
    {
        $this->update([
            'last_tested_at' => now(),
            'test_error' => $error,
            'is_active' => $success,
        ]);
    }

    // Static helper untuk get all active providers dengan models
    public static function getActiveProvidersWithModels()
    {
        return self::active()->get()->mapWithKeys(function ($provider) {
            $models = [];
            foreach ($provider->available_models as $model) {
                $models["{$provider->provider_name}:{$model}"] = "{$provider->display_name} - {$model}";
            }
            return $models;
        })->toArray();
    }

    // Parse model string (e.g., "openai:gpt-4o" => ['openai', 'gpt-4o'])
    public static function parseModelString($modelString)
    {
        $parts = explode(':', $modelString, 2);
        return [
            'provider' => $parts[0] ?? null,
            'model' => $parts[1] ?? null,
        ];
    }

    // Get provider by model string
    public static function getByModelString($modelString)
    {
        $parsed = self::parseModelString($modelString);
        return self::where('provider_name', $parsed['provider'])->first();
    }
}