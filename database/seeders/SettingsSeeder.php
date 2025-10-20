<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'WhatsApp Blast App',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Application Name',
                'description' => 'Name of the application',
                'is_encrypted' => false,
                'order' => 1,
            ],
            [
                'key' => 'default_message_quota',
                'value' => '10000',
                'type' => 'number',
                'group' => 'general',
                'label' => 'Default Message Quota',
                'description' => 'Default message quota for new users',
                'is_encrypted' => false,
                'order' => 2,
            ],
            [
                'key' => 'default_rate_limit',
                'value' => '3',
                'type' => 'number',
                'group' => 'general',
                'label' => 'Default Rate Limit (seconds)',
                'description' => 'Default delay between messages in seconds',
                'is_encrypted' => false,
                'order' => 3,
            ],
            [
                'key' => 'allow_user_registration',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Allow User Registration',
                'description' => 'Enable or disable new user registration',
                'is_encrypted' => false,
                'order' => 4,
            ],

            // WAHA Settings
            [
                'key' => 'waha_base_url',
                'value' => '',
                'type' => 'url',
                'group' => 'waha',
                'label' => 'WAHA Base URL',
                'description' => 'Base URL for WAHA API (e.g., http://localhost:3000)',
                'is_encrypted' => false,
                'order' => 1,
            ],
            [
                'key' => 'waha_api_key',
                'value' => '',
                'type' => 'password',
                'group' => 'waha',
                'label' => 'WAHA API Key',
                'description' => 'API key for WAHA authentication',
                'is_encrypted' => true,
                'order' => 2,
            ],
            [
                'key' => 'waha_webhook_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'waha',
                'label' => 'Enable WAHA Webhooks',
                'description' => 'Enable incoming message webhooks from WAHA',
                'is_encrypted' => false,
                'order' => 3,
            ],

            // n8n Settings
            [
                'key' => 'n8n_webhook_url',
                'value' => '',
                'type' => 'url',
                'group' => 'n8n',
                'label' => 'n8n Webhook URL',
                'description' => 'Webhook URL for n8n workflow',
                'is_encrypted' => false,
                'order' => 1,
            ],
            [
                'key' => 'n8n_api_key',
                'value' => '',
                'type' => 'password',
                'group' => 'n8n',
                'label' => 'n8n API Key',
                'description' => 'API key for n8n authentication',
                'is_encrypted' => true,
                'order' => 2,
            ],
            [
                'key' => 'n8n_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'n8n',
                'label' => 'Enable n8n Integration',
                'description' => 'Enable AI agent workflow via n8n',
                'is_encrypted' => false,
                'order' => 3,
            ],

            // AI Settings
            [
                'key' => 'ai_provider',
                'value' => 'gemini',
                'type' => 'select',
                'group' => 'ai',
                'label' => 'AI Provider',
                'description' => 'Select AI provider (gemini or openai)',
                'is_encrypted' => false,
                'order' => 1,
            ],
            [
                'key' => 'gemini_api_key',
                'value' => '',
                'type' => 'password',
                'group' => 'ai',
                'label' => 'Gemini API Key',
                'description' => 'API key for Google Gemini',
                'is_encrypted' => true,
                'order' => 2,
            ],
            [
                'key' => 'openai_api_key',
                'value' => '',
                'type' => 'password',
                'group' => 'ai',
                'label' => 'OpenAI API Key',
                'description' => 'API key for OpenAI',
                'is_encrypted' => true,
                'order' => 3,
            ],
            [
                'key' => 'ai_max_tokens',
                'value' => '500',
                'type' => 'number',
                'group' => 'ai',
                'label' => 'Max Tokens',
                'description' => 'Maximum tokens for AI responses',
                'is_encrypted' => false,
                'order' => 4,
            ],
            [
                'key' => 'ai_temperature',
                'value' => '0.7',
                'type' => 'number',
                'group' => 'ai',
                'label' => 'AI Temperature',
                'description' => 'Temperature for AI responses (0-1)',
                'is_encrypted' => false,
                'order' => 5,
            ],

            // Email Settings (for future)
            [
                'key' => 'smtp_host',
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Host',
                'description' => 'SMTP server host',
                'is_encrypted' => false,
                'order' => 1,
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'type' => 'number',
                'group' => 'email',
                'label' => 'SMTP Port',
                'description' => 'SMTP server port',
                'is_encrypted' => false,
                'order' => 2,
            ],
            [
                'key' => 'smtp_username',
                'value' => '',
                'type' => 'text',
                'group' => 'email',
                'label' => 'SMTP Username',
                'description' => 'SMTP authentication username',
                'is_encrypted' => false,
                'order' => 3,
            ],
            [
                'key' => 'smtp_password',
                'value' => '',
                'type' => 'password',
                'group' => 'email',
                'label' => 'SMTP Password',
                'description' => 'SMTP authentication password',
                'is_encrypted' => true,
                'order' => 4,
            ],
            [
                'key' => 'smtp_encryption',
                'value' => 'tls',
                'type' => 'select',
                'group' => 'email',
                'label' => 'SMTP Encryption',
                'description' => 'SMTP encryption method (tls or ssl)',
                'is_encrypted' => false,
                'order' => 5,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✅ Settings seeded successfully!');
    }
}