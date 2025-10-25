<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Chat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatSyncService
{
    protected WahaApiService $wahaService;

    public function __construct(WahaApiService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /**
     * Sync all chats from WAHA for an account
     */
    public function syncChatsFromWaha(Account $account, int $limit = 100): array
    {
        try {
            // Get chats overview from WAHA
            $result = $this->wahaService->getChatsOverview($account->session_name, $limit);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to get chats from WAHA',
                ];
            }

            $wahaChats = $result['chats'] ?? [];
            $syncedCount = 0;
            $updatedCount = 0;

            foreach ($wahaChats as $wahaChat) {
                $chatId = $wahaChat['id'] ?? null;
                
                if (!$chatId) {
                    continue;
                }

                // Extract phone number from chatId (remove @c.us or @g.us)
                $contactPhone = str_replace(['@c.us', '@g.us'], '', $chatId);
                
                // Get contact name
                $contactName = $wahaChat['name'] ?? null;
                
                // Find or create chat
                $chat = Chat::firstOrNew([
                    'account_id' => $account->id,
                    'contact_phone' => $contactPhone,
                ]);

                $isNew = !$chat->exists;

                // Update chat data
                $chat->contact_name = $contactName;
                
                // Update last message if available
                if (isset($wahaChat['lastMessage'])) {
                    $lastMsg = $wahaChat['lastMessage'];
                    $chat->last_message = $lastMsg['body'] ?? '[Media]';
                    
                    if (isset($lastMsg['timestamp'])) {
                        $chat->last_message_at = date('Y-m-d H:i:s', $lastMsg['timestamp']);
                    }
                }
                
                // Get unread count from WAHA chat data
                $unreadCount = $this->extractUnreadCount($wahaChat);
                if ($unreadCount !== null) {
                    $chat->unread_count = $unreadCount;
                }
                
                // Save chat picture URL if available
                if (isset($wahaChat['picture']) && $wahaChat['picture']) {
                    $chat->picture_url = $wahaChat['picture'];
                } else {
                    // Try to fetch picture from WAHA API if not in overview
                    $this->fetchAndSaveChatPicture($account, $chatId, $chat);
                }
                
                $chat->save();

                if ($isNew) {
                    $syncedCount++;
                } else {
                    $updatedCount++;
                }
            }

            Log::info('Chats synced from WAHA', [
                'account_id' => $account->id,
                'new' => $syncedCount,
                'updated' => $updatedCount,
                'total' => count($wahaChats),
            ]);

            return [
                'success' => true,
                'synced' => $syncedCount,
                'updated' => $updatedCount,
                'total' => count($wahaChats),
            ];

        } catch (\Exception $e) {
            Log::error('ChatSyncService error', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch and save chat picture
     */
    protected function fetchAndSaveChatPicture(Account $account, string $chatId, Chat $chat): void
    {
        try {
            $result = $this->wahaService->getChatPicture($account->session_name, $chatId);
            
            if ($result['success'] && $result['url']) {
                $chat->picture_url = $result['url'];
            }
        } catch (\Exception $e) {
            Log::debug('Failed to fetch chat picture', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract unread count from WAHA chat data
     */
    protected function extractUnreadCount(array $wahaChat): ?int
    {
        // Try different possible locations for unread count
        return $wahaChat['unreadCount'] ?? 
               $wahaChat['unreadMessages'] ?? 
               $wahaChat['_chat']['unreadCount'] ?? 
               $wahaChat['_data']['unreadCount'] ?? 
               null;
    }

    /**
     * Sync single chat from WAHA
     */
    public function syncSingleChat(Account $account, string $contactPhone): array
    {
        try {
            // Format chat ID
            $chatId = $this->formatChatId($contactPhone);
            
            // Get chat picture
            $pictureResult = $this->wahaService->getChatPicture($account->session_name, $chatId);
            
            // Find or create chat
            $chat = Chat::firstOrNew([
                'account_id' => $account->id,
                'contact_phone' => $contactPhone,
            ]);

            // Update picture if available
            if ($pictureResult['success'] && $pictureResult['url']) {
                $chat->picture_url = $pictureResult['url'];
            }
            
            $chat->save();

            return [
                'success' => true,
                'chat' => $chat,
            ];

        } catch (\Exception $e) {
            Log::error('Sync single chat error', [
                'error' => $e->getMessage(),
                'contact_phone' => $contactPhone,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark chat messages as read in WAHA
     */
    public function markChatAsRead(Chat $chat): array
    {
        try {
            $account = $chat->account;
            $chatId = $this->formatChatId($chat->contact_phone);
            
            // Mark messages as read in WAHA
            $result = $this->wahaService->readChatMessages(
                $account->session_name,
                $chatId
            );

            if ($result['success']) {
                // Update local unread count
                $chat->update(['unread_count' => 0]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Mark chat as read error', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh chat picture from WAHA
     */
    public function refreshChatPicture(Chat $chat, bool $forceRefresh = false): array
    {
        try {
            $account = $chat->account;
            $chatId = $this->formatChatId($chat->contact_phone);
            
            $result = $this->wahaService->getChatPicture(
                $account->session_name,
                $chatId,
                $forceRefresh
            );

            if ($result['success'] && $result['url']) {
                $chat->update(['picture_url' => $result['url']]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Refresh chat picture error', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format chat ID for WhatsApp
     */
    protected function formatChatId(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Convert 0xxx to 62xxx (Indonesia)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }
        
        // Add @c.us if not present
        if (!str_contains($phoneNumber, '@')) {
            $phoneNumber .= '@c.us';
        }
        
        return $phoneNumber;
    }
}