<?php

use App\Models\Setting;

if (!function_exists('settings')) {
    /**
     * Get setting value by key
     */
    function settings(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('waha_config')) {
    /**
     * Get WAHA configuration
     */
    function waha_config(): array
    {
        return [
            'base_url' => settings('waha_base_url'),
            'api_key' => settings('waha_api_key'),
            'webhook_enabled' => settings('waha_webhook_enabled', true),
        ];
    }
}

if (!function_exists('n8n_config')) {
    /**
     * Get n8n configuration
     */
    function n8n_config(): array
    {
        return [
            'webhook_url' => settings('n8n_webhook_url'),
            'api_key' => settings('n8n_api_key'),
            'enabled' => settings('n8n_enabled', false),
        ];
    }
}

if (!function_exists('ai_config')) {
    /**
     * Get AI configuration
     */
    function ai_config(): array
    {
        return [
            'provider' => settings('ai_provider', 'gemini'),
            'gemini_key' => settings('gemini_api_key'),
            'openai_key' => settings('openai_api_key'),
            'max_tokens' => settings('ai_max_tokens', 500),
            'temperature' => settings('ai_temperature', 0.7),
        ];
    }
}