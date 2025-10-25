<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class MessageSyncService
{
    protected WahaApiService $wahaService;

    public function __construct(WahaApiService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /**
     * Sync messages from WAHA for a specific chat
     */
    public function syncChatMessages(Chat $chat, int $limit = 50): array
    {
        try {
            $account = $chat->account;
            
            // Format chat ID
            $chatId = $this->formatChatId($chat->contact_phone);
            
            // Get messages from WAHA
            $result = $this->wahaService->getChatMessages(
                $account->waha_session_id,
                $chatId,
                $limit
            );

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'],
                ];
            }

            $wahaMessages = $result['messages'] ?? [];
            $newMessages = [];
            $existingMessageIds = ChatMessage::where('chat_id', $chat->id)
                ->whereNotNull('waha_message_id')
                ->pluck('waha_message_id')
                ->toArray();

            foreach ($wahaMessages as $wahaMsg) {
                $wahaMessageId = $this->extractMessageId($wahaMsg);
                
                // Skip if already exists
                if (in_array($wahaMessageId, $existingMessageIds)) {
                    continue;
                }

                // Determine direction
                $isFromMe = $wahaMsg['fromMe'] ?? false;
                $direction = $isFromMe ? 'outgoing' : 'incoming';
                
                // Skip if outgoing (already in DB)
                if ($direction === 'outgoing') {
                    continue;
                }

                // Extract message content
                $messageContent = $this->extractMessageContent($wahaMsg);
                
                if (!$messageContent) {
                    continue;
                }

                // Create message
                $message = ChatMessage::create([
                    'chat_id' => $chat->id,
                    'account_id' => $account->id,
                    'direction' => $direction,
                    'from_phone' => $chat->contact_phone,
                    'to_phone' => $account->phone_number,
                    'message_content' => $messageContent,
                    'media_type' => $this->getMediaType($wahaMsg),
                    'status' => 'received',
                    'waha_message_id' => $wahaMessageId,
                    'waha_response' => json_encode($wahaMsg),
                ]);

                $newMessages[] = $message;
            }

            // Update chat if new messages exist
            if (!empty($newMessages)) {
                $latestMessage = end($newMessages);
                $chat->updateLastMessage($latestMessage);
                $chat->incrementUnread();
            }

            return [
                'success' => true,
                'new_messages' => $newMessages,
                'count' => count($newMessages),
            ];

        } catch (\Exception $e) {
            Log::error('MessageSyncService error', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function extractMessageId($wahaMsg): ?string
    {
        return $wahaMsg['id'] ?? 
               $wahaMsg['key']['id'] ?? 
               $wahaMsg['_data']['id']['id'] ?? 
               null;
    }

    protected function extractMessageContent($wahaMsg): ?string
    {
        // Try different possible structures
        return $wahaMsg['body'] ?? 
               $wahaMsg['message']['conversation'] ?? 
               $wahaMsg['message']['extendedTextMessage']['text'] ?? 
               $wahaMsg['_data']['body'] ?? 
               null;
    }

    protected function getMediaType($wahaMsg): string
    {
        if (isset($wahaMsg['hasMedia']) && $wahaMsg['hasMedia']) {
            return $wahaMsg['type'] ?? 'document';
        }
        return 'text';
    }

    protected function formatChatId(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }
        
        if (!str_contains($phoneNumber, '@')) {
            $phoneNumber .= '@c.us';
        }
        
        return $phoneNumber;
    }
}