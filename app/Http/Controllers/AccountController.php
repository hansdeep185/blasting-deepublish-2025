<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Services\WahaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    protected WahaApiService $wahaService;

    public function __construct(WahaApiService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /**
     * Display a listing of accounts
     */
    public function index()
    {
        $accounts = Account::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        // Check if WAHA is configured
        if (!$this->wahaService->isConfigured()) {
            return redirect()->route('accounts.index')
                ->with('error', 'WAHA is not configured. Please contact administrator.');
        }

        return view('accounts.create');
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_name' => 'required|string|max:255|unique:accounts,session_name',
        ]);

        // Start the session in WAHA with message store enabled
        $result = $this->wahaService->startSession($request->session_name);

        if (!$result['success']) {
            return back()->withInput()->with('error', 'Failed to create WAHA session: ' . ($result['error'] ?? 'Unknown error from WAHA.'));
        }

        $account = Account::create([
            'user_id' => auth()->id(),
            'session_name' => $request->session_name,
            'status' => 'pending', // Session is starting, waiting for QR scan
        ]);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account created. Please connect your phone by scanning the QR code.');
    }

    /**
     * Display the specified account
     */
    public function show(Account $account)
    {
        // Authorization check
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        $qrCode = null;
        
        // Step 1: Get the session status from WAHA
        $statusResult = $this->wahaService->getSessionStatus($account->session_name);
        $wahaStatus = $statusResult['status'] ?? 'UNKNOWN';

        \Log::info('WAHA Status Check', [
            'session_name' => $account->session_name,
            'status' => $wahaStatus,
        ]);

        // Step 2: Try to get QR code based on status
        if ($wahaStatus === 'SCAN_QR_CODE') {
            \Log::info('Status is SCAN_QR_CODE. Attempting to fetch from dedicated endpoint...');
            // First, try the dedicated QR endpoint
            $qrCode = $this->wahaService->getQrCode($account->session_name);

            // If dedicated endpoint fails, try to get it from the status response as a fallback
            if (!$qrCode) {
                \Log::warning('Dedicated QR endpoint failed. Checking for QR in status response.');
                $qrCode = $statusResult['qr'] ?? null;
            }
        }

        // Step 3: Update local status
        if (in_array($wahaStatus, ['WORKING', 'AUTHENTICATED']) && !$account->isConnected()) {
            $phoneNumber = $statusResult['data']['me']['id'] ?? null;
            if ($phoneNumber) {
                $phoneNumber = str_replace('@c.us', '', $phoneNumber);
            }
            $account->markAsConnected($phoneNumber);
            $account->update(['qr_code' => null]); // Clear QR on connection
        } elseif (in_array($wahaStatus, ['FAILED', 'STOPPED']) && !$account->isDisconnected()) {
            $account->markAsDisconnected();
        }

        // Step 4: Save QR to DB if found
        if ($qrCode) {
            \Log::info('QR Code found, updating database.');
            $account->update(['qr_code' => $qrCode]);
        }

        return view('accounts.show', [
            'account' => $account,
            'qrCode' => $qrCode ?? $account->qr_code, // Use fresh QR or fallback to DB
            'wahaStatus' => $wahaStatus,
        ]);
    }

    /**
     * Reconnect account
     */
    public function reconnect(Account $account)
    {
        // Authorization check
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        // Start the session in WAHA with message store enabled to force re-authentication
        $result = $this->wahaService->startSession($account->session_name);

        if (!$result['success']) {
            return redirect()->route('accounts.show', $account)
                ->with('error', 'Failed to start WAHA session: ' . ($result['error'] ?? 'Unknown error from WAHA.'));
        }

        $account->update([
            'status' => 'pending',
            'qr_code' => null,
            'phone_number' => null,
        ]);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Reconnecting... Please scan the QR code if prompted.');
    }

    /**
     * Remove the specified account
     */
    public function destroy(Account $account)
    {
        // Authorization check
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete session from WAHA
        $this->wahaService->deleteSession($account->session_name);

        AuditLog::logActivity(
            action: 'delete_account',
            description: 'Deleted WhatsApp account',
            modelType: Account::class,
            modelId: $account->id
        );

        // Delete from database
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    /**
     * Toggle AI agent for account
     */
    public function toggleAi(Account $account)
    {
        // Authorization check
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        $newStatus = $account->toggleAiAgent();

        AuditLog::logActivity(
            action: 'toggle_ai_agent',
            description: 'Toggled AI agent to ' . ($newStatus ? 'active' : 'inactive'),
            modelType: Account::class,
            modelId: $account->id
        );

        return response()->json([
            'success' => true,
            'ai_agent_active' => $newStatus,
            'message' => 'AI Agent ' . ($newStatus ? 'activated' : 'deactivated'),
        ]);
    }
}