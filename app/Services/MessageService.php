<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected WahaApiService $wahaService;

    public function __construct(WahaApiService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /**
     * Send text message
     */
    public function sendText(Account $account, Chat $chat, string $text): array
    {
        try {
            // Create message record in database
            $message = $this->createMessageRecord($account, $chat, [
                'message_content' => $text,
                'media_type' => 'text',
            ]);

            // Format phone number
            $chatId = $this->formatChatId($chat->contact_phone);

            // Send via WAHA - FIXED: menggunakan sendMessage() bukan sendText()
            // dan menggunakan session_name bukan waha_session_id
            $result = $this->wahaService->sendMessage(
                $account->session_name,
                $chatId,
                $text
            );

            if ($result['success']) {
                // Update message as sent
                $message->markAsSent(
                    $result['data']['id'] ?? null,
                    $result['data'] ?? null
                );

                // Update chat last message
                $chat->updateLastMessage($message);

                Log::info('Message sent successfully', [
                    'message_id' => $message->id,
                    'waha_id' => $result['data']['id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => $message,
                ];
            }

            // Mark as failed
            $message->markAsFailed($result['error'] ?? 'Failed to send');

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send message',
                'message' => $message,
            ];

        } catch (\Exception $e) {
            Log::error('MessageService sendText error', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
                'chat_id' => $chat->id,
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
    public function sendImage(Account $account, Chat $chat, string $imageUrl, ?string $caption = null, ?string $filename = null): array
    {
        try {
            // Create message record
            $message = $this->createMessageRecord($account, $chat, [
                'message_content' => $caption ?? 'Image',
                'message_type' => 'image',
                'media_url' => $imageUrl,
                'media_filename' => $filename,
            ]);

            // Format phone number
            $chatId = $this->formatChatId($chat->contact_phone);

            // Send via WAHA - FIXED: menggunakan session_name
            $result = $this->wahaService->sendImage(
                $account->session_name,
                $chatId,
                $imageUrl,
                $caption
            );

            if ($result['success']) {
                $message->markAsSent(
                    $result['data']['id'] ?? null,
                    $result['data'] ?? null
                );

                $chat->updateLastMessage($message);

                return [
                    'success' => true,
                    'message' => $message,
                ];
            }

            $message->markAsFailed($result['error'] ?? 'Failed to send');

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send image',
                'message' => $message,
            ];

        } catch (\Exception $e) {
            Log::error('MessageService sendImage error', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
                'chat_id' => $chat->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send document/file
     */
    public function sendDocument(Account $account, Chat $chat, string $fileUrl, string $filename, ?string $caption = null): array
    {
        try {
            // Create message record
            $message = $this->createMessageRecord($account, $chat, [
                'message_content' => $caption ?? $filename,
                'message_type' => 'document',
                'media_url' => $fileUrl,
                'media_filename' => $filename,
            ]);

            // Format phone number
            $chatId = $this->formatChatId($chat->contact_phone);

            // CATATAN: Method sendDocument() belum ada di WahaApiService
            // Anda perlu menambahkannya atau gunakan sendImage untuk sementara
            Log::warning('sendDocument called but not implemented in WahaApiService', [
                'chat_id' => $chat->id,
                'filename' => $filename,
            ]);

            return [
                'success' => false,
                'error' => 'Document sending not yet implemented',
            ];

        } catch (\Exception $e) {
            Log::error('MessageService sendDocument error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create message record in database
     */
    protected function createMessageRecord(Account $account, Chat $chat, array $data): ChatMessage
    {
        return ChatMessage::create([
            'chat_id' => $chat->id,
            'account_id' => $account->id,
            'direction' => 'outgoing',
            'from_phone' => $account->phone_number,
            'to_phone' => $chat->contact_phone,
            'message_content' => $data['message_content'],
            'media_type' => $data['media_type'] ?? 'text',
            'media_url' => $data['media_url'] ?? null,
            'media_caption' => $data['media_caption'] ?? null,
            'status' => 'pending',
        ]);
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