<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\MessageService;
use App\Services\ChatSyncService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected MessageService $messageService;
    protected ChatSyncService $chatSyncService;


    public function __construct(
        MessageService $messageService, 
        ChatSyncService $chatSyncService
        ){
            $this->messageService = $messageService;
            $this->chatSyncService = $chatSyncService;
        }

    /**
     * Display chat list
     */
     public function index(Request $request)
    {
        $accounts = Account::where('user_id', auth()->id())
            ->where('status', 'connected')
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Please connect a WhatsApp account first!');
        }

        $selectedAccount = $request->account_id 
            ? Account::findOrFail($request->account_id)
            : $accounts->first();

        if ($selectedAccount->user_id !== auth()->id()) {
            abort(403);
        }

        // 🔥 RESTORED: Auto-sync (akan berfungsi setelah NOWEB enabled)
        try {
            $this->chatSyncService->syncChatsFromWaha($selectedAccount, 50);
        } catch (\Exception $e) {
            // Silent fail - tidak mengganggu loading halaman
            Log::debug('Chat sync skipped', ['error' => $e->getMessage()]);
        }

        $query = Chat::where('account_id', $selectedAccount->id)
            ->with('latestMessage');

        if ($request->filled('filter')) {
            if ($request->filter === 'unread') {
                $query->withUnread();
            } elseif ($request->filter === 'archived') {
                $query->archived();
            } else {
                $query->active();
            }
        } else {
            $query->active();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $chats = $query->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('chats.index', compact('accounts', 'selectedAccount', 'chats'));
    }

    public function syncChats(Request $request)
    {
        $accountId = $request->account_id ?? auth()->user()->accounts()->first()?->id;
        
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'error' => 'No account found',
            ], 404);
        }

        $account = Account::findOrFail($accountId);

        if ($account->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
            ], 403);
        }

        try {
            $result = $this->chatSyncService->syncChatsFromWaha($account, 100);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Synced {$result['synced']} new chats and updated {$result['updated']} existing chats",
                    'data' => $result,
                ]);
            }

            // Better error message for NOWEB requirement
            $errorMsg = $result['error'] ?? 'Failed to sync chats';
            if (str_contains($errorMsg, 'NOWEB') || str_contains($errorMsg, '400')) {
                return response()->json([
                    'success' => false,
                    'error' => 'NOWEB store not enabled. Run: php artisan waha:enable-noweb --all',
                    'hint' => 'This will enable NOWEB store for all sessions to fix 400 errors',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 500);

        } catch (\Exception $e) {
            Log::error('Sync chats error', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred',
            ], 500);
        }
    }


    public function show(Request $request, Account $account, $chatId = null)
    {
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        // 🔥 RESTORED: Auto-sync on page load
        try {
            $this->chatSyncService->syncChatsFromWaha($account, 30);
        } catch (\Exception $e) {
            Log::debug('Chat sync skipped on conversation load', [
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->filled('phone')) {
            $chat = Chat::getOrCreate($account->id, $request->phone, $request->name);
            return redirect()->route('chats.conversation', ['account' => $account->id, 'chat' => $chat->id]);
        }

        if ($chatId) {
            $chat = Chat::with(['messages' => function($query) {
                $query->latest()->limit(50);
            }])->findOrFail($chatId);

            if ($chat->account_id !== $account->id) {
                abort(403);
            }

            // 🔥 RESTORED: Mark as read di WAHA (jika NOWEB enabled)
            try {
                $this->chatSyncService->markChatAsRead($chat);
            } catch (\Exception $e) {
                Log::debug('Mark as read skipped', ['error' => $e->getMessage()]);
            }

            // Always mark as read locally
            $chat->markAsRead();

            return view('chats.conversation', compact('account', 'chat'));
        }

        return view('chats.conversation', compact('account'))->with('chat', null);
    }

    /**
     * Send message
     */
    public function send(Request $request, Account $account)
    {
        if ($account->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string|max:4096',
        ]);

        $chat = Chat::findOrFail($validated['chat_id']);

        if ($chat->account_id !== $account->id) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        try {
            $result = $this->messageService->sendText($account, $chat, $validated['message']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send message',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Chat send error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Load more messages (pagination)
     */
    public function loadMessages(Chat $chat, Request $request)
    {
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        $messages = $chat->messages()
            ->where('id', '<', $request->before_id)
            ->latest()
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Archive chat
     */
    public function archive(Chat $chat)
    {
        // Check ownership
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        $chat->archive();

        return response()->json([
            'success' => true,
            'message' => 'Chat archived successfully!',
        ]);
    }

    /**
     * Unarchive chat
     */
    public function unarchive(Chat $chat)
    {
        // Check ownership
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        $chat->unarchive();

        return response()->json([
            'success' => true,
            'message' => 'Chat unarchived successfully!',
        ]);
    }

    /**
     * Delete chat
     */
    public function destroy(Chat $chat)
    {
        // Check ownership
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat deleted successfully!',
        ]);
    }

    /**
     * Get new messages (for polling)
     */
    public function getNewMessages(Chat $chat, Request $request)
    {
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        $afterId = $request->get('after', 0);
        
        $messages = $chat->messages()
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'count' => $messages->count(),
        ]);
    }
    /**
     * 🔥 METHOD BARU: Refresh chat picture
     */
    public function refreshPicture(Chat $chat, Request $request)
    {
        if ($chat->account->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $forceRefresh = $request->boolean('force', false);
            
            $result = $this->chatSyncService->refreshChatPicture($chat, $forceRefresh);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'picture_url' => $result['url'],
                ]);
            }

            $errorMsg = $result['error'] ?? 'Failed to refresh picture';
            if (str_contains($errorMsg, 'NOWEB') || str_contains($errorMsg, '400')) {
                return response()->json([
                    'success' => false,
                    'error' => 'NOWEB store not enabled. Run: php artisan waha:enable-noweb --all',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}