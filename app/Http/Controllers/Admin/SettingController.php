<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $groups = [
            'general' => 'General Settings',
            'waha' => 'WAHA Configuration',
            'n8n' => 'n8n Configuration',
            'ai' => 'AI Settings',
            'email' => 'Email Settings',
        ];

        $settings = Setting::orderBy('group')->orderBy('order')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings', 'groups'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updatedSettings = [];

        foreach ($request->settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                $oldValue = $setting->getActualValue();

                // Validate based on type
                if ($setting->type === 'number' && !is_numeric($value)) {
                    continue;
                }

                if ($setting->type === 'url' && $value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return back()->with('error', "Invalid URL for {$setting->label}");
                }

                // Don't update if value is placeholder for encrypted fields
                if ($setting->is_encrypted && $value === '********') {
                    continue;
                }

                Setting::set($key, $value);
                $updatedSettings[] = $setting->label;

                // Log changes (without sensitive data)
                AuditLog::logActivity(
                    action: 'update_setting',
                    description: "Updated setting: {$setting->label}",
                    modelType: Setting::class,
                    modelId: $setting->id,
                    oldValues: $setting->is_encrypted ? ['value' => '***'] : ['value' => $oldValue],
                    newValues: $setting->is_encrypted ? ['value' => '***'] : ['value' => $value]
                );
            }
        }

        // Clear all cache
        Setting::clearCache();

        if (count($updatedSettings) > 0) {
            return back()->with('success', 'Settings updated successfully: ' . implode(', ', $updatedSettings));
        }

        return back()->with('info', 'No settings were changed.');
    }

    /**
     * Test WAHA connection
     */
    public function testWaha()
    {
        try {
            $baseUrl = Setting::get('waha_base_url');
            $apiKey = Setting::get('waha_api_key');

            if (!$baseUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'WAHA Base URL is not configured'
                ]);
            }

            // Simple connection test
            $client = new \GuzzleHttp\Client();
            $response = $client->get($baseUrl . '/api/sessions', [
                'headers' => [
                    'X-Api-Key' => $apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                AuditLog::logActivity(
                    action: 'test_waha_connection',
                    description: 'WAHA connection test successful'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully connected to WAHA!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WAHA'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test n8n connection
     */
    public function testN8n()
    {
        try {
            $webhookUrl = Setting::get('n8n_webhook_url');

            if (!$webhookUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'n8n Webhook URL is not configured'
                ]);
            }

            // Simple ping test
            $client = new \GuzzleHttp\Client();
            $response = $client->post($webhookUrl, [
                'json' => [
                    'test' => true,
                    'message' => 'Connection test from WhatsApp Blast App'
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                AuditLog::logActivity(
                    action: 'test_n8n_connection',
                    description: 'n8n connection test successful'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully connected to n8n!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to n8n'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ]);
        }
    }
}