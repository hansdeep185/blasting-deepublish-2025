<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WahaApiService
{
    protected Client $client;
    protected ?string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = settings('waha_base_url');
        $this->apiKey = settings('waha_api_key');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Create new WhatsApp session
     */
    public function createSession(string $sessionName): array
    {
        try {
            $response = $this->client->post('/api/sessions', [
                'json' => [
                    'name' => $sessionName,
                    'config' => [
                        'webhooks' => [
                            [
                                'url' => url('/api/waha/webhook'),
                                'events' => ['message', 'session.status'],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA session created', ['session' => $sessionName, 'data' => $data]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA createSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Start session - Required before getting QR code
     */
    public function startSession(string $sessionName): array
    {
        try {
            // Correct endpoint based on docs: POST /api/sessions/{name}/start
            $response = $this->client->post("/api/sessions/{$sessionName}/start");
            
            Log::info('WAHA session started', ['session' => $sessionName]);
            
            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA startSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get QR code for session
     * Session must be in SCAN_QR_CODE status
     */
    public function getQrCode(string $sessionName): ?string
    {
        try {
            // First, check session status
            $statusResult = $this->getSessionStatus($sessionName);
            
            Log::info('WAHA session status check', [
                'session' => $sessionName,
                'status' => $statusResult['status'] ?? 'unknown',
            ]);
            
            // If session is STOPPED, start it first
            if (isset($statusResult['data']['status']) && $statusResult['data']['status'] === 'STOPPED') {
                Log::info('Starting STOPPED session', ['session' => $sessionName]);
                $startResult = $this->startSession($sessionName);
                
                if (!$startResult['success']) {
                    Log::error('Failed to start session', ['session' => $sessionName]);
                    return null;
                }
                
                // Wait for session to reach SCAN_QR_CODE status
                sleep(3);
            }
            
            // Now get QR code - endpoint: GET /api/{session}/auth/qr
            $response = $this->client->get("/api/{$sessionName}/auth/qr");
            $data = json_decode($response->getBody()->getContents(), true);
            
            $base64Data = $data['data'] ?? null;
            $mimetype = $data['mimetype'] ?? 'image/png'; // Ambil mimetype, default ke image/png
            $qrCode = null; // Inisialisasi

            if ($base64Data) {
                // GABUNGKAN MENJADI DATA URL LENGKAP
                $qrCode = "data:{$mimetype};base64,{$base64Data}";

                Log::info('WAHA QR code retrieved', [
                    'session' => $sessionName,
                    'qr_length' => strlen($qrCode), // Panjangnya sekarang termasuk prefix
                    'qr_prefix' => substr($qrCode, 0, 50), // Akan menampilkan "data:image/png;base64,iVBO..."
                ]);
            } else {
                Log::warning('WAHA QR code is null (base64 data not found)', [ // Log warning diganti
                    'session' => $sessionName,
                    'response_data' => $data,
                ]);
            }
            
            return $qrCode; // Mengembalikan data URL lengkap
            
        } catch (GuzzleException $e) {
            $errorBody = '';
            if ($e->hasResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
            }
            
            Log::error('WAHA getQrCode error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
                'response' => $errorBody,
            ]);
            
            return null;
        }
    }

    /**
     * Check session status
     */
    public function getSessionStatus(string $sessionName): array
    {
        try {
            $response = $this->client->get("/api/sessions/{$sessionName}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'status' => $data['status'] ?? 'UNKNOWN',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getSessionStatus error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'status' => 'ERROR',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all sessions
     */
    public function getAllSessions(): array
    {
        try {
            $response = $this->client->get('/api/sessions');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'sessions' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getAllSessions error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'sessions' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send text message
     */
    public function sendMessage(string $sessionName, string $to, string $message): array
    {
        try {
            // Format nomor telepon
            $chatId = $this->formatPhoneNumber($to);
            
            $response = $this->client->post("/api/sendText", [
                'json' => [
                    'session' => $sessionName,
                    'chatId' => $chatId,
                    'text' => $message,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA message sent', [
                'session' => $sessionName,
                'to' => $chatId,
                'message_id' => $data['id'] ?? null,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA sendMessage error', [
                'session' => $sessionName,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send image message
     */
    public function sendImage(string $sessionName, string $to, string $imageUrl, ?string $caption = null): array
    {
        try {
            $chatId = $this->formatPhoneNumber($to);
            
            $response = $this->client->post("/api/sendImage", [
                'json' => [
                    'session' => $sessionName,
                    'chatId' => $chatId,
                    'file' => [
                        'url' => $imageUrl,
                    ],
                    'caption' => $caption,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA sendImage error', [
                'session' => $sessionName,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stop session
     */
    public function stopSession(string $sessionName): array
    {
        try {
            // Endpoint: POST /api/sessions/{name}/stop
            $response = $this->client->post("/api/sessions/{$sessionName}/stop");
            
            Log::info('WAHA session stopped', ['session' => $sessionName]);
            
            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA stopSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restart session
     */
    public function restartSession(string $sessionName): array
    {
        try {
            // Endpoint: POST /api/sessions/{name}/restart
            $response = $this->client->post("/api/sessions/{$sessionName}/restart");
            
            Log::info('WAHA session restarted', ['session' => $sessionName]);
            
            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA restartSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Logout session (disconnect WhatsApp)
     */
    public function logoutSession(string $sessionName): array
    {
        try {
            // Endpoint: POST /api/{session}/auth/logout
            $response = $this->client->post("/api/{$sessionName}/auth/logout");
            
            Log::info('WAHA session logged out', ['session' => $sessionName]);
            
            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA logoutSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            // Logout might fail if already disconnected, that's OK
            return [
                'success' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete session
     */
    public function deleteSession(string $sessionName): array
    {
        try {
            $response = $this->client->delete("/api/sessions/{$sessionName}");
            
            Log::info('WAHA session deleted', ['session' => $sessionName]);
            
            return [
                'success' => true,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA deleteSession error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number untuk WAHA
     * Contoh: 628123456789 -> 628123456789@c.us
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add @c.us if not present
        if (!str_contains($phoneNumber, '@')) {
            $phoneNumber .= '@c.us';
        }
        
        return $phoneNumber;
    }

    /**
     * Check if WAHA is configured and reachable
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    /**
     * Test connection to WAHA
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'WAHA is not configured. Please configure in Settings.',
            ];
        }

        try {
            $response = $this->client->get('/api/sessions');
            
            return [
                'success' => true,
                'message' => 'Successfully connected to WAHA!',
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to WAHA: ' . $e->getMessage(),
            ];
        }
    }
}