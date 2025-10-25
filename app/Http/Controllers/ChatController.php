<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\MessageService;
use App\Services\MessageSyncService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected MessageService $messageService;
    protected MessageSyncService $syncService;

    public function __construct(MessageService $messageService, MessageSyncService $syncService)
    {
        $this->messageService = $messageService;
        $this->syncService = $syncService;
    }

    /**
     * Display chat list
     */
    public function index(Request $request)
    {
        // Get user's accounts
        $accounts = Account::where('user_id', auth()->id())
            ->where('status', 'connected')
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Please connect a WhatsApp account first!');
        }

        // Default to first account
        $selectedAccount = $request->account_id 
            ? Account::findOrFail($request->account_id)
            : $accounts->first();

        // Check ownership
        if ($selectedAccount->user_id !== auth()->id()) {
            abort(403);
        }

        // Get chats for selected account
        $query = Chat::where('account_id', $selectedAccount->id)
            ->with('latestMessage');

        // Filter
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

        // Search
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

    /**
     * Sync and get new messages from WAHA
     */
    public function syncMessages(Chat $chat, Request $request)
    {
        // Check ownership
        if ($chat->account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $result = $this->syncService->syncChatMessages($chat, 50);

            if ($result['success']) {
                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to sync messages',
            ], 500);

        } catch (\Exception $e) {
            Log::error('SyncMessages controller error', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'An unexpected server error occurred.'
            ], 500);
        }
    }

    /**
     * Display chat conversation
     */
    public function show(Request $request, Account $account, $chatId = null)
    {
        // Check account ownership
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        // Get or create chat if phone provided
        if ($request->filled('phone')) {
            $chat = Chat::getOrCreate($account->id, $request->phone, $request->name);
            return redirect()->route('chats.show', ['account' => $account->id, 'chat' => $chat->id]);
        }

        // Load specific chat
        if ($chatId) {
            $chat = Chat::with(['messages' => function($query) {
                $query->latest()->limit(50);
            }])->findOrFail($chatId);

            // Check ownership
            if ($chat->account_id !== $account->id) {
                abort(403);
            }

            // Mark as read
            $chat->markAsRead();

            return view('chats.conversation', compact('account', 'chat'));
        }

        // No chat selected, show empty state
        return view('chats.conversation', compact('account'))->with('chat', null);
    }

    /**
     * Send message
     */
    public function send(Request $request, Account $account)
    {
        // Check account ownership
        if ($account->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string|max:4096',
        ]);

        $chat = Chat::findOrFail($validated['chat_id']);

        // Check chat belongs to account
        if ($chat->account_id !== $account->id) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        try {
            // Send message via MessageService
            $result = $this->messageService->sendText($account, $chat, $validated['message']);

            if ($result['success']) {
                // Return JSON response on success
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ]);
            }

            // Return JSON response on failure
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send message',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Chat send error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return JSON response on exception
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
        // Check ownership
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
        // Check ownership
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
        ]);
    }
}