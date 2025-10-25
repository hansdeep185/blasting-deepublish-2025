<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
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
            'timeout' => 10,
            'connect_timeout' => 3,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Send text message
     */
    public function sendText(string $session, string $chatId, string $text, ?string $replyTo = null): array
    {
        try {
            $payload = [
                'session' => $session,
                'chatId' => $chatId,
                'text' => $text,
                'linkPreview' => true,
                'linkPreviewHighQuality' => false,
            ];

            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }

            Log::info('WAHA sendText request', $payload);

            $response = $this->client->post('/api/sendText', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 || $statusCode === 201) {
                Log::info('WAHA sendText success', ['data' => $data]);
                
                // Extract message ID from nested structure
                $messageId = $data['key']['id'] ?? $data['id'] ?? null;
                
                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'data' => $data,
                ];
            }

            Log::error('WAHA sendText failed', [
                'status' => $statusCode,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Failed to send message',
                'data' => $data,
            ];

        } catch (ConnectException $e) {
            Log::error('WAHA connection error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Cannot connect to WAHA server. Please check if WAHA is running.',
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA sendText exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send image message
     */
    public function sendImage(string $session, string $chatId, string $imageUrl, ?string $caption = null, ?string $filename = null): array
    {
        try {
            $payload = [
                'session' => $session,
                'chatId' => $chatId,
                'file' => [
                    'url' => $imageUrl,
                ],
                'reply_to' => null,
            ];

            if ($filename) {
                $payload['file']['filename'] = $filename;
            }

            if ($caption) {
                $payload['caption'] = $caption;
            }

            Log::info('WAHA sendImage request', $payload);

            $response = $this->client->post('/api/sendImage', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Failed to send image',
                'data' => $data,
            ];

        } catch (ConnectException $e) {
            return [
                'success' => false,
                'error' => 'Cannot connect to WAHA server',
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send document/file
     */
    public function sendDocument(string $session, string $chatId, string $fileUrl, string $filename, ?string $caption = null): array
    {
        try {
            $payload = [
                'session' => $session,
                'chatId' => $chatId,
                'file' => [
                    'url' => $fileUrl,
                    'filename' => $filename,
                ],
                'reply_to' => null,
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            $response = $this->client->post('/api/sendFile', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Failed to send document',
            ];

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get session status
     */
    public function getSessionStatus(string $sessionName): array
    {
        try {
            $response = $this->client->get("/api/sessions/{$sessionName}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Also check if the QR code is embedded in the status response
            $qrCode = $data['qr'] ?? null;

            return [
                'success' => true,
                'status' => $data['status'] ?? 'UNKNOWN',
                'data' => $data,
                'qr' => $qrCode, // Return QR if it exists
            ];
        } catch (GuzzleException $e) {
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
            return [
                'success' => false,
                'sessions' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if WAHA is configured
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
                'error' => 'WAHA is not configured',
            ];
        }

        try {
            $response = $this->client->get('/api/sessions');
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to WAHA!',
                ];
            }

            return [
                'success' => false,
                'error' => 'WAHA returned status ' . $statusCode,
            ];
        } catch (ConnectException $e) {
            return [
                'success' => false,
                'error' => 'Cannot connect to WAHA: ' . $e->getMessage(),
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect: ' . $e->getMessage(),
            ];
        }
    }
    /**
     * Get messages from a chat
     */
    public function getChatMessages(string $session, string $chatId, int $limit = 50): array
    {
        try {
            $response = $this->client->get("/api/messages", [
                'query' => [
                    'session' => $session,
                    'chatId' => $chatId,
                    'limit' => $limit,
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return ['success' => true, 'messages' => $body];
            }

            $errorMessage = $body['message'] ?? 'Failed to get messages from WAHA';
            Log::error('WAHA getChatMessages API error', ['session' => $session, 'chatId' => $chatId, 'status' => $response->getStatusCode(), 'response' => $body]);
            return ['success' => false, 'error' => $errorMessage];

        } catch (ConnectException $e) {
            Log::error('WAHA getChatMessages connection error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Could not connect to WAHA service.'];
        } catch (GuzzleException $e) {
            Log::error('WAHA getChatMessages guzzle error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred while communicating with WAHA: ' . $e->getMessage()];
        }
    }

    /**
     * Start a new WAHA session with message store enabled.
     */
    public function startSession(string $sessionName): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WAHA service is not configured.'];
        }

        try {
            $response = $this->client->post("/api/sessions/start", [
                'json' => [
                    'name' => $sessionName,
                    'config' => [
                        'noweb' => [
                            'store' => [
                                'enabled' => true,
                                'fullSync' => true // Ini sangat penting untuk sinkronisasi
                            ]
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return ['success' => true, 'data' => $body];
            }

            $errorMessage = $body['message'] ?? 'Failed to start session';
            Log::error('WAHA startSession API error', ['session' => $sessionName, 'status' => $response->getStatusCode(), 'response' => $body]);
            return ['success' => false, 'error' => $errorMessage];

        } catch (ConnectException $e) {
            Log::error('WAHA startSession connection error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Could not connect to WAHA service.'];
        } catch (GuzzleException $e) {
            Log::error('WAHA startSession guzzle error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred while communicating with WAHA: ' . $e->getMessage()];
        }
    }

    /**
     * Stop and delete a WAHA session.
     */
    public function deleteSession(string $sessionName): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WAHA service is not configured.'];
        }

        try {
            $response = $this->client->delete("/api/sessions/{$sessionName}");

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::info('WAHA session deleted successfully', ['session' => $sessionName, 'response' => $body]);
                return ['success' => true, 'data' => $body];
            }

            $errorMessage = $body['message'] ?? 'Failed to delete session';
            Log::error('WAHA deleteSession API error', ['session' => $sessionName, 'status' => $response->getStatusCode(), 'response' => $body]);
            return ['success' => false, 'error' => $errorMessage];

        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                Log::warning('Attempted to delete a WAHA session that does not exist.', ['session' => $sessionName]);
                return ['success' => true, 'message' => 'Session not found, assumed deleted.'];
            }
            Log::error('WAHA deleteSession guzzle error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred while communicating with WAHA: ' . $e->getMessage()];
        }
    }

    /**
     * Get QR code for a session.
     */
    public function getQrCode(string $sessionName): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client->get("/api/{$sessionName}/auth/qr");
            $statusCode = $response->getStatusCode();
            $bodyContents = $response->getBody()->getContents();

            if ($statusCode === 200 && !empty($bodyContents)) {
                // Jika sudah base64 string (tanpa header), pastikan di blade nanti tambahkan:
                // <img src="data:image/png;base64,{{ $qrCode }}">
                return $bodyContents;
            }
            // Log jika gagal
            $body = json_decode($bodyContents, true);
            Log::warning('WAHA getQrCode failed with status', [
                'session' => $sessionName, 
                'status' => $statusCode,
                'body' => $body
            ]);
            return null;

        } catch (GuzzleException $e) {
            Log::error('WAHA getQrCode guzzle error', [
                'session' => $sessionName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}