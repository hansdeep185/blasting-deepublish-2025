<?php

namespace App\Services;

use App\Models\AiAgent;
use App\Models\AgentConversation;
use App\Models\LlmProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate AI response for incoming message
     */
    public function generateResponse(
        AiAgent $agent,
        string $userMessage,
        array $conversationHistory = [],
        AgentConversation $conversation = null
    ): array {
        // Parse model string to get provider and model
        $parsed = LlmProvider::parseModelString($agent->llm_model);
        $provider = LlmProvider::getByModelString($agent->llm_model);

        if (!$provider) {
            return [
                'success' => false,
                'error' => 'LLM Provider not found or not configured',
            ];
        }

        // Build system prompt dengan knowledge base
        $systemPrompt = $this->buildSystemPrompt($agent);

        // Build messages array
        $messages = $this->buildMessages($systemPrompt, $conversationHistory, $userMessage);

        try {
            // Call LLM based on provider
            $response = match($parsed['provider']) {
                'openai' => $this->callOpenAI($provider, $parsed['model'], $messages, $agent),
                'anthropic' => $this->callAnthropic($provider, $parsed['model'], $messages, $agent),
                'groq' => $this->callGroq($provider, $parsed['model'], $messages, $agent),
                default => throw new \Exception("Unsupported provider: {$parsed['provider']}")
            };

            // Check for handover conditions
            $shouldHandover = false;
            $handoverReason = null;
            
            if ($agent->handover_enabled && $conversation) {
                $handoverCheck = $this->checkHandoverConditions($agent, $userMessage, $response['content']);
                $shouldHandover = $handoverCheck['should_handover'];
                $handoverReason = $handoverCheck['reason'];
            }

            // Check for auto-labeling
            $suggestedLabels = [];
            if ($agent->auto_labeling_enabled && $conversation) {
                $suggestedLabels = $this->suggestLabels($agent, $userMessage, $response['content']);
            }

            return [
                'success' => true,
                'content' => $response['content'],
                'should_handover' => $shouldHandover,
                'handover_reason' => $handoverReason,
                'suggested_labels' => $suggestedLabels,
                'usage' => $response['usage'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('AiChatService generateResponse error', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build system prompt with knowledge base
     */
    protected function buildSystemPrompt(AiAgent $agent): string
    {
        $prompt = $agent->system_prompt;

        // Add tasks description if exists
        if ($agent->tasks_description) {
            $prompt .= "\n\nTUGAS-TUGAS:\n" . $agent->tasks_description;
        }

        // Add knowledge base
        $knowledgeBase = $agent->knowledgeBase()
            ->where('status', 'completed')
            ->get();

        if ($knowledgeBase->isNotEmpty()) {
            $prompt .= "\n\nKNOWLEDGE BASE:\n";
            foreach ($knowledgeBase as $kb) {
                $prompt .= "\n[{$kb->title}]\n{$kb->content}\n";
            }
        }

        // Add media library info
        $mediaLibrary = $agent->mediaLibrary()->get();
        if ($mediaLibrary->isNotEmpty()) {
            $prompt .= "\n\nMEDIA TERSEDIA:\n";
            foreach ($mediaLibrary as $media) {
                $prompt .= "- {$media->name}";
                if ($media->usage_trigger) {
                    $prompt .= " (Gunakan saat: {$media->usage_trigger})";
                }
                if ($media->trigger_keywords) {
                    $prompt .= " [Keywords: {$media->trigger_keywords}]";
                }
                $prompt .= "\n";
            }
        }

        return $prompt;
    }

    /**
     * Build messages array for LLM
     */
    protected function buildMessages(string $systemPrompt, array $history, string $userMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add conversation history (limit to last 20 messages)
        $history = array_slice($history, -20);
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        return $messages;
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(LlmProvider $provider, string $model, array $messages, AiAgent $agent): array
    {
        $response = $this->client->post($provider->api_url ?: 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $provider->api_key,
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $agent->temperature,
                'max_tokens' => $agent->max_tokens,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? null,
        ];
    }

    /**
     * Call Anthropic API
     */
    protected function callAnthropic(LlmProvider $provider, string $model, array $messages, AiAgent $agent): array
    {
        // Extract system message
        $systemMessage = '';
        $conversationMessages = [];
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage = $msg['content'];
            } else {
                $conversationMessages[] = $msg;
            }
        }

        $response = $this->client->post($provider->api_url ?: 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $provider->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => $model,
                'system' => $systemMessage,
                'messages' => $conversationMessages,
                'temperature' => $agent->temperature,
                'max_tokens' => $agent->max_tokens,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'usage' => $data['usage'] ?? null,
        ];
    }

    /**
     * Call Groq API
     */
    protected function callGroq(LlmProvider $provider, string $model, array $messages, AiAgent $agent): array
    {
        $response = $this->client->post($provider->api_url ?: 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $provider->api_key,
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $agent->temperature,
                'max_tokens' => $agent->max_tokens,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? null,
        ];
    }

    /**
     * Check if handover conditions are met
     */
    protected function checkHandoverConditions(AiAgent $agent, string $userMessage, string $aiResponse): array
    {
        if (!$agent->handover_conditions) {
            return ['should_handover' => false, 'reason' => null];
        }

        // Use simple LLM call to check handover conditions
        try {
            $prompt = "Berdasarkan kondisi handover berikut:\n\n{$agent->handover_conditions}\n\n";
            $prompt .= "Pesan user: {$userMessage}\n";
            $prompt .= "Respons AI: {$aiResponse}\n\n";
            $prompt .= "Apakah kondisi handover terpenuhi? Jawab hanya dengan 'YA' atau 'TIDAK'. ";
            $prompt .= "Jika YA, berikan alasan singkat setelah kata YA dengan format: YA - [alasan]";

            // Quick check with minimal tokens
            $parsed = LlmProvider::parseModelString($agent->llm_model);
            $provider = LlmProvider::getByModelString($agent->llm_model);

            if (!$provider) {
                return ['should_handover' => false, 'reason' => null];
            }

            $messages = [
                ['role' => 'system', 'content' => 'Kamu adalah asisten yang mengevaluasi kondisi handover.'],
                ['role' => 'user', 'content' => $prompt]
            ];

            $response = match($parsed['provider']) {
                'openai' => $this->callOpenAI($provider, $parsed['model'], $messages, $agent),
                'anthropic' => $this->callAnthropic($provider, $parsed['model'], $messages, $agent),
                'groq' => $this->callGroq($provider, $parsed['model'], $messages, $agent),
                default => ['content' => 'TIDAK']
            };

            $result = trim($response['content']);
            
            if (str_starts_with(strtoupper($result), 'YA')) {
                $reason = str_replace('YA -', '', $result);
                $reason = trim(str_replace('YA', '', $reason));
                return [
                    'should_handover' => true,
                    'reason' => $reason ?: 'Kondisi handover terpenuhi'
                ];
            }

            return ['should_handover' => false, 'reason' => null];

        } catch (\Exception $e) {
            Log::error('Handover check failed', ['error' => $e->getMessage()]);
            return ['should_handover' => false, 'reason' => null];
        }
    }

    /**
     * Suggest labels based on conversation
     */
    protected function suggestLabels(AiAgent $agent, string $userMessage, string $aiResponse): array
    {
        if (!$agent->labeling_instructions) {
            return [];
        }

        try {
            // Get available labels
            $labels = $agent->user->conversationLabels()->pluck('name')->toArray();
            
            if (empty($labels)) {
                return [];
            }

            $prompt = "Berdasarkan instruksi labeling berikut:\n\n{$agent->labeling_instructions}\n\n";
            $prompt .= "Pesan user: {$userMessage}\n";
            $prompt .= "Respons AI: {$aiResponse}\n\n";
            $prompt .= "Label yang tersedia: " . implode(', ', $labels) . "\n\n";
            $prompt .= "Pilih label yang paling sesuai (bisa lebih dari satu). ";
            $prompt .= "Jawab hanya dengan nama label yang dipisahkan koma, atau 'TIDAK ADA' jika tidak ada yang sesuai.";

            $parsed = LlmProvider::parseModelString($agent->llm_model);
            $provider = LlmProvider::getByModelString($agent->llm_model);

            if (!$provider) {
                return [];
            }

            $messages = [
                ['role' => 'system', 'content' => 'Kamu adalah asisten yang mengkategorikan percakapan.'],
                ['role' => 'user', 'content' => $prompt]
            ];

            $response = match($parsed['provider']) {
                'openai' => $this->callOpenAI($provider, $parsed['model'], $messages, $agent),
                'anthropic' => $this->callAnthropic($provider, $parsed['model'], $messages, $agent),
                'groq' => $this->callGroq($provider, $parsed['model'], $messages, $agent),
                default => ['content' => 'TIDAK ADA']
            };

            $result = trim($response['content']);
            
            if (strtoupper($result) === 'TIDAK ADA') {
                return [];
            }

            // Parse suggested labels
            $suggestedLabels = array_map('trim', explode(',', $result));
            
            // Filter only valid labels
            return array_values(array_intersect($suggestedLabels, $labels));

        } catch (\Exception $e) {
            Log::error('Label suggestion failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Test connection to LLM provider
     */
    public function testProvider(LlmProvider $provider): array
    {
        try {
            $parsed = LlmProvider::parseModelString($provider->default_model ?: $provider->available_models[0]);
            
            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Say "Connection successful!" if you can read this.']
            ];

            // Create temporary agent for testing
            $tempAgent = new AiAgent([
                'temperature' => 0.7,
                'max_tokens' => 50
            ]);

            $response = match($parsed['provider']) {
                'openai' => $this->callOpenAI($provider, $parsed['model'], $messages, $tempAgent),
                'anthropic' => $this->callAnthropic($provider, $parsed['model'], $messages, $tempAgent),
                'groq' => $this->callGroq($provider, $parsed['model'], $messages, $tempAgent),
                default => throw new \Exception("Unsupported provider")
            };

            return [
                'success' => true,
                'message' => 'Connection successful! Response: ' . $response['content'],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}