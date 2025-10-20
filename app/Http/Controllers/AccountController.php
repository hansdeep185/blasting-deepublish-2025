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

        // Generate unique session name
        $sessionName = Str::slug($request->session_name) . '_' . auth()->id() . '_' . time();

        // STEP 1: Create session in WAHA
        $result = $this->wahaService->createSession($sessionName);

        if (!$result['success']) {
            return back()->with('error', 'Failed to create WAHA session: ' . ($result['error'] ?? 'Unknown error'));
        }

        // STEP 2: Start the session immediately
        $startResult = $this->wahaService->startSession($sessionName);
        
        if (!$startResult['success']) {
            // If start fails, try to delete the created session
            $this->wahaService->deleteSession($sessionName);
            return back()->with('error', 'Failed to start WAHA session: ' . ($startResult['error'] ?? 'Unknown error'));
        }

        // STEP 3: Create account in database
        $account = Account::create([
            'user_id' => auth()->id(),
            'session_name' => $sessionName,
            'status' => 'pending',
            'waha_session_id' => $sessionName,
        ]);

        AuditLog::logActivity(
            action: 'create_account',
            description: 'Created new WhatsApp account',
            modelType: Account::class,
            modelId: $account->id
        );

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account created successfully! Please wait a moment for QR code to generate.');
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

        // Get QR code if status is pending
        $qrCode = null;
        if ($account->isPending()) {
            \Log::info('Account is pending, attempting to get QR code', [
                'account_id' => $account->id,
                'session_name' => $account->session_name,
            ]);
            
            // Get session status first
            $statusResult = $this->wahaService->getSessionStatus($account->session_name);
            $wahaStatus = $statusResult['data']['status'] ?? 'UNKNOWN';
            
            \Log::info('Current WAHA status', [
                'session_name' => $account->session_name,
                'status' => $wahaStatus,
            ]);
            
            // If session is STOPPED, start it first
            if ($wahaStatus === 'STOPPED') {
                \Log::info('Session is STOPPED, starting...', ['session_name' => $account->session_name]);
                
                $startResult = $this->wahaService->startSession($account->session_name);
                
                if ($startResult['success']) {
                    \Log::info('Session started successfully, waiting 3 seconds...');
                    // Wait a bit for session to reach SCAN_QR_CODE status
                    sleep(3);
                } else {
                    \Log::error('Failed to start session', ['error' => $startResult['error'] ?? 'Unknown']);
                }
            }
            
            // Now try to get QR code
            \Log::info('Attempting to get QR code...');
            $qrCode = $this->wahaService->getQrCode($account->session_name);
            
            if ($qrCode) {
                \Log::info('QR code retrieved successfully', [
                    'qr_length' => strlen($qrCode),
                    'is_data_url' => str_starts_with($qrCode, 'data:'),
                ]);
                // Update QR code in database if found
                $account->update(['qr_code' => $qrCode]);
            } else {
                \Log::warning('QR code not available yet', [
                    'account_id' => $account->id,
                    'session_name' => $account->session_name,
                ]);
            }
        }

        // ALWAYS get latest QR from database or service
        if ($account->isPending() && !$qrCode) {
            $qrCode = $account->qr_code; // Try from database
            \Log::info('Using QR from database', [
                'has_qr' => !empty($qrCode),
            ]);
        }

        // Get session status from WAHA
        $statusResult = $this->wahaService->getSessionStatus($account->session_name);
        $wahaStatus = $statusResult['data']['status'] ?? 'UNKNOWN';

        // Update local status if changed
        if (in_array($wahaStatus, ['WORKING', 'AUTHENTICATED']) && !$account->isConnected()) {
            $phoneNumber = $statusResult['data']['me']['id'] ?? null;
            if ($phoneNumber) {
                $phoneNumber = str_replace('@c.us', '', $phoneNumber);
            }
            $account->markAsConnected($phoneNumber);
        } elseif (in_array($wahaStatus, ['FAILED', 'STOPPED']) && !$account->isDisconnected()) {
            $account->markAsDisconnected();
        }

        return view('accounts.show', compact('account', 'qrCode', 'wahaStatus'));
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

        // Use restart endpoint
        $result = $this->wahaService->restartSession($account->session_name);

        if (!$result['success']) {
            return back()->with('error', 'Failed to restart session: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Update account status to pending
        $account->update([
            'status' => 'pending',
            'qr_code' => null,
            'phone_number' => null,
        ]);

        AuditLog::logActivity(
            action: 'reconnect_account',
            description: 'Reconnected WhatsApp account',
            modelType: Account::class,
            modelId: $account->id
        );

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Session restarted. Please scan QR code to reconnect.');
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