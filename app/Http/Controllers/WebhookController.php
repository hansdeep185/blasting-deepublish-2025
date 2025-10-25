<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function wahaWebhook(Request $request)
    {
        Log::info('WAHA Webhook received', $request->all());

        $event = $request->input('event');
        $session = $request->input('session');
        $payload = $request->input('payload');

        // Handle message event
        if ($event === 'message' && isset($payload['body'])) {
            $this->handleIncomingMessage($session, $payload);
        }

        return response()->json(['success' => true]);
    }

    protected function handleIncomingMessage(string $session, array $payload)
    {
        try {
            // Find account by session
            $account = Account::where('waha_session_id', $session)->first();
            
            if (!$account) {
                Log::warning('Account not found for session', ['session' => $session]);
                return;
            }

            // Skip outgoing messages (sent by us)
            if ($payload['fromMe'] ?? false) {
                return;
            }

            // Get phone number
            $from = $payload['from'] ?? null;
            if (!$from) {
                return;
            }

            // Remove @c.us or @s.whatsapp.net
            $phoneNumber = str_replace(['@c.us', '@s.whatsapp.net'], '', $from);

            // Get or create chat
            $chat = Chat::getOrCreate($account->id, $phoneNumber, $payload['notifyName'] ?? null);

            // Create message
            $message = ChatMessage::create([
                'chat_id' => $chat->id,
                'account_id' => $account->id,
                'direction' => 'incoming',
                'from_phone' => $phoneNumber,
                'to_phone' => $account->phone_number,
                'message_content' => $payload['body'] ?? '',
                'media_type' => $this->getMediaType($payload),
                'status' => 'received',
                'waha_message_id' => $payload['id'] ?? null,
                'waha_response' => json_encode($payload),
            ]);

            // Update chat
            $chat->updateLastMessage($message);
            $chat->incrementUnread();

            Log::info('Incoming message saved', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling incoming message', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    protected function getMediaType(array $payload): string
    {
        if (isset($payload['hasMedia']) && $payload['hasMedia']) {
            return $payload['type'] ?? 'document';
        }
        return 'text';
    }
}