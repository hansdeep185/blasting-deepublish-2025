<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class N8nService
{
    protected Client $client;
    protected ?string $webhookUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->webhookUrl = settings('n8n_webhook_url');
        $this->apiKey = settings('n8n_api_key');

        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Trigger AI agent workflow untuk pesan masuk
     */
    public function triggerAiAgent(int $accountId, string $incomingMessage, string $fromNumber): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'n8n is not configured',
            ];
        }

        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'action' => 'ai_response',
                    'account_id' => $accountId,
                    'message' => $incomingMessage,
                    'from_number' => $fromNumber,
                    'timestamp' => now()->toIso8601String(),
                ],
                'headers' => [
                    'X-Api-Key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('n8n AI agent triggered', [
                'account_id' => $accountId,
                'from' => $fromNumber,
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('n8n triggerAiAgent error', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process training data - buat embeddings
     */
    public function processTrainingData(int $userId, array $trainingData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'n8n is not configured',
            ];
        }

        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'action' => 'process_training',
                    'user_id' => $userId,
                    'training_data' => $trainingData,
                    'timestamp' => now()->toIso8601String(),
                ],
                'headers' => [
                    'X-Api-Key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('n8n training data processed', [
                'user_id' => $userId,
                'data_count' => count($trainingData),
            ]);
            
            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            Log::error('n8n processTrainingData error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if n8n is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl);
    }

    /**
     * Test connection to n8n
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'n8n is not configured. Please configure in Settings.',
            ];
        }

        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => [
                    'action' => 'test',
                    'message' => 'Connection test from WhatsApp Blast App',
                ],
                'headers' => [
                    'X-Api-Key' => $this->apiKey,
                ],
            ]);
            
            return [
                'success' => true,
                'message' => 'Successfully connected to n8n!',
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to n8n: ' . $e->getMessage(),
            ];
        }
    }
}