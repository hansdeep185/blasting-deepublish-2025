<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_encrypted',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Get setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            // Decrypt if encrypted
            if ($setting->is_encrypted && $value) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    return $default;
                }
            }

            // Cast based on type
            return match ($setting->type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'number' => is_numeric($value) ? (float) $value : $default,
                default => $value,
            };
        });
    }

    /**
     * Set setting value
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        // Encrypt if needed
        if ($setting->is_encrypted && $value) {
            $value = Crypt::encryptString($value);
        }

        $setting->update(['value' => $value]);

        // Clear cache
        Cache::forget("setting_{$key}");

        return true;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return self::where('group', $group)
                ->orderBy('order')
                ->get()
                ->mapWithKeys(function ($setting) {
                    $value = $setting->value;

                    // Decrypt if encrypted (for display, show masked)
                    if ($setting->is_encrypted && $value) {
                        // Don't decrypt for display in forms
                        $value = $setting->value;
                    }

                    return [$setting->key => [
                        'value' => $value,
                        'type' => $setting->type,
                        'label' => $setting->label,
                        'description' => $setting->description,
                        'is_encrypted' => $setting->is_encrypted,
                    ]];
                })
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Get decrypted value for display (masked)
     */
    public function getDisplayValue(): string
    {
        if ($this->is_encrypted && $this->value) {
            // Show only last 4 characters for encrypted values
            try {
                $decrypted = Crypt::decryptString($this->value);
                if (strlen($decrypted) > 4) {
                    return str_repeat('*', strlen($decrypted) - 4) . substr($decrypted, -4);
                }
                return str_repeat('*', strlen($decrypted));
            } catch (\Exception $e) {
                return '********';
            }
        }

        return $this->value ?? '';
    }

    /**
     * Get actual decrypted value
     */
    public function getActualValue(): ?string
    {
        if ($this->is_encrypted && $this->value) {
            try {
                return Crypt::decryptString($this->value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->value;
    }
}