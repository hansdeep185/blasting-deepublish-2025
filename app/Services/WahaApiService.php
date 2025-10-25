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
                    'start' => true, // Auto-start session
                    'config' => [
                        // 🔥 TAMBAHAN: Enable NOWEB store
                        'noweb' => [
                            'store' => [
                                'enabled' => true,      // Enable store untuk chats API
                                'fullSync' => false,    // Tidak full sync (lightweight)
                            ],
                        ],
                        // Webhook configuration
                        'webhooks' => [
                            [
                                'url' => url('/api/waha/webhook'),
                                'events' => [
                                    'message',
                                    'message.any',
                                    'session.status',
                                    'message.ack',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA session created with NOWEB store', [
                'session' => $sessionName,
                'noweb_enabled' => true,
                'data' => $data,
            ]);
            
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
     * Update Session
     */
    public function updateSessionConfig(string $sessionName): array
    {
        try {
            // Get current session config
            $getResponse = $this->client->get("/api/sessions/{$sessionName}");
            $currentConfig = json_decode($getResponse->getBody()->getContents(), true);
            
            // Update with NOWEB config
            $response = $this->client->patch("/api/sessions/{$sessionName}", [
                'json' => [
                    'config' => [
                        'noweb' => [
                            'store' => [
                                'enabled' => true,
                                'fullSync' => false,
                            ],
                        ],
                        'webhooks' => [
                            [
                                'url' => url('/api/waha/webhook'),
                                'events' => [
                                    'message',
                                    'message.any',
                                    'session.status',
                                    'message.ack',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA session config updated with NOWEB', [
                'session' => $sessionName,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA updateSessionConfig error', [
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
            // Stop session
            $this->client->post("/api/sessions/{$sessionName}/stop");
            
            // Wait a bit
            sleep(2);
            
            // Start session
            $response = $this->client->post("/api/sessions/{$sessionName}/start");
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA session restarted', [
                'session' => $sessionName,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
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

    /**
     * Send document/file message
     * Endpoint: POST /api/sendFile
     */
    public function sendDocument(string $sessionName, string $to, string $fileUrl, string $filename, ?string $caption = null): array
    {
        try {
            $chatId = $this->formatPhoneNumber($to);
            
            $response = $this->client->post("/api/sendFile", [
                'json' => [
                    'session' => $sessionName,
                    'chatId' => $chatId,
                    'file' => [
                        'url' => $fileUrl,
                        'filename' => $filename,
                    ],
                    'caption' => $caption,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA document sent', [
                'session' => $sessionName,
                'to' => $chatId,
                'filename' => $filename,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA sendDocument error', [
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
     * Get all chats with overview (for chat list)
     * Endpoint: GET /api/{session}/chats/overview
     */
    public function getChatsOverview(string $sessionName, int $limit = 100, int $offset = 0): array
    {
        try {
            $response = $this->client->get("/api/{$sessionName}/chats/overview", [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

            $chats = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA chats overview retrieved', [
                'session' => $sessionName,
                'count' => count($chats),
            ]);
            
            return [
                'success' => true,
                'chats' => $chats,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getChatsOverview error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'chats' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all chats (simple list)
     * Endpoint: GET /api/{session}/chats
     */
    public function getAllChats(string $sessionName, array $params = []): array
    {
        try {
            $defaultParams = [
                'limit' => 100,
                'offset' => 0,
                'sortBy' => 'messageTimestamp',
                'sortOrder' => 'desc',
            ];
            
            $queryParams = array_merge($defaultParams, $params);
            
            $response = $this->client->get("/api/{$sessionName}/chats", [
                'query' => $queryParams,
            ]);

            $chats = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA all chats retrieved', [
                'session' => $sessionName,
                'count' => count($chats),
            ]);
            
            return [
                'success' => true,
                'chats' => $chats,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getAllChats error', [
                'session' => $sessionName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'chats' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get chat picture (avatar)
     * Endpoint: GET /api/{session}/chats/{chatId}/picture
     */
    public function getChatPicture(string $sessionName, string $chatId, bool $refresh = false): array
    {
        try {
            $response = $this->client->get("/api/{$sessionName}/chats/{$chatId}/picture", [
                'query' => $refresh ? ['refresh' => 'True'] : [],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'url' => $data['url'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getChatPicture error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark chat as unread
     * Endpoint: POST /api/{session}/chats/{chatId}/unread
     */
    public function markChatUnread(string $sessionName, string $chatId): array
    {
        try {
            $response = $this->client->post("/api/{$sessionName}/chats/{$chatId}/unread");
            
            Log::info('WAHA chat marked as unread', [
                'session' => $sessionName,
                'chat_id' => $chatId,
            ]);
            
            return ['success' => true];
        } catch (GuzzleException $e) {
            Log::error('WAHA markChatUnread error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Read all messages in chat (mark as read)
     * Endpoint: POST /api/{session}/chats/{chatId}/messages/read
     */
    public function readChatMessages(string $sessionName, string $chatId, ?int $messages = null, ?int $days = null): array
    {
        try {
            $payload = [];
            
            if ($messages !== null) {
                $payload['messages'] = $messages;
            }
            
            if ($days !== null) {
                $payload['days'] = $days;
            }
            
            $response = $this->client->post("/api/{$sessionName}/chats/{$chatId}/messages/read", [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA chat messages marked as read', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'read_ids' => $data['ids'] ?? [],
            ]);
            
            return [
                'success' => true,
                'ids' => $data['ids'] ?? [],
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA readChatMessages error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get messages from specific chat
     * Endpoint: GET /api/{session}/chats/{chatId}/messages
     */
    public function getChatMessages(string $sessionName, string $chatId, int $limit = 50, int $offset = 0, bool $downloadMedia = false): array
    {
        try {
            $response = $this->client->get("/api/{$sessionName}/chats/{$chatId}/messages", [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'downloadMedia' => $downloadMedia ? 'true' : 'false',
                ],
            ]);

            $messages = json_decode($response->getBody()->getContents(), true);
            
            Log::info('WAHA messages retrieved', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'count' => count($messages),
            ]);
            
            return [
                'success' => true,
                'messages' => $messages,
            ];
        } catch (GuzzleException $e) {
            Log::error('WAHA getChatMessages error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'messages' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Archive chat
     * Endpoint: POST /api/{session}/chats/{chatId}/archive
     */
    public function archiveChat(string $sessionName, string $chatId): array
    {
        try {
            $response = $this->client->post("/api/{$sessionName}/chats/{$chatId}/archive");
            
            Log::info('WAHA chat archived', [
                'session' => $sessionName,
                'chat_id' => $chatId,
            ]);
            
            return ['success' => true];
        } catch (GuzzleException $e) {
            Log::error('WAHA archiveChat error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unarchive chat
     * Endpoint: POST /api/{session}/chats/{chatId}/unarchive
     */
    public function unarchiveChat(string $sessionName, string $chatId): array
    {
        try {
            $response = $this->client->post("/api/{$sessionName}/chats/{$chatId}/unarchive");
            
            Log::info('WAHA chat unarchived', [
                'session' => $sessionName,
                'chat_id' => $chatId,
            ]);
            
            return ['success' => true];
        } catch (GuzzleException $e) {
            Log::error('WAHA unarchiveChat error', [
                'session' => $sessionName,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

}